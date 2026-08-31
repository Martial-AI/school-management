<?php

namespace App\Http\Controllers;

use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\StudentMonthlyFee;
use App\Models\Invoice;
use App\Services\MonthlyFeeReceiptService;
use App\Services\SensitiveActivityNotifier;
use App\Services\SchoolFeeStatusService;
use App\Services\RegistrationFeeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;
use Barryvdh\DomPDF\Facade\Pdf;

class SchoolFeeController extends Controller
{
    public function index(Request $request, SchoolFeeStatusService $feeStatus, RegistrationFeeService $registrationFees): View
    {
        abort_unless(auth()->user()?->can('payments.view'), 403);

        $month = Carbon::createFromFormat('Y-m', $request->string('month')->toString() ?: now()->format('Y-m'))->startOfMonth();
        $search = trim($request->string('search')->toString());
        [$totalOverdue, $overdueStudentIds, $overdueMonths] = $feeStatus->overdueSummary();
        $overdueOnly = $request->boolean('overdue');
        $selectedClassId = $request->integer('class');
        $overdueFilterUrl = route('school-fees.index', array_filter([
            // This screen is a global list: it must not stay limited to the current class.
            'class' => null,
            'search' => $search ?: null,
            'overdue' => $overdueOnly ? null : 1,
        ]));
        $classes = SchoolClass::with('academicYear')->orderBy('name')->get();
        $selectedClass = $selectedClassId ? SchoolClass::with('academicYear')->find($selectedClassId) : null;
        $feeMonths = collect();
        $overdueEnrollments = collect();

        if ($overdueOnly) {
            $overdueEnrollments = \App\Models\Enrollment::query()
                ->where('status', 'active')
                ->whereIn('student_id', $overdueStudentIds)
                ->with(['student.monthlyFees', 'schoolClass'])
                ->get()
                ->filter(fn ($enrollment) => !empty($overdueMonths[$enrollment->student_id] ?? []));
        }

        if ($selectedClass) {
            if ($selectedClass->fee_mode === 'registration_only') $registrationFees->syncClassInvoices($selectedClass);
            $start = Carbon::parse($selectedClass->training_starts_on ?? $selectedClass->academicYear?->starts_on ?? now())->startOfMonth();
            $end = $start->copy()->addMonths(max(1, (int) ($selectedClass->duration_months ?: 12)))->subMonth()->startOfMonth();
            $feeMonths = collect();
            for ($date = $start->copy(); $date->lessThanOrEqualTo($end); $date->addMonth()) $feeMonths->push($date->copy());
            $selectedClass->load(['enrollments' => function ($query) use ($feeMonths, $search, $overdueOnly, $overdueStudentIds): void {
            $query->where('status', 'active')->with('student.invoices');
            if ($overdueOnly) $query->whereIn('student_id', $overdueStudentIds);
            if ($search !== '') {
                $query->whereHas('student', fn ($students) => $students->where(function ($match) use ($search): void {
                    $match->where('first_name', 'like', "%{$search}%")->orWhere('last_name', 'like', "%{$search}%")->orWhere('student_number', 'like', "%{$search}%");
                }));
            }
            }]);
            $selectedClass->enrollments->each(fn ($enrollment) => $enrollment->student?->load(['monthlyFees' => fn ($fees) => $fees->whereBetween('fee_month', [$feeMonths->first()->toDateString(), $feeMonths->last()->toDateString()]) ]));
        }

        $notificationClasses = SchoolClass::with(['enrollments' => function ($query) use ($month): void {
            $query->where('status', 'active')->with(['student.monthlyFees' => fn ($fees) => $fees->whereDate('fee_month', $month->toDateString())]);
        }])->get();
        $this->notifyOverdueStudents($notificationClasses, $month);
        // Include both monthly school fees and registration-fee invoices, exactly as on the dashboard.
        $collectedAmount = StudentMonthlyFee::whereNotNull('paid_at')->sum('amount') + Invoice::sum('amount_paid');

        $registrationInvoices = $selectedClass?->fee_mode === 'registration_only'
            ? $selectedClass->enrollments->mapWithKeys(fn ($enrollment) => [$enrollment->student_id => $enrollment->student->invoices->firstWhere('school_class_id', $selectedClass->id)])
            : collect();
        $registrationFeeRows = $registrationInvoices->map(function ($invoice, $studentId) use ($selectedClass): array {
            $due = $invoice?->due_date;
            $dueAmount = (float) ($invoice?->amount_due ?? $selectedClass->registration_fee_amount ?? 0);
            $paidAmount = (float) ($invoice?->amount_paid ?? 0);
            return [
                'remaining' => max(0, $dueAmount - $paidAmount),
                'paid' => $dueAmount > 0 && $paidAmount >= $dueAmount,
                'overdue' => $due && $due->isPast() && $paidAmount < $dueAmount,
                'due_date' => $due?->format('d/m/Y'),
                'due_date_value' => $due?->format('Y-m-d'),
                'toggle_url' => route('school-fees.registration.toggle', [$selectedClass, $studentId]),
                'details_url' => route('school-fees.registration.details', [$selectedClass, $studentId]),
                'receipt_url' => $invoice ? route('school-fees.registration.receipt', [$selectedClass, $studentId]) : null,
            ];
        });
        $registrationFeeRowsJson = $registrationFeeRows->values();
        if ($selectedClass?->fee_mode === 'registration_only') {
            foreach ($selectedClass->enrollments as $enrollment) {
                $row = $registrationFeeRows->get($enrollment->student_id);
                if (!($row['overdue'] ?? false)) continue;
                $key = 'registration-fee-overdue-'.$selectedClass->id.'-'.$enrollment->student_id;
                if (cache()->add($key, true, now()->endOfDay())) {
                    $student = $enrollment->student;
                    SensitiveActivityNotifier::send('Droit d’inscription en retard', $student->first_name.' '.$student->last_name.' a un droit d’inscription restant à payer pour la classe '.$selectedClass->name.'.');
                }
            }
        }

        return view('school-fees.index', compact('classes', 'selectedClass', 'feeMonths', 'month', 'search', 'totalOverdue', 'overdueStudentIds', 'overdueMonths', 'overdueOnly', 'overdueEnrollments', 'overdueFilterUrl', 'collectedAmount', 'registrationInvoices', 'registrationFeeRows', 'registrationFeeRowsJson'));
    }

