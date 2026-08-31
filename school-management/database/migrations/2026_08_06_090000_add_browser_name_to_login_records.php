<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('login_histories', fn (Blueprint $table) => $table->string('device_browser')->nullable()->after('device_platform'));
        Schema::table('login_approvals', fn (Blueprint $table) => $table->string('requester_device_browser')->nullable()->after('requester_device_platform'));
    }

    public function down(): void
    {
        Schema::table('login_histories', fn (Blueprint $table) => $table->dropColumn('device_browser'));
        Schema::table('login_approvals', fn (Blueprint $table) => $table->dropColumn('requester_device_browser'));
    }
};
