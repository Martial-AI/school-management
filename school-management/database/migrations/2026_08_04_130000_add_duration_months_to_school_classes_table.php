<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('school_classes', function (Blueprint $table): void {
            $table->unsignedSmallInteger('duration_months')->nullable()->after('level');
        });
    }

    public function down(): void
    {
        Schema::table('school_classes', function (Blueprint $table): void {
            $table->dropColumn('duration_months');
        });
    }
};
