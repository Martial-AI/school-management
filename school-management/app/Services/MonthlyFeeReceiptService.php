<?php

namespace App\Services;

use App\Models\StudentMonthlyFee;
use Barryvdh\DomPDF\Facade\Pdf;

class MonthlyFeeReceiptService
{
    public function make(StudentMonthlyFee $fee): \Barryvdh\DomPDF\PDF
    {
        $fee->loadMissing('student.enrollments.schoolClass', 'paidBy');

        // 80 mm wide thermal paper; height is intentionally generous for the ticket content.
        return Pdf::loadView('students.monthly-fee-receipt', ['fee' => $fee])
            ->setPaper([0, 0, 226.77, 510.24]);
    }

    public function number(): string
    {
        do {
            $number = 'REC-'.now()->format('Ymd').'-'.strtoupper(substr(bin2hex(random_bytes(4)), 0, 6));
        } while (StudentMonthlyFee::where('receipt_number', $number)->exists());

        return $number;
    }
}
