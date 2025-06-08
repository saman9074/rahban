<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Trip;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class PermanentlyDeleteOldTrips extends Command
{
    protected $signature = 'app:permanently-delete-old-trips';
    protected $description = 'Permanently delete trips that are marked for deletion (e.g., older than 7 days)';

    public function handle()
    {
        $this->info('Starting permanent deletion process for old trips...');

        // پیدا کردن سفرهایی که زمان حذف نهایی آنها فرا رسیده است
        $tripsToDelete = Trip::where('deletable_at', '<', Carbon::now())->get();

        if ($tripsToDelete->isEmpty()) {
            $this->info('No old trips found to delete.');
            return;
        }

        $count = $tripsToDelete->count();
        $this->info("Found {$count} old trips. Deleting them permanently...");

        foreach ($tripsToDelete as $trip) {
            $trip->delete(); // این دستور سفر و تمام روابط آن (نگهبانان، موقعیت‌ها) را حذف می‌کند
            Log::info("Trip with ID {$trip->id} was permanently deleted from the database.");
        }

        $this->info("Cleanup process finished. {$count} trips were permanently deleted.");
    }
}