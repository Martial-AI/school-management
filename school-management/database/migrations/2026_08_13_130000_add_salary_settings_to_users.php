<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void { Schema::table('users', function (Blueprint $table): void { $table->decimal('monthly_salary_amount',12,2)->nullable()->after('contract_end_date'); $table->unsignedTinyInteger('salary_payment_day')->nullable()->after('monthly_salary_amount'); }); }
    public function down(): void { Schema::table('users', fn (Blueprint $table) => $table->dropColumn(['monthly_salary_amount','salary_payment_day'])); }
};
