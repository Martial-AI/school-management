<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('first_name')->nullable()->after('name');
            $table->string('last_name')->nullable()->after('first_name');
            $table->string('cin', 50)->nullable()->unique()->after('phone');
            $table->date('birth_date')->nullable()->after('cin');
            $table->string('birth_place')->nullable()->after('birth_date');
            $table->text('address')->nullable()->after('birth_place');
            $table->string('emergency_contact')->nullable()->after('address');
            $table->string('contract_type', 50)->nullable()->after('emergency_contact');
            $table->date('contract_start_date')->nullable()->after('contract_type');
            $table->date('contract_end_date')->nullable()->after('contract_start_date');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn(['first_name', 'last_name', 'cin', 'birth_date', 'birth_place', 'address', 'emergency_contact', 'contract_type', 'contract_start_date', 'contract_end_date']);
        });
    }
};
