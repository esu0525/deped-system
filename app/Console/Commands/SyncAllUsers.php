<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SyncAllUsers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'users:sync-all';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Manually sync all users to the Transmittal System';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $users = User::all();
        $total = $users->count();
        $this->info("Found {$total} users to sync...");

        $url = env('TRANSMITTAL_SYNC_URL');
        $token = env('TRANSMITTAL_SYNC_TOKEN');

        if (!$url || !$token) {
            $this->error("Sync URL or Token not found in .env");
            return 1;
        }

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $successCount = 0;
        $failCount = 0;

        foreach ($users as $user) {
            try {
                $response = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $token,
                    'Accept' => 'application/json',
                    'X-SYNC-SOURCE' => 'DEPED-SYSTEM-BULK'
                ])->post($url, [
                    'id' => $user->id,
                    'lastname' => $user->last_name,
                    'middle_name' => $user->middle_name,
                    'first_name' => $user->first_name,
                    'suffix' => $user->suffix,
                    'email' => $user->email,
                    'email_hash' => $user->email_searchable,
                    'profile_image' => $user->avatar ? asset('storage/' . $user->avatar) : null,
                    'password' => $user->password,
                    'role' => $user->role,
                    'assigned' => $user->assign,
                    'is_active' => $user->is_active,
                    'email_verified_at' => $user->email_verified_at,
                ]);

                if ($response->successful()) {
                    $successCount++;
                } else {
                    $failCount++;
                    Log::error("Bulk Sync Fail for {$user->email}: " . $response->body());
                }
            } catch (\Exception $e) {
                $failCount++;
                Log::error("Bulk Sync Exception for {$user->email}: " . $e->getMessage());
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("Sync completed!");
        $this->info("Success: {$successCount}");
        if ($failCount > 0) {
            $this->warn("Failed: {$failCount} (Check logs for details)");
        }

        return 0;
    }
}
