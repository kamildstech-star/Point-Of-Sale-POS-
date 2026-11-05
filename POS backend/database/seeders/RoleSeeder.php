<?php

namespace Database\Seeders;

use App\Models\Roles;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permssion = json_encode([1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20]);
        $user      = json_encode([1,2,3,4,5]);
            DB::table('roles')->insert([
                [
                    'id' => 1,
                    'role' => 'Super Admin',
                    'permission' => $permssion,
                ],
                [
                    'id' => 2,
                    'role' => 'Admin',
                    'permission' => $user,
                ]
        ]);
    }
}
