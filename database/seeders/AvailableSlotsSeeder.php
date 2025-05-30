<?php

namespace Database\Seeders;

use App\Models\AvailableSlot;
use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AvailableSlotsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $slots = [];

        // شيفت صباحي من 9:00 إلى 13:00
        $start = Carbon::createFromTime(9, 0);
        $end = Carbon::createFromTime(13, 0);
        while ($start < $end) {
            $slots[] = ['start_time' => $start->format('H:i:s')];
            $start->addMinutes(30);
        }

        // شيفت مسائي من 14:00 إلى 18:00
        $start = Carbon::createFromTime(14, 0);
        $end = Carbon::createFromTime(18, 0);
        while ($start < $end) {
            $slots[] = ['start_time' => $start->format('H:i:s')];
            $start->addMinutes(30);
        }

        AvailableSlot::insert($slots);
    }
}
