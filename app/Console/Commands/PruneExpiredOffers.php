<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Offer;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;

class
PruneExpiredOffers extends Command
{
    protected $signature = 'offers:prune-expired';
    protected $description = 'Deletes offers that have passed their end date';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting to prune expired offers...');
        $expiredOffers = Offer::where('end_date', '<', Carbon::today())->get();
        if ($expiredOffers->isEmpty()) {
            $this->info('No expired offers to delete.');
            return 0;
        }

        $count = $expiredOffers->count();
        $this->info("Found {$count} expired offers to delete.");
        foreach ($expiredOffers as $offer) {
            if ($offer->image) {
                Storage::disk('public')->delete($offer->image);
            }
            $offer->delete();
        }

        $this->info("Successfully deleted {$count} expired offers.");
        return 0;
    }
}
