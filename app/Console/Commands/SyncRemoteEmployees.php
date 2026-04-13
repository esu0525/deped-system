<?php

namespace App\Console\Commands;

use App\Models\Employee;
use App\Models\LeaveCard;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SyncRemoteEmployees extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:remote-employees';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch masterlist from remote API and sync to local database';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $url = env('REMOTE_API_URL');
        $token = env('REMOTE_BEARER_TOKEN');

        if (!$url || !$token) {
            $this->error('Please set REMOTE_API_URL and REMOTE_BEARER_TOKEN in your .env file.');
            return 1;
        }

        $this->info("Fetching data from: {$url}...");

        try {
            $response = Http::withToken($token)->get($url);

            if (!$response->successful()) {
                $this->error("Failed to fetch data. Error: " . $response->status());
                return 1;
            }

            $employees = $response->json();
            
            $this->info("Full API Response:");
            dump($employees);

            // Note: If their API returns data inside a key like 'data', change this:
            // $employees = $response->json()['data'];

            if (!is_array($employees)) {
                $this->error("Invalid response format. Expected array of employees.");
                return 1;
            }

            $count = count($employees);
            $this->info("Found {$count} employees. Starting sync...");

            $bar = $this->output->createProgressBar($count);
            $bar->start();

            foreach ($employees as $data) {
                try {
                    $this->info("Processing: " . ($data['name'] ?? $data['full_name'] ?? 'Unknown'));
                    $this->syncEmployee($data);
                    $bar->advance();
                } catch (\Exception $e) {
                    $this->error("\nError syncing employee: " . $e->getMessage());
                    $this->info("Data: " . json_encode($data));
                }
            }

            $bar->finish();
            $this->newLine();
            $this->info('Sync completed successfully!');

        } catch (\Exception $e) {
            $this->error("Error during sync: " . $e->getMessage());
            return 1;
        }

        return 0;
    }

    private function syncEmployee($data)
    {
        // Mapping fields from remote API based on their database structure
        $mappedData = [
            'employee_id'       => $data['id'] ?? $data['emp_id'] ?? $data['employee_id'] ?? null,
            'full_name'         => $data['name'] ?? $data['full_name'] ?? '',
            'gender'            => $data['sex'] ?? $data['gender'] ?? null,
            'position'          => $data['position'] ?? null,
            'category'          => $data['category'] ?? 'National',
            'employment_status' => $data['employment_status'] ?? $data['employment_type'] ?? 'Permanent',
            'address'           => $data['address'] ?? null,
            'email'             => $data['email'] ?? null,
            'contact_number'    => $data['phone'] ?? $data['contact_number'] ?? null,
            'profile_picture'   => $data['profile_picture'] ?? null,
            'date_hired'        => $data['date_joined'] ?? $data['date_hired'] ?? now()->format('Y-m-d'),
            'status'            => $data['status'] ?? 'Active',
        ];

        if (!$mappedData['employee_id']) {
            $this->warn("\nSkipping: No employee ID found for " . ($data['name'] ?? 'unknown'));
            return;
        }

        // Handle Profile Picture
        if ($mappedData['profile_picture'] && filter_var($mappedData['profile_picture'], FILTER_VALIDATE_URL)) {
            try {
                $imgResponse = Http::get($mappedData['profile_picture']);
                if ($imgResponse->successful()) {
                    $extension = pathinfo($mappedData['profile_picture'], PATHINFO_EXTENSION) ?: 'jpg';
                    $filename = 'avatars/' . $mappedData['employee_id'] . '_' . time() . '.' . $extension;
                    Storage::disk('public')->put($filename, $imgResponse->body());
                    $mappedData['profile_picture'] = $filename;
                }
            } catch (\Exception $e) {
                $mappedData['profile_picture'] = null;
            }
        }

        DB::transaction(function () use ($mappedData) {
            // 1. Handle User
            $emailHash = User::generateEmailHash($mappedData['email']);
            $user = User::where('email_searchable', $emailHash)->first();

            $nameParts = explode(' ', $mappedData['full_name']);
            $lastName = array_pop($nameParts);
            $firstName = implode(' ', $nameParts) ?: 'Employee';

            if (!$user) {
                $user = User::create([
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'email' => $mappedData['email'],
                    'email_searchable' => $emailHash,
                    'password' => Hash::make(Str::random(12)),
                    'role' => 'employee',
                    'is_active' => true,
                    'avatar' => $mappedData['profile_picture']
                ]);
            } else {
                $user->update([
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'avatar' => $mappedData['profile_picture'] ?? $user->avatar
                ]);
            }

            // 2. Handle Employee
            $employeeData = $mappedData;
            $employeeData['user_id'] = $user->id;

            $employee = Employee::updateOrCreate(
                ['employee_id' => $mappedData['employee_id']],
                $employeeData
            );

            // 3. Initialize Leave Card
            LeaveCard::firstOrCreate(
                ['employee_id' => $employee->id, 'year' => now()->year],
                [
                    'vl_beginning_balance' => 0,
                    'sl_beginning_balance' => 0,
                    'vl_earned' => 0,
                    'sl_earned' => 0,
                    'vl_used' => 0,
                    'sl_used' => 0,
                    'vl_balance' => 0,
                    'sl_balance' => 0,
                ]
            );
        });
    }
}
