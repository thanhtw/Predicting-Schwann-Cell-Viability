<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Role 1: Admin (unchanged)
        Role::firstOrCreate(
            ['RoleCode' => 'admin'],
            ['RoleName' => 'Administrator']
        );

        // Default prediction role (RoleCode remains "user" for compatibility)
        Role::updateOrCreate(
            ['RoleCode' => 'user'],
            ['RoleName' => 'Prediction User']
        );

        // Permission Group: Dataset Manager
        // Can: Manage Datasets
        Role::firstOrCreate(
            ['RoleCode' => 'dataset_manager'],
            ['RoleName' => 'Dataset Manager']
        );

        // Permission Group: Model Trainer
        // Can: Manage Datasets, Train Models, Manage Models
        Role::firstOrCreate(
            ['RoleCode' => 'model_trainer'],
            ['RoleName' => 'Model Trainer']
        );
    }
}
