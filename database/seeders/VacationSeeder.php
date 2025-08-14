<?php

namespace Database\Seeders;

use App\Models\Vacation;
use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;


class VacationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $today = Carbon::today();

        Vacation::insert([
            [
                'employee_id' => 1,
                'start_day'   => $today->copy()->subDays(2)->toDateString(),
                'end_day'     => $today->copy()->addDays(3)->toDateString(),
                'days'        => 5,
                'paid'        => true,
                'deduction'   => 0,
                'reason'      => 'إجازة سنوية',
                'status'      => 'active',
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
            [
                'employee_id' => 2,
                'start_day'   => $today->copy()->subDays(10)->toDateString(),
                'end_day'     => $today->copy()->subDays(5)->toDateString(),
                'days'        => 5,
                'paid'        => false,
                'deduction'   => 150.00,
                'reason'      => 'إجازة مرضية',
                'status'      => 'expired',
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
            [
                'employee_id' => 3,
                'start_day'   => $today->copy()->addDays(1)->toDateString(),
                'end_day'     => $today->copy()->addDays(4)->toDateString(),
                'days'        => 3,
                'paid'        => true,
                'deduction'   => 0,
                'reason'      => 'إجازة عائلية',
                'status'      => 'active',
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
        ]);
    }

}
