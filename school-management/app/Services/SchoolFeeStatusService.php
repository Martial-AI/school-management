<?php

namespace App\Services;

use App\Models\Enrollment;
use App\Models\StudentMonthlyFee;
use Illuminate\Support\Carbon;

class SchoolFeeStatusService
{
    /** @return array{0: float, 1: array<int, int>, 2: array<int, array<int, string>>} */
    public function overdueSummary(): array
    {
        $paid = StudentMonthlyFee::whereNotNull('paid_at')->get(['student_id', 'fee_month'])
            ->mapWithKeys(fn (StudentMonthlyFee $fee) => [$fee->student_id.'-'.$fee->fee_month->format('Y-m') => true]);
        $total = 0.0;
        $studentIds = [];
        $overdueMonths = [];

        Enrollment::with(['student:id,monthly_fee_amount,status', 'schoolClass.academicYear'])
            ->where('status', 'active')
            ->get()
            ->each(function (Enrollment $enrollment) use (&$total, &$studentIds, &$overdueMonths, $paid): void {
                $student = $enrollment->student;
                $class = $enrollment->schoolClass;
                $studentStatus = $student?->status instanceof \BackedEnum ? $student->status->value : $student?->status;
                if (!$student || !$class || $studentStatus !== 'active' || (float) $student->monthly_fee_amount <= 0) return;
                $start = Carbon::parse($class->training_starts_on ?? $class->academicYear?->starts_on ?? $enrollment->enrolled_on)->startOfMonth();
                $end = $start->copy()->addMonths(max(1, (int) ($class->duration_months ?: 12)))->subMonth()->startOfMonth();
                $dueDay = (int) ($class->monthly_fee_due_day ?: 5);

                for ($month = $start->copy(); $month->lessThanOrEqualTo($end) && $month->lessThanOrEqualTo(now()->startOfMonth()); $month->addMonth()) {
                    $dueAt = $month->copy()->day(min($dueDay, $month->daysInMonth))->endOfDay();
                    if ($dueAt->isPast() && !isset($paid[$student->id.'-'.$month->format('Y-m')])) {
                        $total += (float) $student->monthly_fee_amount;
                        $studentIds[$student->id] = true;
                        $overdueMonths[$student->id][] = $month->format('Y-m');
                    }
                }
            });

        return [$total, array_map('intval', array_keys($studentIds)), $overdueMonths];
    }
}
