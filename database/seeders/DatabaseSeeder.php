<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create permissions
        $permissions = [
            'create posts',
            'edit posts',
            'delete posts',
            'publish posts',
            'view users',
            
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Create roles and assign permissions
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $editorRole = Role::firstOrCreate(['name' => 'editor']);
        $userRole = Role::firstOrCreate(['name' => 'user']);
        $customerRole = Role::firstOrCreate(['name' => 'customer']);

        // Assign permissions to roles
        $adminRole->givePermissionTo($permissions);
        $editorRole->givePermissionTo(['create posts', 'edit posts', 'publish posts']);
        $userRole->givePermissionTo(['view users']);
        $customerRole->givePermissionTo(['view users']);

        // Assign a role to a user (Example: Assign 'admin' role to user with ID 1)
        $user = \App\Models\User::find(1);
        if ($user) {
            $user->assignRole('admin');
        }

        $this->command->info('Roles and permissions seeded successfully.');
        // run php artisan db:seed

    }
}
