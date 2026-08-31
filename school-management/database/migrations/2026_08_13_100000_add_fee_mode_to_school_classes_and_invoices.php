<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('school_classes', function (Blueprint $table): void {
            $table->string('fee_mode', 30)->default('monthly')->after('monthly_fee_due_day');
            $table->decimal('registration_fee_amount', 12, 2)->nullable()->after('fee_mode');
            $table->date('registration_fee_due_date')->nullable()->after('registration_fee_amount');
        });

        Schema::table('invoices', function (Blueprint $table): void {
            $table->foreignId('school_class_id')->nullable()->after('student_id')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('invoices', fn (Blueprint $table) => $table->dropConstrainedForeignId('school_class_id'));
        Schema::table('school_classes', fn (Blueprint $table) => $table->dropColumn(['fee_mode', 'registration_fee_amount', 'registration_fee_due_date']));
    }
};
