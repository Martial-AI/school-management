<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('salary_payment_method', 30)->nullable()->after('salary_payment_day');
            $table->string('salary_payment_phone', 50)->nullable()->after('salary_payment_method');
            $table->string('salary_payment_transaction_hint', 100)->nullable()->after('salary_payment_phone');
            $table->text('salary_payment_bank_details')->nullable()->after('salary_payment_transaction_hint');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['salary_payment_method', 'salary_payment_phone', 'salary_payment_transaction_hint', 'salary_payment_bank_details']);
        });
    }
};
