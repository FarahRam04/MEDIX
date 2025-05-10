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
                'name' => 'Dr. Omar',
                'email' => 'omar@clinic.com',
                'role' => 'doctor',
                'password' => Hash::make('password'),
            ],
            [
                'name' => 'Dr. Lina',
                'email' => 'lina@clinic.com',
                'role' => 'doctor',
                'password' => Hash::make('password'),
            ],
            [
                'name' => 'Aya Reception',
                'email' => 'aya@clinic.com',
                'role' => 'receptionist',
                'password' => Hash::make('password'),
            ],
            [
                'name' => 'Rami Reception',
                'email' => 'rami@clinic.com',
                'role' => 'receptionist',
                'password' => Hash::make('password'),
            ],
        ];

        foreach ($employees as $emp) {
            $employee = Employee::create([
                'name' => $emp['name'],
                'email' => $emp['email'],
                'password' =>$emp['password'],
                'role' => $emp['role']
            ]);
            $employee->assignRole($emp['role']);
        }


    }
}
