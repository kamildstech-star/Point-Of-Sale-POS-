<?php

namespace Database\Seeders;

use App\Models\Permissions;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = collect(
            [
                [ 'name' => 'user'],
                [ 'name' => 'product'],
                [ 'name' => 'size'],
                [ 'name' => 'school'],
                [ 'name' => 'stock'],
                [ 'name' => 'order'],
                [ 'name' => 'return'],
                [ 'name' => 'report'],
                [ 'name' => 'account'],
                [ 'name' => 'businessDealer'],
                [ 'name' => 'setting'],
                [ 'name' => 'role'],
                [ 'name' => 'dashboard'],
                [ 'name' => 'permission'],
                [ 'name' => 'ledger'],
                [ 'name' => 'daily account'],
                [ 'name' => 'expences'],
                [ 'name' => 'stock report'],
                [ 'name' => 'sale report'],
                [ 'name' => 'return report'],
            ]
        );
        $permissions->each(function($permission){
            Permissions::create($permission);
        });
    }
}
