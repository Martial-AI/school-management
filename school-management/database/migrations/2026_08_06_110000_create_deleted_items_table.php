<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('deleted_items', function (Blueprint $table): void {
            $table->id();
            $table->string('type', 80)->index();
            $table->string('label')->index();
            $table->json('payload');
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('deleted_at')->index();
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('deleted_items'); }
};
