<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('school_classes', function (Blueprint $table): void {
            $table->date('training_starts_on')->nullable()->after('academic_year_id');
            $table->date('training_ends_on')->nullable()->after('training_starts_on');
        });

        $currentYear = now()->year;
        foreach (range($currentYear, $currentYear + 5) as $year) {
            $id = DB::table('academic_years')->where('name', (string) $year)->value('id');
            if (!$id) {
                $id = DB::table('academic_years')->insertGetId(['name' => (string) $year, 'starts_on' => "$year-01-01", 'ends_on' => "$year-12-31", 'is_current' => $year === $currentYear, 'created_at' => now(), 'updated_at' => now()]);
            }
        }

        DB::table('school_classes')->orderBy('id')->each(function (object $class): void {
            $oldStart = DB::table('academic_years')->where('id', $class->academic_year_id)->value('starts_on');
            $start = Carbon::parse($oldStart ?: now())->startOfDay();
            $end = $start->copy()->addMonths((int) ($class->duration_months ?: 12))->subDay();
            $newYearId = DB::table('academic_years')->where('name', (string) $start->year)->value('id');
            DB::table('school_classes')->where('id', $class->id)->update(['academic_year_id' => $newYearId, 'training_starts_on' => $start->toDateString(), 'training_ends_on' => $end->toDateString(), 'updated_at' => now()]);
        });
    }

    public function down(): void
    {
        Schema::table('school_classes', function (Blueprint $table): void {
            $table->dropColumn(['training_starts_on', 'training_ends_on']);
        });
    }
};
