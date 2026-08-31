<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\Enrollment;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\StudentMonthlyFee;
use App\Services\SensitiveActivityNotifier;
use App\Services\TrashService;
use App\Services\RegistrationFeeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class SchoolClassController extends Controller
{
    private function authorizeManagement(): void
    {
        abort_unless(auth()->user()?->can('classes.manage'), 403);
    }

    public function index(): View
    {
        $this->authorizeManagement();
        $yearNames = collect(range(now()->year, now()->year + 5))->map(fn (int $year) => (string) $year);
        foreach ($yearNames as $year) AcademicYear::firstOrCreate(['name' => $year], ['starts_on' => "$year-01-01", 'ends_on' => "$year-12-31", 'is_current' => (int) $year === now()->year]);
        return view('classes.index', [
            'classes' => SchoolClass::with('academicYear')->withCount(['enrollments as active_students_count' => fn ($query) => $query->where('status', 'active')])->orderBy('name')->get(),
            'years' => AcademicYear::whereIn('name', $yearNames)->orderBy('name')->get(),
        ]);
    }

    public function show(SchoolClass $schoolClass): View
    {
        $this->authorizeManagement();
        $schoolClass->load(['academicYear', 'enrollments' => fn ($query) => $query->where('status', 'active')->with('student')->orderBy('enrolled_on')]);
        $targetClasses = SchoolClass::whereKeyNot($schoolClass->id)->with('academicYear')->orderBy('name')->get();
        return view('classes.show', compact('schoolClass', 'targetClasses'));
    }

    public function store(Request $request, RegistrationFeeService $registrationFees): RedirectResponse
    {
        $this->authorizeManagement();
        $class = SchoolClass::create($this->validated($request));
        $registrationFees->syncClassInvoices($class);
        return back()->with('success', __('Class created.'));
    }

    public function update(Request $request, SchoolClass $schoolClass, RegistrationFeeService $registrationFees): RedirectResponse
    {
        $this->authorizeManagement();
        $schoolClass->update($this->validated($request));
        if ($schoolClass->fee_mode === 'monthly') {
            $studentIds = $schoolClass->enrollments()->where('status', 'active')->pluck('student_id');
            Student::whereIn('id', $studentIds)->update(['monthly_fee_amount' => $schoolClass->monthly_fee_amount]);
            StudentMonthlyFee::whereIn('student_id', $studentIds)->whereNull('paid_at')->update(['amount' => $schoolClass->monthly_fee_amount ?? 0]);
        }
        $registrationFees->syncClassInvoices($schoolClass);
        return back()->with('success', __('Class updated.'));
    }

    public function transferStudents(Request $request, SchoolClass $schoolClass): RedirectResponse
    {
        $this->authorizeManagement();
        $this->confirmCurrentPassword($request);
        $data = $request->validate(['student_ids' => ['required', 'array', 'min:1'], 'student_ids.*' => ['integer'], 'target_class_id' => ['required', 'exists:school_classes,id']]);
        $target = SchoolClass::findOrFail($data['target_class_id']);
        abort_if($target->is($schoolClass), 422, __('The destination class must be different.'));
        DB::transaction(function () use ($schoolClass, $target, $data): void {
            $enrollments = Enrollment::where('school_class_id', $schoolClass->id)->where('status', 'active')->whereIn('student_id', $data['student_ids'])->get();
            foreach ($enrollments as $enrollment) {
                $alreadyThere = Enrollment::where('school_class_id', $target->id)->where('student_id', $enrollment->student_id)->exists();
                $alreadyThere ? $enrollment->delete() : $enrollment->update(['school_class_id' => $target->id]);
            }
        });
        activity('classes')->causedBy(auth()->user())->performedOn($schoolClass)->log('a transféré des élèves vers '.$target->name);
        SensitiveActivityNotifier::send('Élèves transférés', 'Des élèves ont été transférés de '.$schoolClass->name.' vers '.$target->name.'.');
        return to_route('classes.show', $target)->with('success', __('Students transferred successfully.'));
    }

    public function destroy(Request $request, SchoolClass $schoolClass): RedirectResponse
    {
        abort_unless(auth()->user()?->hasRole('Admin'), 403);
        $this->confirmCurrentPassword($request);
        $studentIds = $schoolClass->enrollments()->where('status', 'active')->pluck('student_id')->unique();
        foreach (Student::whereIn('id', $studentIds)->get() as $student) TrashService::store('student', $student, 'Élève : '.$student->first_name.' '.$student->last_name);
        TrashService::store('school_class', $schoolClass, 'Classe : '.$schoolClass->name);
        DB::transaction(function () use ($schoolClass, $studentIds): void { Student::whereIn('id', $studentIds)->delete(); $schoolClass->delete(); });
        activity('classes')->causedBy(auth()->user())->log('a supprimé une classe et ses élèves inscrits.');
        SensitiveActivityNotifier::send('Classe supprimée', 'Une classe et ses élèves inscrits ont été supprimés définitivement.');
        return to_route('classes.index')->with('success', __('Class and enrolled students permanently deleted.'));
    }

    private function confirmCurrentPassword(Request $request): void
    {
        $request->validate(['password_confirmation_action' => ['required', 'string']]);
        if (! Hash::check($request->input('password_confirmation_action'), auth()->user()->password)) throw ValidationException::withMessages(['password_confirmation_action' => __('The password is incorrect.')]);
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'academic_year_id' => ['required', 'exists:academic_years,id'],
            'training_starts_on' => ['required', 'date'],
            'training_ends_on' => ['required', 'date', 'after_or_equal:training_starts_on'],
            'name' => ['required', 'string', 'max:100'],
            'level' => ['nullable', 'string', 'max:100'],
            'duration_months' => ['required', 'integer', 'min:1', 'max:120'],
            'monthly_fee_due_day' => ['nullable', 'integer', 'min:1', 'max:31'],
            'monthly_fee_amount' => ['nullable', 'numeric', 'min:0'],
            'fee_mode' => ['required', 'in:monthly,registration_only'],
            'registration_fee_amount' => ['nullable', 'numeric', 'min:0', 'required_if:fee_mode,registration_only'],
            'registration_fee_due_date' => ['nullable', 'date'],
            'capacity' => ['nullable', 'integer', 'min:1', 'max:5000'],
        ]);

        $data['duration_months'] = max(1, Carbon::parse($data['training_starts_on'])->diffInMonths(Carbon::parse($data['training_ends_on'])));
        $data['registration_fee_due_date'] = null;
        if ($data['fee_mode'] === 'registration_only') {
            $data['monthly_fee_due_day'] = null;
            $data['monthly_fee_amount'] = null;
        } else {
            $data['registration_fee_amount'] = null;
        }

        return $data;
    }
}
