<?php

namespace App\Console\Commands;

use App\Models\Vacation;
use Carbon\Carbon;
use Illuminate\Console\Command;

class UpdateExpiredVacations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:update-expired-vacations';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description ='Updates the status of active vacations to "expired" if their end date has passed';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Checking for expired vacations to update...');
        $updatedCount = Vacation::where('status', 'active')
            ->where('end_day', '<', Carbon::today())
            ->get();
        if($updatedCount->isEmpty()){
            $this->info('No expired vacations found to update.');
            return 0;
        }
        $count = $updatedCount->count();
        $this->info("Found {$count} vacations to update status.");
foreach ($updatedCount as $vacation){
    $vacation->update(['status'=>'expired']);
}
        $this->info("Successfully updated {$count} vacation.");
        return 0;
    }
}
