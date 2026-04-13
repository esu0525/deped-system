<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\SyncService;
use Illuminate\Console\Command;

class SyncAllUsers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:users';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync all local users to the external system';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $users = User::all();
        $this->info("Found " . $users->count() . " users to sync.");

        $bar = $this->output->createProgressBar($users->count());

        foreach ($users as $user) {
            SyncService::syncUser($user, 'updated');
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info('Synchronization complete!');
    }
}
