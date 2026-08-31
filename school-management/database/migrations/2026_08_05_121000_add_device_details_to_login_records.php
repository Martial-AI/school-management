<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('login_histories', function (Blueprint $table): void {
            $table->string('device_model')->nullable()->after('user_agent');
            $table->string('device_platform', 40)->nullable()->after('device_model');
        });
        Schema::table('login_approvals', function (Blueprint $table): void {
            $table->string('requester_device_model')->nullable()->after('requester_user_agent');
            $table->string('requester_device_platform', 40)->nullable()->after('requester_device_model');
        });
    }

    public function down(): void
    {
        Schema::table('login_histories', fn (Blueprint $table) => $table->dropColumn(['device_model', 'device_platform']));
        Schema::table('login_approvals', fn (Blueprint $table) => $table->dropColumn(['requester_device_model', 'requester_device_platform']));
    }
};