    public function toggle(Request $request, Student $student, MonthlyFeeReceiptService $receipts): RedirectResponse
    {
        abort_unless(auth()->user()?->can('payments.view'), 403);
        $data = $request->validate(['month' => ['required', 'date_format:Y-m']]);
        $month = Carbon::createFromFormat('Y-m', $data['month'])->startOfMonth();
        $enrollment = $student->enrollments()->where('status', 'active')->with('schoolClass.academicYear')->firstOrFail();
        $start = Carbon::parse($enrollment->schoolClass->training_starts_on ?? $enrollment->schoolClass->academicYear?->starts_on ?? $enrollment->enrolled_on)->startOfMonth();
        $end = $start->copy()->addMonths(max(1, (int) ($enrollment->schoolClass->duration_months ?: 12)))->subMonth()->startOfMonth();
        if ($month->lt($start) || $month->gt($end)) {
            return back()->with('error', __("This month is outside the student's training duration."));
        }

        $fee = StudentMonthlyFee::firstOrNew(['student_id' => $student->id, 'fee_month' => $month->toDateString()]);
        if ($fee->exists && $fee->paid_at) {
            abort_unless(auth()->user()->hasRole('Admin'), 403);
            $fee->update(['paid_at' => null, 'paid_by' => null, 'receipt_number' => null]);
            activity('ecolages')->causedBy(auth()->user())->performedOn($student)->log('Paiement mensuel annulé depuis les écolages.');
            return back()->with('success', __('Payment canceled.'));
        }

        abort_unless(auth()->user()->can('payments.manage'), 403);
        if ((float) $student->monthly_fee_amount <= 0) {
            return back()->with('error', __('Define the monthly school fee before payment.'));
        }
        $fee->fill(['amount' => $student->monthly_fee_amount, 'paid_at' => now(), 'paid_by' => auth()->id(), 'receipt_number' => $receipts->number()])->save();
        activity('ecolages')->causedBy(auth()->user())->performedOn($student)->log('Paiement mensuel validé depuis les écolages.');

        return back()->with('success', __('Payment recorded.'));
    }

    public function toggleRegistrationFee(SchoolClass $schoolClass, Student $student, RegistrationFeeService $registrationFees): RedirectResponse
    {
        abort_unless(auth()->user()?->can('payments.view') && $schoolClass->fee_mode === 'registration_only', 403);
        abort_unless($student->enrollments()->where('school_class_id', $schoolClass->id)->where('status', 'active')->exists(), 404);
        $invoice = $registrationFees->ensureStudentInvoice($student, $schoolClass);
        abort_unless($invoice, 422, __('The registration fee amount must be defined for this class.'));

        if ((float) $invoice->amount_paid >= (float) $invoice->amount_due) {
            abort_unless(auth()->user()->hasRole('Admin'), 403);
            $invoice->update(['amount_paid' => 0, 'status' => 'unpaid']);
            return back()->with('success', __('Registration fee payment canceled.'));
        }

        abort_unless(auth()->user()->can('payments.manage'), 403);
        $invoice->update(['amount_paid' => $invoice->amount_due, 'status' => 'paid']);
        activity('ecolages')->causedBy(auth()->user())->performedOn($student)->log('Droit d’inscription payé.');
        return back()->with('success', __('Registration fee payment recorded.'));
    }

