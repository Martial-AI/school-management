<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void { Schema::table('users', function (Blueprint $table): void { $table->json('activity_notifications_read_ids')->nullable(); }); }
    public function down(): void { Schema::table('users', fn (Blueprint $table) => $table->dropColumn('activity_notifications_read_ids')); }
};
