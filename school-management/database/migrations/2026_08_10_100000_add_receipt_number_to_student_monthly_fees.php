<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('student_monthly_fees', function (Blueprint $table): void {
            $table->string('receipt_number', 40)->nullable()->unique()->after('amount');
        });
    }
    public function down(): void
    {
        Schema::table('student_monthly_fees', fn (Blueprint $table) => $table->dropUnique(['receipt_number']));
        Schema::table('student_monthly_fees', fn (Blueprint $table) => $table->dropColumn('receipt_number'));
    }
};
