<?php

namespace Database\Seeders;

use App\Models\Admin;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admins=[
            [
                'name'=>'admin',
                'email'=>'hebaabbas991@gmail.com',
                'password'=>Hash::make('password'),
                'role'=>'admin'
            ],
        ];

        foreach ($admins as $admin) {
            $admin=Admin::create([
                'name'=>$admin['name'],
                'email'=>$admin['email'],
                'password'=>$admin['password'],
                'role'=>$admin['role']
            ]);
           $admin->assignRole($admin['role']);
        }
    }
}
