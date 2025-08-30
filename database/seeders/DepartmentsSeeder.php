<?php

namespace Database\Seeders;

use App\Models\Department;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DepartmentsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        collect([
            ['en'=>'Ophthalmology','ar'=>'طب العيون'],
            ['en'=>'Cardiology','ar'=>'أمراض القلب'],
            ['en'=>'Dermatology','ar'=>'الأمراض الجلدية'],
            ['en'=>'Gastroenterology','ar'=>'أمراض الجهاز الهضمي'],
            ['en'=>'Neurology','ar'=>'علم الأعصاب'],
            ['en'=>'Pediatric','ar'=>'طب الأطفال'],
        ])->each(fn($name) => Department::create(['name' => $name]));
    }
}
