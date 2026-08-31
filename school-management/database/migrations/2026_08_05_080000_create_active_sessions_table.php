<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('active_sessions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('session_id', 120)->unique();
            $table->timestamp('last_seen_at')->index();
            $table->timestamp('ended_at')->nullable()->index();
            $table->timestamps();
            $table->index(['user_id', 'ended_at', 'last_seen_at']);
        });
    }
    public function down(): void { Schema::dropIfExists('active_sessions'); }
};
