<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $userRole = DB::table('roles')->where('RoleCode', 'user')->first();
        $duplicateRole = DB::table('roles')->where('RoleCode', 'prediction_user')->first();

        if (!$userRole) {
            return;
        }

        DB::table('roles')
            ->where('id', $userRole->id)
            ->update(['RoleName' => 'Prediction User', 'updated_at' => now()]);

        if (!$duplicateRole) {
            return;
        }

        DB::table('users')
            ->where('role_id', $duplicateRole->id)
            ->update(['role_id' => $userRole->id]);

        DB::table('user_roles')
            ->where('role_id', $duplicateRole->id)
            ->delete();

        DB::table('roles')->where('id', $duplicateRole->id)->delete();
    }

    public function down(): void
    {
        DB::table('roles')
            ->where('RoleCode', 'user')
            ->update(['RoleName' => 'User', 'updated_at' => now()]);

        if (!DB::table('roles')->where('RoleCode', 'prediction_user')->exists()) {
            DB::table('roles')->insert([
                'RoleCode' => 'prediction_user',
                'RoleName' => 'Prediction User',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
};
