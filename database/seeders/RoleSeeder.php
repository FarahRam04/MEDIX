<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'admin']);
        Role::firstOrCreate(['name' => 'doctor', 'guard_name' => 'employee']);
        Role::firstOrCreate(['name' => 'receptionist', 'guard_name' => 'employee']);
    }
}
