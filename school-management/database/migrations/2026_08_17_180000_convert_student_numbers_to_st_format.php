<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $counters = [];
        DB::table('students')->orderBy('id')->get(['id', 'created_at'])->each(function (object $student) use (&$counters): void {
            $year = $student->created_at ? Carbon::parse($student->created_at)->format('y') : Carbon::now()->format('y');
            $counters[$year] = ($counters[$year] ?? 0) + 1;
            DB::table('students')->where('id', $student->id)->update(['student_number' => 'ST'.$year.'-'.str_pad((string) $counters[$year], 4, '0', STR_PAD_LEFT)]);
        });
    }

    public function down(): void
    {
        // The original identifiers are not recoverable after conversion.
    }
};
