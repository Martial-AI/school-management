<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('professional_number', 20)->nullable()->unique()->after('nickname');
            $table->string('teaching_subjects', 500)->nullable()->after('professional_number');
        });

        $counters = [];
        DB::table('users')->orderBy('id')->get(['id', 'created_at'])->each(function (object $user) use (&$counters): void {
            $year = $user->created_at ? Carbon::parse($user->created_at)->format('y') : Carbon::now()->format('y');
            $counters[$year] = ($counters[$year] ?? 0) + 1;
            DB::table('users')->where('id', $user->id)->update(['professional_number' => 'PA'.$year.'-'.str_pad((string) $counters[$year], 3, '0', STR_PAD_LEFT)]);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropUnique(['professional_number']);
            $table->dropColumn(['professional_number', 'teaching_subjects']);
        });
    }
};
