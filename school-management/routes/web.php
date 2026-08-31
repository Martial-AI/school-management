<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\SchoolClassController;
use App\Http\Controllers\SchoolFeeController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ActivityNotificationController;
use App\Http\Controllers\DataSyncController;
use App\Http\Controllers\SessionHeartbeatController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\HistoryController;
use App\Http\Controllers\Admin\TrashController;
use App\Http\Controllers\TeachingProgramController;
use App\Http\Controllers\ExpenseController;
use App\Models\Invoice;
use App\Models\Student;
use App\Models\Attendance;
use App\Models\Enrollment;
use App\Models\StudentMonthlyFee;
use App\Models\User;
use App\Models\Expense;
use App\Services\ExpenseService;
use App\Services\SchoolFeeStatusService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

Route::get('/', function () {
    return redirect()->route(auth()->check() ? 'dashboard' : 'login');
});

Route::post('/language/{locale}', function (string $locale) {
    abort_unless(in_array($locale, ['fr', 'en'], true), 404);
    session(['locale' => $locale]);
    return back();
})->name('language.switch');

Route::middleware(['auth'])->group(function (): void {
    Route::post('notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.read-all');
    Route::post('activity-notifications/read', [ActivityNotificationController::class, 'markRead'])->name('activity-notifications.read');
    Route::get('activity-notifications/feed', [ActivityNotificationController::class, 'feed'])->name('activity-notifications.feed');
    Route::delete('activity-notifications', [ActivityNotificationController::class, 'destroy'])->name('activity-notifications.destroy');
    Route::get('data-sync/version', [DataSyncController::class, 'version'])->name('data-sync.version');
    Route::post('session-heartbeat/ping', [SessionHeartbeatController::class, 'ping'])->name('session-heartbeat.ping');
    Route::post('session-heartbeat/close', [SessionHeartbeatController::class, 'close'])->name('session-heartbeat.close');
    Route::get('programs/version', [DataSyncController::class, 'programsVersion'])->name('programs.version');
    Route::get('admin/users', [UserController::class, 'index'])->name('admin.users.index');
    Route::get('classes', [SchoolClassController::class, 'index'])->name('classes.index');
    Route::get('classes/{schoolClass}', [SchoolClassController::class, 'show'])->name('classes.show');
    Route::post('classes', [SchoolClassController::class, 'store'])->name('classes.store');
    Route::put('classes/{schoolClass}', [SchoolClassController::class, 'update'])->name('classes.update');
    Route::patch('classes/{schoolClass}/transfer-students', [SchoolClassController::class, 'transferStudents'])->name('classes.transfer-students');
    Route::delete('classes/{schoolClass}', [SchoolClassController::class, 'destroy'])->name('classes.destroy');
    Route::get('school-fees', [SchoolFeeController::class, 'index'])->name('school-fees.index');
    Route::patch('school-fees/{student}', [SchoolFeeController::class, 'toggle'])->name('school-fees.toggle');
    Route::patch('school-fees/classes/{schoolClass}/registration/{student}', [SchoolFeeController::class, 'toggleRegistrationFee'])->name('school-fees.registration.toggle');
    Route::patch('school-fees/classes/{schoolClass}/registration/{student}/details', [SchoolFeeController::class, 'updateRegistrationFeeDetails'])->name('school-fees.registration.details');
    Route::get('school-fees/classes/{schoolClass}/registration/{student}/receipt', [SchoolFeeController::class, 'registrationFeeReceipt'])->name('school-fees.registration.receipt');
    Route::get('school-fees/classes/{schoolClass}/registration/{student}/receipt-preview', [SchoolFeeController::class, 'registrationFeeReceiptPreview'])->name('school-fees.registration.receipt-preview');
    Route::get('expenses', [ExpenseController::class, 'index'])->name('expenses.index');
    Route::post('expenses', [ExpenseController::class, 'store'])->name('expenses.store');
    Route::get('admin/history', [HistoryController::class, 'index'])->name('admin.history.index');
    Route::delete('admin/history/logins', [HistoryController::class, 'destroyLogins'])->name('admin.history.logins.destroy');
    Route::get('admin/trash', [TrashController::class, 'index'])->name('admin.trash.index');
    Route::post('admin/trash/{deletedItem}/restore', [TrashController::class, 'restore'])->name('admin.trash.restore');
    Route::delete('admin/trash/{deletedItem}', [TrashController::class, 'destroy'])->name('admin.trash.destroy');
    Route::delete('admin/trash', [TrashController::class, 'destroyAll'])->name('admin.trash.destroy-all');
    Route::get('programs', [TeachingProgramController::class, 'index'])->name('programs.index');
    Route::get('programs/schedule', [TeachingProgramController::class, 'schedule'])->name('programs.schedule');
    Route::get('programs/schedule/pdf', [TeachingProgramController::class, 'schedulePdf'])->name('programs.schedule.pdf');
    Route::post('programs/schedule/slots', [TeachingProgramController::class, 'storeScheduleSlot'])->name('programs.schedule.slots.store');
    Route::put('programs/schedule/slots/{classSchedule}', [TeachingProgramController::class, 'updateScheduleSlot'])->name('programs.schedule.slots.update');
    Route::delete('programs/schedule/slots/{classSchedule}', [TeachingProgramController::class, 'destroyScheduleSlot'])->name('programs.schedule.slots.destroy');
    Route::get('programs/create', [TeachingProgramController::class, 'create'])->name('programs.create');
    Route::post('programs', [TeachingProgramController::class, 'store'])->name('programs.store');
    Route::get('programs/{program}/edit', [TeachingProgramController::class, 'edit'])->name('programs.edit');
    Route::put('programs/{program}', [TeachingProgramController::class, 'update'])->name('programs.update');
    Route::get('programs/{program}/follow', [TeachingProgramController::class, 'follow'])->name('programs.follow');
    Route::patch('programs/{program}/lessons/{lesson}', [TeachingProgramController::class, 'toggleLesson'])->name('programs.lessons.toggle');
    Route::delete('programs/{program}', [TeachingProgramController::class, 'destroy'])->name('programs.destroy');
    Route::get('admin/users/create', [UserController::class, 'create'])->name('admin.users.create');
    Route::post('admin/users', [UserController::class, 'store'])->name('admin.users.store');
    Route::get('admin/users/scan', [UserController::class, 'scanQr'])->name('admin.users.scan');
    Route::get('admin/users/{user}/photo', [UserController::class, 'photo'])->name('admin.users.photo');
    Route::get('admin/users/{user}/card', [UserController::class, 'card'])->name('admin.users.card');
    Route::get('admin/users/{user}', [UserController::class, 'show'])->name('admin.users.show');
    Route::get('admin/users/{user}/edit', [UserController::class, 'edit'])->name('admin.users.edit');
    Route::put('admin/users/{user}', [UserController::class, 'update'])->name('admin.users.update');
    Route::patch('admin/users/{user}/toggle', [UserController::class, 'toggle'])->name('admin.users.toggle');
    Route::patch('admin/users/{user}/end-contract', [UserController::class, 'endContract'])->name('admin.users.end-contract');
    Route::patch('admin/users/{user}/reset-password', [UserController::class, 'resetPassword'])->name('admin.users.reset-password');
    Route::patch('admin/users/{user}/salary', [UserController::class, 'toggleSalary'])->name('admin.users.salary.toggle');
    Route::get('admin/users/{user}/salary/{payment}/receipt', [UserController::class, 'salaryReceipt'])->name('admin.users.salary.receipt');
    Route::get('admin/users/{user}/salary/{payment}/receipt-preview', [UserController::class, 'salaryReceiptPreview'])->name('admin.users.salary.receipt-preview');
    Route::delete('admin/users/{user}', [UserController::class, 'destroy'])->name('admin.users.destroy');
    Route::get('students/{student}/card', [StudentController::class, 'card'])->name('students.card');
    Route::get('students/{student}/photo', [StudentController::class, 'photo'])->name('students.photo');
    Route::get('students/{student}/enrollment-receipt', [StudentController::class, 'enrollmentReceipt'])->name('students.enrollment-receipt');
    Route::get('students/{student}/enrollment-receipt-preview', [StudentController::class, 'enrollmentReceiptPreview'])->name('students.enrollment-receipt-preview');
    Route::patch('students/{student}/monthly-fees', [StudentController::class, 'toggleMonthlyFee'])->name('students.monthly-fees.toggle');
    Route::get('students/{student}/monthly-fees/{fee}/receipt-preview', [StudentController::class, 'monthlyFeeReceiptPreview'])->name('students.monthly-fees.receipt-preview');
    Route::get('students/{student}/monthly-fees/{fee}/receipt', [StudentController::class, 'monthlyFeeReceipt'])->name('students.monthly-fees.receipt');
    Route::delete('students/{student}/permanent', [StudentController::class, 'destroyPermanently'])->name('students.destroy-permanently');
    Route::get('students/export-list', [StudentController::class, 'exportList'])->name('students.export-list');
    Route::get('students/scan', [StudentController::class, 'scanQr'])->name('students.scan');
    Route::resource('students', StudentController::class);
});

