<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void { Schema::table('school_classes', fn (Blueprint $table) => $table->decimal('monthly_fee_amount', 12, 2)->nullable()->after('monthly_fee_due_day')); }
    public function down(): void { Schema::table('school_classes', fn (Blueprint $table) => $table->dropColumn('monthly_fee_amount')); }
};
