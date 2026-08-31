<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = [
            'dashboard.view',
            'students.view', 'students.create', 'students.update', 'students.delete',
            'classes.manage', 'subjects.manage',
            'attendance.view', 'attendance.manage',
            'payments.view', 'payments.manage', 'payments.remind',
            'expenses.view', 'expenses.create', 'expenses.update', 'expenses.delete',
            'programs.view', 'programs.manage', 'students.follow',
            'activity.view', 'login_history.view',
            'settings.manage', 'roles.manage', 'accounts.reset_password',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $roles = [
            'Admin' => $permissions,
            'Prof' => ['dashboard.view', 'students.view', 'students.follow', 'attendance.view', 'attendance.manage', 'programs.view', 'programs.manage'],
            'Secrétaire' => ['dashboard.view', 'students.view', 'students.create', 'students.update', 'classes.manage'],
            'Trésorier' => ['dashboard.view', 'students.view', 'payments.view', 'payments.manage', 'payments.remind', 'expenses.view', 'expenses.create', 'expenses.update'],
        ];

        foreach ($roles as $name => $rolePermissions) {
            Role::findOrCreate($name, 'web')->syncPermissions($rolePermissions);
        }

        $admin = User::updateOrCreate(
            ['email' => 'admin@gestion-scolaire.local'],
            ['name' => 'Administrateur', 'password' => Hash::make('ChangeMe123!')],
        );

        $admin->syncRoles(['Admin']);
    }
}