Route::get('/dashboard', function () {
    $attendanceTotal = Attendance::count();
    $attendancePresent = Attendance::whereIn('status', ['present', 'late'])->count();
    $paidMonthlyFees = StudentMonthlyFee::whereNotNull('paid_at')->sum('amount');
    $paidFeeMonths = StudentMonthlyFee::whereNotNull('paid_at')
        ->get(['student_id', 'fee_month'])
        ->mapWithKeys(fn (StudentMonthlyFee $fee) => [$fee->student_id.'-'.$fee->fee_month->format('Y-m') => true]);
    $lastPossibleMonth = now()->startOfMonth();
    $overdueMonthlyAmount = 0.0;

    Enrollment::with(['student:id,monthly_fee_amount,status', 'schoolClass.academicYear'])
        ->where('status', 'active')
        ->whereHas('student', fn ($query) => $query->where('status', 'active')->where('monthly_fee_amount', '>', 0))
        ->get()
        ->each(function (Enrollment $enrollment) use (&$overdueMonthlyAmount, $paidFeeMonths, $lastPossibleMonth): void {
            $class = $enrollment->schoolClass;
            if (!$class) {
                return;
            }

            $start = Carbon::parse($class->training_starts_on ?? $class->academicYear?->starts_on ?? $enrollment->enrolled_on)->startOfMonth();
            $trainingEnd = $start->copy()->addMonths(max(1, (int) ($class->duration_months ?: 12)))->subMonth()->startOfMonth();
            $end = $trainingEnd->lessThan($lastPossibleMonth) ? $trainingEnd : $lastPossibleMonth;
            $dueDay = (int) ($class->monthly_fee_due_day ?: 5);

            for ($month = $start->copy(); $month->lessThanOrEqualTo($end); $month->addMonth()) {
                $dueAt = $month->copy()->day(min($dueDay, $month->daysInMonth))->endOfDay();
                if ($dueAt->isPast() && !isset($paidFeeMonths[$enrollment->student_id.'-'.$month->format('Y-m')])) {
                    $overdueMonthlyAmount += (float) $enrollment->student->monthly_fee_amount;
                }
            }
        });

    $totalExpenses = 0;
    if (auth()->user()?->can('expenses.view')) {
        app(ExpenseService::class)->synchronizePaidSalaryExpenses();
        $totalExpenses = Expense::sum('amount');
    }

    return view('dashboard', [
        'studentCount' => Student::count(),
        'teacherCount' => User::role('Prof')->count(),
        'staffCount' => User::role(['Secrétaire', 'Trésorier'])->count(),
        'overdueInvoices' => Invoice::where('status', 'overdue')->sum(DB::raw('amount_due - amount_paid')) + app(SchoolFeeStatusService::class)->overdueSummary()[0],
        'collectedAmount' => Invoice::sum('amount_paid') + $paidMonthlyFees,
        'totalExpenses' => $totalExpenses,
        'attendanceRate' => $attendanceTotal ? round(($attendancePresent / $attendanceTotal) * 100, 1) : 0,
    ]);
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
