<?php

namespace Database\Seeders;

use App\Models\Employee;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class EmployeeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $employees = [
            [
                'first_name' => 'Omar',
                'last_name' => 'Hernandez',
                'email' => 'omar@clinic.com',
                'role' => 'doctor',
                'password' => Hash::make('password'),
                'salary' => 5000,
            ],
            [
                'first_name' => 'Lina',
                'last_name' => 'Hernandez',
                'email' => 'lina@clinic.com',
                'role' => 'doctor',
                'password' => Hash::make('password'),
                'salary' => 5000,
            ],
            [
                'first_name' => 'Aya ',
                'last_name' => 'Hernandez',
                'email' => 'aya@clinic.com',
                'role' => 'receptionist',
                'password' => Hash::make('password'),
                'salary' => 5000,
            ],
            [
                'first_name' => 'Rami Reception',
                'last_name' => 'Hernandez',
                'email' => 'rami@clinic.com',
                'role' => 'receptionist',
                'password' => Hash::make('password'),
                'salary' => 5000,
            ],
        ];

        foreach ($employees as $emp) {
            $employee = Employee::create([
                'first_name' => $emp['first_name'],
                'last_name' => $emp['last_name'],
                'email' => $emp['email'],
                'password' =>$emp['password'],
                'role' => $emp['role'],
                'salary' => $emp['salary'],
            ]);
            $employee->assignRole($emp['role']);
        }


    }
}
