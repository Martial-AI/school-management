<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration {
    public function up(): void
    {
        foreach (['activity.delete', 'login_history.delete'] as $name) Permission::findOrCreate($name, 'web');
        Role::findOrCreate('Admin', 'web')->givePermissionTo(['activity.delete', 'login_history.delete']);
    }
    public function down(): void {}
};
