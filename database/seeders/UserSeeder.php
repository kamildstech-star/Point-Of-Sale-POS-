<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('users')->insert([
            [
                'name' => 'Super Admin',
                'email' => 'SAdmin@bestuniform.com',
                'password' => Hash::make('SAdmin@123'),
                'role_id' => 1
            ],
            [
                'name' => 'Admin',
                'email' => 'Admin@bestuniform.com',
                'password' => Hash::make('admin123'),
                'role_id' => 2
            ]
        ]);
    }
}
