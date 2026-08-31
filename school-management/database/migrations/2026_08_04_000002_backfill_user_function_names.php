<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $assignments = DB::table('model_has_roles')
            ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
            ->where('model_has_roles.model_type', 'App\\Models\\User')
            ->select('model_has_roles.model_id', 'roles.name')
            ->get();

        foreach ($assignments as $assignment) {
            $user = DB::table('users')->where('id', $assignment->model_id)->first();
            if ($user) {
                DB::table('users')->where('id', $user->id)->update([
                    'nickname' => $assignment->name === 'Admin' ? 'Admin' : $assignment->name.' '.$user->name,
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        // Les fonctions sont conservées lors d'un retour de migration.
    }
};
