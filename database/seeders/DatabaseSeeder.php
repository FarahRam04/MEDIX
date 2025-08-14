<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
       $this->call([DepartmentsSeeder::class]);
        $this->call([RoleSeeder::class]);
       $this->call([AdminSeeder::class]);
        $this->call([DaySeeder::class]);
        $this->call([AvailableSlotsSeeder::class]);
        $this->call([UserSeeder::class]);
        $this->call([EmployeeSeeder::class]);
        $this->call([AppointmentSeeder::class]);
        $this->call([QualificationSeeder::class]);
        $this->call([OffersSeeder::class]);
        $this->call(VacationSeeder::class);
        $this->call(NotificationSeeder::class);
    }

}
