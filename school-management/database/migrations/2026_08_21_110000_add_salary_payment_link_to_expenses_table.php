<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('expenses', function (Blueprint $table): void {
            $table->dateTime('spent_at')->nullable()->after('spent_on')->index();
            $table->foreignId('employee_payment_id')->nullable()->after('recorded_by')
                ->constrained('employee_payments')->nullOnDelete();
            $table->unique('employee_payment_id');
        });
    }

    public function down(): void
    {
        Schema::table('expenses', function (Blueprint $table): void {
            $table->dropForeign(['employee_payment_id']);
            $table->dropUnique(['employee_payment_id']);
            $table->dropIndex(['spent_at']);
            $table->dropColumn(['employee_payment_id', 'spent_at']);
        });
    }
};
