<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('school_classes')->orderBy('id')->get()->each(function (object $class): void {
            $amount = DB::table('enrollments')
                ->join('students', 'students.id', '=', 'enrollments.student_id')
                ->where('enrollments.school_class_id', $class->id)
                ->where('enrollments.status', 'active')
                ->max('students.monthly_fee_amount');
            if ((float) $amount > 0) DB::table('school_classes')->where('id', $class->id)->update(['monthly_fee_amount' => $amount]);
        });
    }

    public function down(): void {}
};
