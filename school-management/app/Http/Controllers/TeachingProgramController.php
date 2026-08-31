<?php

namespace App\Http\Controllers;

use App\Models\ClassSchedule;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\TeachingProgram;
use App\Models\ProgramLesson;
use App\Models\TeacherAssignment;
use App\Models\User;
use App\Services\SensitiveActivityNotifier;
use App\Services\TrashService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class TeachingProgramController extends Controller
{
    public function index(): View
    {
        abort_unless(auth()->user()?->hasAnyRole(['Admin', 'Prof']), 403);

        $query = TeachingProgram::with(['lessons', 'teacher', 'schoolClass', 'subject'])->latest();
        if (! auth()->user()->can('roles.manage')) {
            $query->where('teacher_id', auth()->id());
        }

        return view('programs.index', ['programs' => $query->get()]);
    }

    public function create(): View
    {
        abort_unless(auth()->user()?->hasAnyRole(['Admin', 'Prof']), 403);

        return view('programs.create', $this->formData());
    }

    public function schedule(Request $request): View
    {
        return view('programs.schedule', $this->scheduleContext($request));
    }

    public function schedulePdf(Request $request)
    {
        $context = $this->scheduleContext($request);
        abort_unless($context['selectedClass'], 404);

        return Pdf::loadView('programs.schedule-pdf', [
            'schoolClass' => $context['selectedClass'],
            'slots' => $context['slots'],
            'dayLabels' => $context['dayLabels'],
            'showTeacher' => $context['isAdmin'],
        ])->setPaper('a4', 'landscape')->download('emploi-du-temps-'.Str::slug($context['selectedClass']->name).'.pdf');
    }

    public function storeScheduleSlot(Request $request): RedirectResponse
    {
        $this->authorizeScheduleManagement();

        $data = $this->validatedScheduleSlot($request);
        $data['position'] = ((int) ClassSchedule::where('school_class_id', $data['school_class_id'])->max('position')) + 1;
        $slot = ClassSchedule::create($data);

        $this->logScheduleActivity('a ajouté un créneau à l’emploi du temps de', $slot->school_class_id);

        return to_route('programs.schedule', ['class_id' => $slot->school_class_id])
            ->with('success', __('Schedule updated.'));
    }

    public function updateScheduleSlot(Request $request, ClassSchedule $classSchedule): RedirectResponse
    {
        $this->authorizeScheduleManagement();

        $data = $this->validatedScheduleSlot($request);
        $classSchedule->update($data);

        $this->logScheduleActivity('a modifié l’emploi du temps de', $classSchedule->school_class_id);

        return to_route('programs.schedule', ['class_id' => $classSchedule->school_class_id])
            ->with('success', __('Schedule updated.'));
    }

    public function destroyScheduleSlot(ClassSchedule $classSchedule): RedirectResponse
    {
        $this->authorizeScheduleManagement();

        $classId = $classSchedule->school_class_id;
        $this->logScheduleActivity('a supprimé un créneau de l’emploi du temps de', $classId);
        $classSchedule->delete();

        return to_route('programs.schedule', ['class_id' => $classId])
            ->with('success', __('Schedule updated.'));
    }

    private function scheduleContext(Request $request): array
    {
        abort_unless(auth()->user()?->hasAnyRole(['Admin', 'Prof']), 403);

        $user = auth()->user();
        $isAdmin = $user->can('roles.manage');
        $classesQuery = SchoolClass::query()->orderBy('name');

        if (! $isAdmin) {
            $classIds = $user->teacherAssignments()->pluck('school_class_id')
                ->merge(TeachingProgram::where('teacher_id', $user->id)->pluck('school_class_id'))
                ->unique()
                ->values();
            $classesQuery->whereIn('id', $classIds);
        }

        $classes = $classesQuery->get();
        $requestedClassId = $request->integer('class_id');
        $selectedClass = $requestedClassId
            ? $classes->firstWhere('id', $requestedClassId)
            : $classes->first();

        abort_if($requestedClassId && ! $selectedClass, 403);

        $slots = collect();
        if ($selectedClass) {
            $slotsQuery = ClassSchedule::with(['subject', 'teacher'])
                ->where('school_class_id', $selectedClass->id);

            if (! $isAdmin) {
                $slotsQuery->where('teacher_id', $user->id);
            }

            $slots = $slotsQuery->orderBy('day_of_week')->orderBy('starts_at')->orderBy('position')->get();
        }

        $teacherIds = $selectedClass
            ? TeacherAssignment::where('school_class_id', $selectedClass->id)->pluck('teacher_id')
                ->merge($slots->pluck('teacher_id')->filter())->unique()
            : collect();

        return [
            'classes' => $classes,
            'selectedClass' => $selectedClass,
            'slots' => $slots,
            'subjects' => Subject::where('is_active', true)->orderBy('name')->get(),
            'teachers' => User::role('Prof')->whereIn('id', $teacherIds)->orderBy('first_name')->orderBy('last_name')->get(),
            'dayLabels' => $this->scheduleDayLabels(),
            'isAdmin' => $isAdmin,
            'programs' => $this->programsForSchedule($user, $isAdmin),
        ];
    }

    private function programsForSchedule(User $user, bool $isAdmin)
    {
        $query = TeachingProgram::with(['teacher', 'schoolClass', 'subject'])->orderBy('starts_on')->orderBy('ends_on');

        if (! $isAdmin) {
            $query->where('teacher_id', $user->id);
        }

        return $query->get();
    }

    private function validatedScheduleSlot(Request $request): array
    {
        $data = $request->validate([
            'school_class_id' => ['required', 'exists:school_classes,id'],
            'day_of_week' => ['required', 'integer', 'between:1,6'],
            'subject_id' => ['required', 'exists:subjects,id'],
            'teacher_id' => ['required', 'exists:users,id'],
            'starts_at' => ['required', 'date_format:H:i'],
            'ends_at' => ['required', 'date_format:H:i'],
        ]);

        if ($data['ends_at'] <= $data['starts_at']) {
            throw ValidationException::withMessages(['ends_at' => __('The end time must be after the start time.')]);
        }

        $isAssignedTeacher = User::role('Prof')->whereKey($data['teacher_id'])->exists()
            && TeacherAssignment::where('teacher_id', $data['teacher_id'])
                ->where('school_class_id', $data['school_class_id'])->exists();

        if (! $isAssignedTeacher) {
            throw ValidationException::withMessages(['teacher_id' => __('Choose a teacher assigned to this class.')]);
        }

        return $data;
    }

    private function authorizeScheduleManagement(): void
    {
        abort_unless(auth()->user()?->can('roles.manage'), 403);
    }

    private function scheduleDayLabels(): array
    {
        return [
            1 => __('Monday'),
            2 => __('Tuesday'),
            3 => __('Wednesday'),
            4 => __('Thursday'),
            5 => __('Friday'),
            6 => __('Saturday'),
        ];
    }

    private function logScheduleActivity(string $action, int $classId): void
    {
        $className = SchoolClass::find($classId)?->name ?? __('Class');
        activity('programmes')->causedBy(auth()->user())->log($action.' '.$className);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless(auth()->user()?->hasAnyRole(['Admin', 'Prof']), 403);

        $program = TeachingProgram::create($this->validatedData($request));
        activity('programmes')->causedBy(auth()->user())->performedOn($program)->log('a créé le programme '.$program->title);

        return to_route('programs.index')->with('success', __('Program created successfully.'));
    }

    public function edit(TeachingProgram $program): View
    {
        $this->authorizeProgram($program);

        $program->load('lessons.children');
        return view('programs.edit', ['program' => $program, ...$this->formData()]);
    }

    public function update(Request $request, TeachingProgram $program): RedirectResponse
    {
        $this->authorizeProgram($program);
        $program->update($this->validatedData($request));
        foreach ($request->input('chapters', []) as $index => $chapter) {
            $title = trim((string) ($chapter['title'] ?? ''));
            if ($title === '') continue;
            ProgramLesson::create(['teaching_program_id' => $program->id, 'parent_id' => filled($chapter['parent_id'] ?? null) ? $chapter['parent_id'] : null, 'title' => $title, 'description' => $chapter['description'] ?? null, 'position' => $program->lessons()->count() + $index + 1]);
        }
        activity('programmes')->causedBy(auth()->user())->performedOn($program)->log('a modifié le programme '.$program->title);

        return to_route('programs.index')->with('success', __('Program updated successfully.'));
    }

    public function follow(TeachingProgram $program): View
    {
        $this->authorizeProgram($program);
        $program->load(['lessons' => fn ($query) => $query->whereNull('parent_id')->with('children')]);
        return view('programs.follow', compact('program'));
    }

    public function toggleLesson(Request $request, TeachingProgram $program, ProgramLesson $lesson): RedirectResponse
    {
        $this->authorizeProgram($program);
        abort_unless($lesson->teaching_program_id === $program->id, 404);
        $data = $request->validate(['completed' => ['nullable', 'boolean'], 'completion_note' => ['nullable', 'string', 'max:1000']]);
        $lesson->update(['completed_at' => $request->boolean('completed') ? now() : null, 'completion_note' => $data['completion_note'] ?? null]);
        activity('programmes')->causedBy(auth()->user())->performedOn($program)->log('a mis à jour le suivi : '.$lesson->title);
        return back()->with('success', __('Course progress updated.'));
    }

    public function destroy(Request $request, TeachingProgram $program): RedirectResponse
    {
        abort_unless(auth()->user()?->can('roles.manage'), 403);
        $request->validate(['admin_password' => ['required', 'string']]);
        if (! Hash::check($request->input('admin_password'), auth()->user()->password)) throw ValidationException::withMessages(['admin_password' => __('The administrator password is incorrect.')]);
        $title = $program->title;
        TrashService::store('program', $program, 'Programme : '.$title, ['lessons' => $program->lessons()->get()->map(fn ($lesson) => $lesson->getAttributes())->all()]);
        activity('programmes')->causedBy(auth()->user())->performedOn($program)->log('a supprimé le programme '.$title);
        SensitiveActivityNotifier::send('Programme supprimé', 'Le programme '.$title.' a été supprimé définitivement.');
        $program->delete();
        return to_route('programs.index')->with('success', __('Program permanently deleted.'));
    }

    private function formData(): array
    {
        return [
            'teachers' => User::role('Prof')->get(),
            'classes' => SchoolClass::all(),
            'subjects' => Subject::all(),
            'isAdmin' => auth()->user()->can('roles.manage'),
        ];
    }

    private function validatedData(Request $request): array
    {
        $data = $request->validate([
            'teacher_id' => ['nullable', 'exists:users,id'],
            'school_class_id' => ['required', 'exists:school_classes,id'],
            'subject_name' => ['required', 'string', 'max:150'],
            'period_name' => ['required', 'string', 'max:255'],
            'starts_on' => ['required', 'date'],
            'ends_on' => ['required', 'date', 'after_or_equal:starts_on'],
            'title' => ['required', 'string', 'max:255'],
            'objectives' => ['nullable', 'string'],
        ]);

        $subject = Subject::firstOrCreate(
            ['name' => $data['subject_name']],
            ['code' => strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $data['subject_name']), 0, 8)) ?: 'MAT'.random_int(100, 999)]
        );

        $data['subject_id'] = $subject->id;
        unset($data['subject_name']);
        $data['teacher_id'] = auth()->user()->can('roles.manage') ? $data['teacher_id'] : auth()->id();

        return $data;
    }

    private function authorizeProgram(TeachingProgram $program): void
    {
        abort_unless(auth()->user()?->hasAnyRole(['Admin', 'Prof']), 403);
        abort_unless(auth()->user()->can('roles.manage') || $program->teacher_id === auth()->id(), 403);
    }
}
