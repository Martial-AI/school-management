<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('login_approvals', function (Blueprint $table): void {
            $table->string('requester_ip_address', 45)->nullable()->after('requester_session_id');
            $table->text('requester_user_agent')->nullable()->after('requester_ip_address');
        });
    }

    public function down(): void
    {
        Schema::table('login_approvals', function (Blueprint $table): void {
            $table->dropColumn(['requester_ip_address', 'requester_user_agent']);
        });
    }
};
