<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DaySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $days = [
            'sunday',
            'monday',
            'tuesday',
            'wednesday',
            'thursday',
            'friday',
            'saturday',
        ];

        foreach ($days as $index => $day) {
            DB::table('days')->insert([
                'id' => $index, // ← نحدد id يدويًا: 0 للأحد، 1 للاثنين...
                'day_name' => $day,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
