<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            'org.view', 'org.create', 'org.update', 'org.delete',
            'user.view', 'user.manage',
            'category.view', 'category.create', 'category.update', 'category.delete',
            'question.view', 'question.create', 'question.update', 'question.delete',
            'exam.view', 'exam.create', 'exam.update', 'exam.delete', 'exam.publish',
            'member.view', 'member.manage',
            'attempt.take', 'attempt.view_own', 'attempt.view_all',
            'settings.org',
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
        }

        $admin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $admin->syncPermissions(Permission::all());

        Role::firstOrCreate(['name' => 'org_admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'editor', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'viewer', 'guard_name' => 'web']);
    }
}
