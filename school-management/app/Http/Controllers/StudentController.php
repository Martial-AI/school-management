<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreStudentRequest;
use App\Models\Guardian;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\StudentMonthlyFee;
use App\Services\StudentCardService;
use App\Services\StudentIdentifierService;
use App\Services\SensitiveActivityNotifier;
use App\Services\TrashService;
use App\Services\MonthlyFeeReceiptService;
use App\Services\RegistrationFeeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Carbon;
use Illuminate\View\View;
use Barryvdh\DomPDF\Facade\Pdf;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class StudentController extends Controller
{
    use AuthorizesRequests;
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Student::class);
        $user = $request->user();
        $isTeacher = $user->hasRole('Prof');
        $teacherClassIds = $isTeacher
            ? $user->teacherAssignments()->pluck('school_class_id')->map(fn ($id) => (int) $id)->all()
            : [];
        $classId = $request->integer('class_id') ?: null;
        if ($isTeacher && $classId !== null && ! in_array($classId, $teacherClassIds, true)) $classId = null;
        $term = $isTeacher ? '' : $request->string('q')->toString();
        $students = Student::query()->with(['enrollments' => function ($enrollments) use ($isTeacher, $teacherClassIds): void {
                $enrollments->where('status', 'active');
                if ($isTeacher) $enrollments->whereIn('school_class_id', $teacherClassIds);
                $enrollments->with('schoolClass');
            }])
            ->whereHas('enrollments', function ($enrollments) use ($classId, $isTeacher, $teacherClassIds): void {
                $enrollments->where('status', 'active');
                if ($isTeacher) $enrollments->whereIn('school_class_id', $teacherClassIds);
                if ($classId) $enrollments->where('school_class_id', $classId);
            })
            ->when($term, fn ($query) => $query->where(fn ($q) => $q->where('student_number', 'like', "%{$term}%")->orWhere('first_name', 'like', "%{$term}%")->orWhere('last_name', 'like', "%{$term}%")))
            ->latest()->paginate(15)->withQueryString();
        $classes = SchoolClass::query()->whereRaw("LOWER(name) NOT LIKE '%ecolage%' AND LOWER(name) NOT LIKE '%écolage%'")->withCount(['enrollments as active_students_count' => fn ($query) => $query->where('status', 'active')])->orderBy('name')->get();
        $totalStudents = $isTeacher ? null : Student::whereHas('enrollments', fn ($query) => $query->where('status', 'active'))->count();
        if ($isTeacher) $classes = $classes->whereIn('id', $teacherClassIds)->values();
        return view('students.index', compact('students', 'classes', 'classId', 'totalStudents', 'isTeacher'));
    }

    public function scanQr(Request $request): RedirectResponse|\Illuminate\Http\JsonResponse
    {
        $this->authorize('viewAny', Student::class);
        abort_if($request->user()->hasRole('Prof'), 403, __('This action is not available for teachers.'));
        $value = trim($request->string('value')->toString());
        $value = trim(explode('|', $value, 2)[0]);
        $student = Student::query()
            ->where('qr_token', $value)
            ->orWhere('student_number', $value)
            ->first();

        if (!$student) {
            if ($request->expectsJson()) return response()->json(['message' => __('No student.')], 404);
            return back()->with('error', __('No student.'));
        }

        $this->authorize('view', $student);
        $url = route('students.show', $student);
        return $request->expectsJson() ? response()->json(['url' => $url]) : to_route('students.show', $student);
    }

    public function exportList(): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $this->authorize('viewAny', Student::class);
        abort_if(auth()->user()->hasRole('Prof'), 403, __('This action is not available for teachers.'));
        $students = Student::with(['enrollments' => fn ($query) => $query->where('status', 'active')->with('schoolClass')])
            ->whereHas('enrollments', fn ($query) => $query->where('status', 'active'))
            ->orderBy('last_name')->orderBy('first_name')->get();

        return response()->streamDownload(function () use ($students): void {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, [__('Class'), __('Student number'), __('Last name'), __('First name'), __('Email'), __('Phone')], ';');
            foreach ($students->sortBy(fn ($student) => $student->enrollments->first()?->schoolClass?->name ?? '') as $student) {
                $class = $student->enrollments->first()?->schoolClass?->name ?? '—';
                fputcsv($handle, [$class, $student->student_number, $student->last_name, $student->first_name, $student->email ?? '', $student->phone ?? ''], ';');
            }
            fclose($handle);
        }, 'liste-eleves-par-classe.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        $this->authorize('create', Student::class);
        abort_if(auth()->user()->hasRole('Prof'), 403, __('This action is not available for teachers.'));
        $classes = SchoolClass::query()->with('academicYear')->orderBy('name')->get();
        $classPaymentOptions = $classes->map(fn (SchoolClass $class) => [
            'id' => $class->id,
            'mode' => $class->fee_mode,
            'registration_amount' => (float) $class->registration_fee_amount,
            'monthly_amount' => (float) $class->monthly_fee_amount,
            'start' => $class->training_starts_on?->format('Y-m-d'),
            'duration' => (int) $class->duration_months,
        ])->values();
        return view('students.create', compact('classes', 'classPaymentOptions'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreStudentRequest $request, StudentIdentifierService $identifiers, RegistrationFeeService $registrationFees, MonthlyFeeReceiptService $receipts): RedirectResponse
    {
        $student = DB::transaction(function () use ($request, $identifiers): Student {
            $data = $request->safe()->except(['guardian', 'school_class_id', 'photo', 'registration_payment', 'registration_remaining_amount', 'registration_due_date', 'monthly_fee_months']);
            $data['student_number'] = $identifiers->next();
            if ($request->hasFile('photo')) $data['photo_path'] = $request->file('photo')->store('students/photos', 'local');
            $student = Student::create($data);
            $guardian = Guardian::create($request->validated('guardian'));
            $student->guardians()->attach($guardian, ['is_primary_contact' => true]);
            $student->enrollments()->create(['school_class_id' => $request->integer('school_class_id'), 'enrolled_on' => today(), 'status' => 'active']);
            return $student;
        });
        $class = SchoolClass::findOrFail($request->integer('school_class_id'));
        if ($class->fee_mode === 'registration_only') {
            $registrationFees->recordAtEnrollment($student, $class, $request->string('registration_payment')->toString() ?: 'none', $request->float('registration_remaining_amount'), $request->string('registration_due_date')->toString() ?: null);
        }
        if ($class->fee_mode === 'monthly') $student->update(['monthly_fee_amount' => $class->monthly_fee_amount]);
        if ($class->fee_mode === 'monthly' && auth()->user()?->can('payments.manage')) {
            foreach ($request->input('monthly_fee_months', []) as $feeMonth) {
                if ((float) $class->monthly_fee_amount <= 0) break;
                $date = Carbon::createFromFormat('Y-m', $feeMonth)->startOfMonth();
                StudentMonthlyFee::firstOrCreate(['student_id' => $student->id, 'fee_month' => $date->toDateString()], ['amount' => $class->monthly_fee_amount, 'paid_at' => now(), 'paid_by' => auth()->id(), 'receipt_number' => $receipts->number()]);
            }
        }
        return to_route('students.index')->with('success', __('Student enrolled successfully.'))->with('enrollment_receipt', [
            'preview' => route('students.enrollment-receipt-preview', $student),
            'pdf' => route('students.enrollment-receipt', $student),
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(Student $student): View
    {
        $this->authorize('view', $student);
        $canViewFees = auth()->user()->can('payments.view');
        $student->load('guardians', 'enrollments.schoolClass.academicYear', 'documents');
        $months = collect();
        $trainingClass = $student->enrollments->firstWhere('status', 'active')?->schoolClass;
        if ($canViewFees && $trainingClass?->fee_mode !== 'registration_only') {
            $student->load('invoices', 'monthlyFees.paidBy');
            $fees = $student->monthlyFees->keyBy(fn (StudentMonthlyFee $fee) => $fee->fee_month->format('Y-m'));
            $start = $trainingClass?->training_starts_on ? Carbon::parse($trainingClass->training_starts_on)->startOfMonth() : ($trainingClass?->academicYear?->starts_on ? Carbon::parse($trainingClass->academicYear->starts_on)->startOfMonth() : now()->startOfMonth());
            $end = $start->copy()->addMonths(max(1, (int) ($trainingClass?->duration_months ?? 12)))->subMonth()->startOfMonth();
            $months = collect();
            for ($date = $start->copy(); $date->lessThanOrEqualTo($end); $date->addMonth()) {
                // Store an independent date object: the loop increments $date after each pass.
                // Reusing it here would display the paid status one month too late.
                $feeMonth = $date->copy();
                $fee = $fees->get($feeMonth->format('Y-m'));
                $months->push(['date' => $feeMonth, 'fee' => $fee, 'amount' => $fee?->paid_at ? $fee->amount : $student->monthly_fee_amount]);
            }
        }
        $receiptUrls = $months->map(function (array $item) use ($student): ?array {
            if (!$item['fee']?->paid_at) {
                return null;
            }

            return [
                'preview' => route('students.monthly-fees.receipt-preview', [$student, $item['fee']]),
                'pdf' => route('students.monthly-fees.receipt', [$student, $item['fee']]),
            ];
        })->values();
        $registrationInvoice = $canViewFees && $trainingClass?->fee_mode === 'registration_only'
            ? $student->invoices->firstWhere('school_class_id', $trainingClass->id)
            : null;

        $studentQr = base64_encode(QrCode::format('svg')->size(420)->margin(0)->generate($student->qr_token ?: $student->student_number));
        $studentPhotoUrl = $student->photo_path ? route('students.photo', $student) : '';
        return view('students.show', compact('student', 'months', 'canViewFees', 'trainingClass', 'receiptUrls', 'registrationInvoice', 'studentQr', 'studentPhotoUrl'));
    }

    public function photo(Student $student): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $this->authorize('view', $student);
        abort_unless($student->photo_path && Storage::disk('local')->exists($student->photo_path), 404);
        return Storage::disk('local')->response($student->photo_path);
    }

    public function card(Student $student, StudentCardService $cards): \Symfony\Component\HttpFoundation\Response
    {
        $this->authorize('view', $student);
        return $cards->pdf($student)->download("carte-{$student->student_number}.pdf");
    }

    public function enrollmentReceipt(Request $request, Student $student): \Symfony\Component\HttpFoundation\Response
    {
        $this->authorize('view', $student);
        $student->load(['enrollments.schoolClass', 'monthlyFees']);
        $schoolClass = $student->enrollments->firstWhere('status', 'active')?->schoolClass;
        $registrationInvoice = $schoolClass?->fee_mode === 'registration_only' ? $student->invoices()->where('school_class_id', $schoolClass->id)->first() : null;
        $monthlyPayments = $student->monthlyFees->whereNotNull('paid_at');
        $pdf = Pdf::loadView('students.enrollment-receipt', compact('student', 'schoolClass', 'registrationInvoice', 'monthlyPayments'))->setPaper([0, 0, 226.77, 510.24]);
        return $request->boolean('download') ? $pdf->download('recu-inscription-'.$student->student_number.'.pdf') : $pdf->stream('recu-inscription-'.$student->student_number.'.pdf');
    }

    public function enrollmentReceiptPreview(Student $student): View
    {
        $this->authorize('view', $student);
        $student->load(['enrollments.schoolClass', 'monthlyFees']);
        $schoolClass = $student->enrollments->firstWhere('status', 'active')?->schoolClass;
        $registrationInvoice = $schoolClass?->fee_mode === 'registration_only' ? $student->invoices()->where('school_class_id', $schoolClass->id)->first() : null;
        $monthlyPayments = $student->monthlyFees->whereNotNull('paid_at');
        return view('students.enrollment-receipt', compact('student', 'schoolClass', 'registrationInvoice', 'monthlyPayments'));
    }

    public function monthlyFeeReceipt(Request $request, Student $student, StudentMonthlyFee $fee, MonthlyFeeReceiptService $receipts): \Symfony\Component\HttpFoundation\Response
    {
        $this->authorize('view', $student);
        abort_unless(auth()->user()->can('payments.view') && $fee->student_id === $student->id && $fee->paid_at, 403);
        if (blank($fee->receipt_number)) $fee->update(['receipt_number' => $receipts->number()]);
        $pdf = $receipts->make($fee);
        $filename = "recu-{$fee->receipt_number}.pdf";

        return $request->boolean('download')
            ? $pdf->download($filename)
            : $pdf->stream($filename);
    }

    public function monthlyFeeReceiptPreview(Student $student, StudentMonthlyFee $fee): View
    {
        $this->authorize('view', $student);
        abort_unless(auth()->user()->can('payments.view') && $fee->student_id === $student->id && $fee->paid_at, 403);

        $fee->loadMissing('student.enrollments.schoolClass', 'paidBy');

        return view('students.monthly-fee-receipt', compact('fee'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Student $student): View
    {
        $this->authorize('update', $student);
        $student->load('guardians');
        return view('students.edit', ['student' => $student, 'classes' => SchoolClass::orderBy('name')->get()]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Student $student): RedirectResponse
    {
        $this->authorize('update', $student);
        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'gender' => ['nullable', 'in:male,female'],
            'birth_date' => ['nullable', 'date'],
            'birth_place' => ['nullable', 'string', 'max:150'],
            'phone' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string', 'max:1000'],
            'monthly_fee_amount' => ['nullable', 'numeric', 'min:0'],
            'photo' => ['nullable', 'image', 'max:5120'],
            'guardian' => ['nullable', 'array'],
            'guardian.first_name' => ['required_with:guardian', 'string', 'max:100'],
            'guardian.last_name' => ['required_with:guardian', 'string', 'max:100'],
            'guardian.phone' => ['required_with:guardian', 'string', 'max:30'],
            'guardian.email' => ['nullable', 'email', 'max:255'],
        ]);
        $guardianData = $data['guardian'] ?? null;
        unset($data['guardian'], $data['photo']);
        $oldPhoto = $student->photo_path;
        if ($request->hasFile('photo')) {
            $data['photo_path'] = $request->file('photo')->store('students/photos', 'local');
        }
        $student->update($data);
        if (!empty($data['photo_path']) && $oldPhoto && $oldPhoto !== $data['photo_path']) {
            Storage::disk('local')->delete($oldPhoto);
        }
        if ($guardianData) {
            $guardian = $student->guardians()->wherePivot('is_primary_contact', true)->first() ?? $student->guardians()->first();
            if ($guardian) {
                $guardian->update($guardianData);
            } else {
                $guardian = Guardian::create($guardianData);
                $student->guardians()->attach($guardian, ['is_primary_contact' => true]);
            }
        }
        if (array_key_exists('monthly_fee_amount', $data)) {
            $student->monthlyFees()->whereNull('paid_at')->update(['amount' => $data['monthly_fee_amount'] ?? 0]);
        }
        return to_route('students.show', $student)->with('success', __('Student record updated.'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Student $student): RedirectResponse
    {
        $this->authorize('delete', $student);
        $student->update(['status' => 'left']);
        return to_route('students.index')->with('success', __('Student archived.'));
    }

    public function destroyPermanently(Request $request, Student $student): RedirectResponse
    {
        abort_unless(auth()->user()?->hasRole('Admin'), 403);
        $request->validate(['password_confirmation_action' => ['required', 'string']]);
        if (! Hash::check($request->input('password_confirmation_action'), auth()->user()->password)) {
            throw ValidationException::withMessages(['password_confirmation_action' => __('The password is incorrect.')]);
        }
        $name = $student->first_name.' '.$student->last_name;
        TrashService::store('student', $student, 'Élève : '.$name);
        activity('eleves')->causedBy(auth()->user())->performedOn($student)->log('a supprimé définitivement l’élève '.$name);
        SensitiveActivityNotifier::send('Élève supprimé', 'La fiche de l’élève '.$name.' a été supprimée définitivement.');
        $student->delete();
        return to_route('students.index')->with('success', __('Student permanently deleted.'));
    }

    public function toggleMonthlyFee(Request $request, Student $student, MonthlyFeeReceiptService $receipts): RedirectResponse
    {
        $this->authorize('view', $student);
        $data = $request->validate(['month' => ['required', 'date_format:Y-m']]);
        $month = Carbon::createFromFormat('Y-m', $data['month'])->startOfMonth();
        $fee = StudentMonthlyFee::firstOrNew(['student_id' => $student->id, 'fee_month' => $month->toDateString()]);

        // An administrator must always be able to cancel an already recorded payment,
        // even if the formation dates were modified later.
        if ($fee->exists && $fee->paid_at) {
            abort_unless(auth()->user()->hasRole('Admin'), 403, __('Only the administrator can cancel a confirmed payment.'));
            $fee->update(['paid_at' => null, 'paid_by' => null]);
            activity('ecolages')->causedBy(auth()->user())->performedOn($student)->log('Paiement mensuel annulé.');
            return back()->with('success', __('Monthly payment canceled.'));
        }

        $enrollment = $student->enrollments()->where('status', 'active')->with('schoolClass.academicYear')->first();
        // The payable months follow the actual class training dates, not merely the academic year.
        $class = $enrollment?->schoolClass;
        $start = $class?->training_starts_on
            ? Carbon::parse($class->training_starts_on)->startOfMonth()
            : ($class?->academicYear?->starts_on ? Carbon::parse($class->academicYear->starts_on)->startOfMonth() : null);
        $duration = $enrollment?->schoolClass?->duration_months;
        abort_if(! $start || ! $duration || $month->lt($start) || $month->gte($start->copy()->addMonths($duration)), 422, __('This month is outside the training duration of this class.'));

        abort_unless(auth()->user()->can('payments.manage'), 403);
        if ((float) ($student->monthly_fee_amount ?? 0) <= 0) {
            return back()->with('error', __('Define the monthly school fee amount before confirming a payment.'));
        }
        $fee->amount = $student->monthly_fee_amount ?? 0;
        $fee->receipt_number = $receipts->number();
        $fee->paid_at = now();
        $fee->paid_by = auth()->id();
        $fee->save();
        activity('ecolages')->causedBy(auth()->user())->performedOn($student)->log('Paiement mensuel validé.');
        return back()->with('success', __('Monthly payment recorded.'));
    }
}
