<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('program_lessons', fn (Blueprint $table) => $table->foreignId('parent_id')->nullable()->after('teaching_program_id')->constrained('program_lessons')->nullOnDelete());
    }
    public function down(): void
    {
        Schema::table('program_lessons', fn (Blueprint $table) => $table->dropConstrainedForeignId('parent_id'));
    }
};