    public function updateRegistrationFeeDetails(Request $request, SchoolClass $schoolClass, Student $student, RegistrationFeeService $registrationFees): RedirectResponse
    {
        abort_unless(auth()->user()?->can('payments.manage') && $schoolClass->fee_mode === 'registration_only', 403);
        abort_unless($student->enrollments()->where('school_class_id', $schoolClass->id)->where('status', 'active')->exists(), 404);
        $data = $request->validate(['remaining_amount' => ['required', 'numeric', 'min:0'], 'due_date' => ['nullable', 'date']]);
        $invoice = $registrationFees->ensureStudentInvoice($student, $schoolClass);
        abort_unless($invoice, 422);
        $invoice->update([
            'amount_due' => (float) $invoice->amount_paid + (float) $data['remaining_amount'],
            'due_date' => $data['due_date'] ?: null,
            'status' => (float) $data['remaining_amount'] <= 0 ? 'paid' : ((float) $invoice->amount_paid > 0 ? 'partial' : 'unpaid'),
        ]);
        return back()->with('success', __('The registration fee balance and deadline have been updated.'));
    }

    public function registrationFeeReceipt(Request $request, SchoolClass $schoolClass, Student $student): \Symfony\Component\HttpFoundation\Response
    {
        abort_unless(auth()->user()?->can('payments.view'), 403);
        $invoice = Invoice::where('school_class_id', $schoolClass->id)->where('student_id', $student->id)->firstOrFail();
        $pdf = Pdf::loadView('school-fees.registration-fee-receipt', compact('invoice', 'student', 'schoolClass'))->setPaper([0, 0, 226.77, 510.24]);
        return $request->boolean('download') ? $pdf->download('recu-droit-'.$student->student_number.'.pdf') : $pdf->stream('recu-droit-'.$student->student_number.'.pdf');
    }

    public function registrationFeeReceiptPreview(SchoolClass $schoolClass, Student $student): View
    {
        abort_unless(auth()->user()?->can('payments.view'), 403);
        $invoice = Invoice::where('school_class_id', $schoolClass->id)->where('student_id', $student->id)->firstOrFail();
        return view('school-fees.registration-fee-receipt', compact('invoice', 'student', 'schoolClass'));
    }

    private function overdueSummary(): array
    {
        $paid = StudentMonthlyFee::whereNotNull('paid_at')->get(['student_id', 'fee_month'])->mapWithKeys(fn ($fee) => [$fee->student_id.'-'.$fee->fee_month->format('Y-m') => true]);
        $total = 0.0;
        $studentIds = [];
        $enrollments = \App\Models\Enrollment::with(['student:id,monthly_fee_amount,status', 'schoolClass.academicYear'])->where('status', 'active')->get();
        foreach ($enrollments as $enrollment) {
            if ($enrollment->student?->status !== 'active' || (float) $enrollment->student->monthly_fee_amount <= 0) continue;
            $start = Carbon::parse($enrollment->schoolClass->training_starts_on ?? $enrollment->schoolClass->academicYear?->starts_on ?? $enrollment->enrolled_on)->startOfMonth();
            $end = $start->copy()->addMonths(max(1, (int) ($enrollment->schoolClass->duration_months ?: 12)))->subMonth()->startOfMonth();
            $dueDay = (int) ($enrollment->schoolClass->monthly_fee_due_day ?: 5);
            for ($date = $start->copy(); $date->lessThanOrEqualTo($end) && $date->lessThanOrEqualTo(now()->startOfMonth()); $date->addMonth()) {
                $dueAt = $date->copy()->day(min($dueDay, $date->daysInMonth))->endOfDay();
                if ($dueAt->isPast() && !isset($paid[$enrollment->student_id.'-'.$date->format('Y-m')])) {
                    $total += (float) $enrollment->student->monthly_fee_amount;
                    $studentIds[$enrollment->student_id] = true;
                }
            }
        }
        return [$total, array_keys($studentIds)];
    }

    private function notifyOverdueStudents($classes, Carbon $month): void
    {
        foreach ($classes as $class) {
            $start = Carbon::parse($class->training_starts_on ?? $class->academicYear?->starts_on ?? now())->startOfMonth();
            $end = $start->copy()->addMonths(max(1, (int) ($class->duration_months ?: 12)))->subMonth()->startOfMonth();
            if ($month->lt($start) || $month->gt($end)) {
                continue;
            }
            $dueDay = (int) ($class->monthly_fee_due_day ?: 5);
            $dueAt = $month->copy()->day(min($dueDay, $month->daysInMonth))->endOfDay();
            if (!$dueAt->isPast()) {
                continue;
            }

            foreach ($class->enrollments as $enrollment) {
                $student = $enrollment->student;
                $fee = $student?->monthlyFees->first();
                if (!$student || $fee?->paid_at || (float) $student->monthly_fee_amount <= 0) {
                    continue;
                }

                $key = 'school-fee-overdue-v2-'.$student->id.'-'.$month->format('Y-m');
                if (cache()->add($key, true, now()->endOfDay())) {
                    SensitiveActivityNotifier::send('Écolage en retard', $student->first_name.' '.$student->last_name.' ('.$student->student_number.') a un écolage en retard pour '.$month->locale('fr')->translatedFormat('F Y').'.');
                    activity('ecolages')->causedBy(auth()->user())->performedOn($student)->log('Écolage en retard : '.$student->first_name.' '.$student->last_name.' — '.$month->locale('fr')->translatedFormat('F Y').'.');
                }
            }
        }
    }
}
