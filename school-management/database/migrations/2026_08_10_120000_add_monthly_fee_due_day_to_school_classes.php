<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('school_classes', function (Blueprint $table): void {
            $table->unsignedTinyInteger('monthly_fee_due_day')->nullable()->after('duration_months');
        });
    }

    public function down(): void
    {
        Schema::table('school_classes', function (Blueprint $table): void {
            $table->dropColumn('monthly_fee_due_day');
        });
    }
};
