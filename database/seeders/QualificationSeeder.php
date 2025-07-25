<?php

namespace Database\Seeders;

use App\Models\Doctor;
use App\Models\Qualification;
use Database\Factories\QualificationFactory;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class QualificationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $doctors = Doctor::all();
        foreach ($doctors as $doctor) {
            Qualification::factory()->count(rand(2,4))->for($doctor)->create();
        }
    }
}
