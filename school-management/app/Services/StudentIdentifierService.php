<?php

namespace App\Services;

use App\Models\Student;
use Illuminate\Support\Carbon;

class StudentIdentifierService
{
    public function next(): string
    {
        $year = Carbon::now()->format('y');
        $prefix = "ST{$year}-";
        $numbers = Student::query()->where('student_number', 'like', "{$prefix}%")->lockForUpdate()->pluck('student_number');
        $sequence = $numbers->map(fn (string $number): int => (int) substr($number, -4))->max() + 1;

        return $prefix.str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
    }
}
