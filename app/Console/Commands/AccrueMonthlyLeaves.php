<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class AccrueMonthlyLeaves extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:accrue-monthly-leaves';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Automatically credits 1.25 VL and SL to all permanent active employees for the previous month.';

    /**
     * Execute the console command.
     */
    public function handle(\App\Services\LeaveCardService $service)
    {
        // Add credits for the *previous completed month* (e.g. running on Feb 1 credits January)
        $targetDate = now()->subMonth();

        $this->info("Accruing leaves for completed month: " . $targetDate->format('m/Y'));

        $count = $service->addMonthlyCredits($targetDate->year, $targetDate->month);

        $this->info("Successfully credited {$count} permanent active employees.");
    }
}
