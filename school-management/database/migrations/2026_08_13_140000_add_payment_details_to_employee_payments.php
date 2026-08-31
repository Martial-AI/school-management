<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void { Schema::table('employee_payments', function (Blueprint $table): void { $table->string('payment_method',30)->nullable()->after('status'); $table->string('payment_phone',50)->nullable()->after('payment_method'); $table->string('transaction_id',100)->nullable()->after('payment_phone'); $table->text('bank_details')->nullable()->after('transaction_id'); }); }
    public function down(): void { Schema::table('employee_payments', fn (Blueprint $table) => $table->dropColumn(['payment_method','payment_phone','transaction_id','bank_details'])); }
};
