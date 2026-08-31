<?php

namespace App\Services;

use App\Models\EmployeePayment;
use App\Models\Expense;
use App\Models\User;

class ExpenseService
{
    public function synchronizePaidSalaryExpenses(): void
    {
        EmployeePayment::query()
            ->with('user')
            ->where('status', 'paid')
            ->whereNotNull('paid_at')
            ->orderBy('id')
            ->chunkById(100, function ($payments): void {
                $payments->each(fn (EmployeePayment $payment) => $this->syncSalaryPayment($payment));
            });
    }

    public function syncSalaryPayment(EmployeePayment $payment): ?Expense
    {
        if (! $payment->paid_at || $payment->status !== 'paid') {
            $this->removeSalaryPayment($payment);

            return null;
        }

        $payment->loadMissing('user');
        $recordedBy = $payment->recorded_by ?: User::role('Admin')->value('id') ?: User::query()->value('id');

        return Expense::updateOrCreate(
            ['employee_payment_id' => $payment->id],
            [
                'reference' => 'EXP-SAL-'.$payment->id,
                'category' => 'salary',
                'reason' => 'Salary payment — '.($payment->user?->name ?? '—'),
                'amount' => $payment->amount,
                'spent_on' => $payment->paid_at->toDateString(),
                'spent_at' => $payment->paid_at,
                'recorded_by' => $recordedBy,
            ],
        );
    }

    public function removeSalaryPayment(EmployeePayment $payment): void
    {
        Expense::where('employee_payment_id', $payment->id)->delete();
    }
}
