<?php

namespace Database\Seeders;

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
        DB::table('departments')->insert([
            ['name' => json_encode(['en'=>'General'])],
            ['name' => json_encode(['en'=>'Cardiology'])],
            ['name' => json_encode(['en'=>'Dermatology'])],
            ['name' => json_encode(['en'=>'Gastroenterology'])],
            ['name' => json_encode(['en'=>'Neurology'])],
            ['name' => json_encode(['en'=>'Pediatric'])],
        ]);
    }
}
