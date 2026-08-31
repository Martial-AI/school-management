<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $adminIds = DB::table('model_has_roles')
            ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
            ->where('model_has_roles.model_type', 'App\\Models\\User')
            ->where('roles.name', 'Admin')
            ->pluck('model_has_roles.model_id');

        DB::table('users')->whereIn('id', $adminIds)->update(['nickname' => 'Admin', 'updated_at' => now()]);
    }

    public function down(): void
    {
    }
};
