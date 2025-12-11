<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Permission;
use App\Models\Role;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Define all permissions
        $permissions = [
            [
                'name' => 'training_model',
                'description' => 'Can train machine learning models'
            ],
            [
                'name' => 'manage_dataset',
                'description' => 'Can upload, edit, delete datasets'
            ],
            [
                'name' => 'manage_users',
                'description' => 'Can manage user accounts'
            ],
            [
                'name' => 'manage_models',
                'description' => 'Can manage ML models'
            ],
            [
                'name' => 'view_predictions',
                'description' => 'Can view prediction results'
            ],
            [
                'name' => 'make_predictions',
                'description' => 'Can make predictions using models'
            ],
            [
                'name' => 'view_history',
                'description' => 'Can view prediction history'
            ],
            [
                'name' => 'manage_roles',
                'description' => 'Can manage roles and permissions'
            ],
        ];

        // Create permissions
        foreach ($permissions as $permission) {
            Permission::firstOrCreate(
                ['name' => $permission['name']],
                ['description' => $permission['description']]
            );
        }

        // Get roles
        $adminRole = Role::where('id', 1)->first(); // Admin
        $userRole = Role::where('id', 2)->first();  // User

        // Get all permissions
        $allPermissions = Permission::all();

        // Admin has all permissions
        if ($adminRole) {
            $adminRole->permissions()->sync($allPermissions->pluck('id'));
        }

        // User has limited permissions
        if ($userRole) {
            $userPermissions = Permission::whereIn('name', [
                'view_predictions',
                'make_predictions',
                'view_history'
            ])->pluck('id');
            
            $userRole->permissions()->sync($userPermissions);
        }
    }
}
