<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\Department;
use App\Models\LeaveCard;
use App\Models\User;
use App\Models\AuditTrail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MasterlistSyncController extends Controller
{
    /**
     * Pull all employees from the Employee Masterlist System (201)
     * and upsert them into the DepEd Leave Card System.
     */
    public function pullFromMasterlist(Request $request)
    {
        set_time_limit(3600);

        // Prevent UserObserver from triggering outbound syncs during import
        config(['syncing_from_external' => true]);

        $apiUrl = rtrim(env('EMPLOYEE_SYSTEM_API_URL', 'http://localhost:8002/api'), '/') . '/masterlist/export';
        $apiKey = env('API_SYNC_KEY', 'deped-sync-key-2024');

        Log::info("[MasterlistSync] Pulling from: {$apiUrl}");

        try {
            $response = Http::withHeaders([
                'X-API-KEY' => $apiKey,
                'Accept'    => 'application/json',
            ])->timeout(120)->get($apiUrl);

            if ($response->failed()) {
                Log::error("[MasterlistSync] API call failed: " . $response->body());
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to connect to Employee Masterlist System: ' . $response->status(),
                ], 500);
            }

            $body = $response->json();
            $employees = $body['employees'] ?? [];
            $total = count($employees);

            if ($total === 0) {
                return response()->json([
                    'success' => true,
                    'message' => 'No employees found in the masterlist.',
                    'synced' => 0,
                    'failed' => 0,
                ]);
            }

            Log::info("[MasterlistSync] Received {$total} employees. Starting upsert...");

            $synced = 0;
            $failed = 0;
            $errors = [];

            foreach ($employees as $data) {
                try {
                    $this->upsertEmployee($data);
                    $synced++;
                } catch (\Exception $e) {
                    $failed++;
                    $empId = $data['emp_id'] ?? 'unknown';
                    $errors[] = "{$empId}: {$e->getMessage()}";
                    Log::error("[MasterlistSync] Failed for {$empId}: {$e->getMessage()}");
                }
            }

            AuditTrail::log(
                'SYNC',
                'Employee Management',
                "Pulled {$synced} employees from Masterlist System. ({$failed} failed)"
            );

            Log::info("[MasterlistSync] Completed. Synced: {$synced}, Failed: {$failed}");

            return response()->json([
                'success' => true,
                'message' => "Sync completed. {$synced} synced, {$failed} failed.",
                'synced' => $synced,
                'failed' => $failed,
                'errors' => array_slice($errors, 0, 10), // Return first 10 errors
            ]);

        } catch (\Exception $e) {
            Log::error("[MasterlistSync] Exception: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Sync error: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Upsert a single employee from masterlist data into the DepEd system.
     */
    protected function upsertEmployee(array $data): void
    {
        $empId = $data['emp_id'] ?? null;
        if (!$empId) {
            throw new \Exception('Missing emp_id');
        }

        // Build formatted name: Lastname, Firstname M.I. Suffix
        $rawLastName  = trim($data['last_name'] ?? '');
        $rawFirstName = trim($data['first_name'] ?? '');
        $rawMiddle    = trim($data['middle_name'] ?? '');
        $rawSuffix    = trim($data['suffix'] ?? '');

        // Fallback: parse from 'name' field if parts are empty
        if (empty($rawLastName) && empty($rawFirstName) && !empty($data['name'])) {
            $rawName = trim($data['name']);
            if (str_contains($rawName, ',')) {
                $parts = explode(',', $rawName, 2);
                $rawLastName = trim($parts[0]);
                $rest = trim($parts[1] ?? '');
                $bits = preg_split('/\s+/', $rest);
                $suffixes = ['JR.', 'SR.', 'III', 'IV', 'V', 'II', 'JR', 'SR'];
                $firstBits = [];
                foreach ($bits as $i => $bit) {
                    $upper = strtoupper(trim($bit, '. '));
                    if (in_array($upper, $suffixes)) {
                        $rawSuffix = trim($bit);
                    } elseif (strlen(trim($bit, '.')) === 1 && $i > 0) {
                        $rawMiddle = trim($bit);
                    } else {
                        $firstBits[] = $bit;
                    }
                }
                $rawFirstName = implode(' ', $firstBits);
            } else {
                $parts = preg_split('/\s+/', $rawName);
                if (count($parts) >= 2) {
                    $rawLastName = array_pop($parts);
                    $rawFirstName = implode(' ', $parts);
                } else {
                    $rawFirstName = $rawName;
                }
            }
        }

        $middleInitial = !empty($rawMiddle) ? strtoupper(mb_substr($rawMiddle, 0, 1)) . '.' : '';

        $formattedName = $rawLastName;
        if (!empty($rawFirstName)) {
            $formattedName .= ', ' . $rawFirstName;
        }
        if (!empty($middleInitial)) {
            $formattedName .= ' ' . $middleInitial;
        }
        if (!empty($rawSuffix)) {
            $formattedName .= ' ' . $rawSuffix;
        }
        if (empty($rawLastName) && empty($rawFirstName)) {
            $formattedName = $data['name'] ?? $data['full_name'] ?? '';
        }

        // Map Employment Status
        $incomingStatus = $data['employment_status'] ?? '';
        $empStatus = null;
        if (stripos($incomingStatus, 'Permanent') !== false || stripos($incomingStatus, 'Regular') !== false) {
            $empStatus = 'Regular';
        } elseif (stripos($incomingStatus, 'Contract') !== false || stripos($incomingStatus, 'COS') !== false) {
            $empStatus = 'Contractual';
        }

        // Handle department
        $departmentId = null;
        $agency = $data['agency'] ?? $data['department'] ?? $data['school'] ?? null;
        if (!empty($agency)) {
            $dept = Department::firstOrCreate(
                ['name' => $agency],
                ['is_active' => true]
            );
            $departmentId = $dept->id;
        }

        DB::transaction(function () use ($data, $empId, $formattedName, $rawFirstName, $rawLastName, $rawMiddle, $rawSuffix, $empStatus, $departmentId) {
            // 1. Handle User account
            $email = $data['email'] ?? null;

            // Validate email is not malformed before processing
            $user = null;
            if ($email && filter_var($email, FILTER_VALIDATE_EMAIL) && !str_starts_with($email, '.')) {
                $emailHash = User::generateEmailHash($email);
                $user = User::where('email_searchable', $emailHash)->first();

                if (!$user) {
                    $cleanLastName = preg_replace('/[^A-Za-z]/', '', $rawLastName);
                    $prefix = ucfirst(strtolower(substr($cleanLastName, 0, 2)));
                    $fallbackPassword = "#" . $prefix . "d3P3d";

                    $user = User::create([
                        'first_name'       => $rawFirstName,
                        'last_name'        => $rawLastName,
                        'middle_name'      => $rawMiddle,
                        'suffix'           => $rawSuffix,
                        'email'            => $email,
                        'email_searchable' => User::generateEmailHash($email),
                        'password'         => Hash::make($fallbackPassword),
                        'role'             => 'employee',
                        'is_active'        => true,
                    ]);
                } else {
                    $user->update([
                        'first_name'  => $rawFirstName,
                        'last_name'   => $rawLastName,
                        'middle_name' => $rawMiddle,
                        'suffix'      => $rawSuffix,
                    ]);
                }
            }

            // 2. Handle Employee record
            $employeeData = [
                'full_name'         => $formattedName,
                'gender'            => $data['gender'] ?? null,
                'position'          => $data['position'] ?? null,
                'category'          => $data['category'] ?? 'National',
                'employment_status' => $empStatus,
                'address'           => $data['address'] ?? null,
                'email'             => $email,
                'contact_number'    => $data['contact_number'] ?? null,
                'date_hired'        => $data['date_hired'] ?? now()->format('Y-m-d'),
                'status'            => $data['status'] ?? 'Active',
                'department_id'     => $departmentId,
                'profile_picture'   => null,
            ];

            if ($user) {
                $employeeData['user_id'] = $user->id;
            }

            $employee = Employee::updateOrCreate(
                ['employee_id' => $empId],
                $employeeData
            );

            // 3. Ensure Leave Card exists for current year
            $currentYear = now()->year;
            LeaveCard::firstOrCreate(
                ['employee_id' => $employee->id, 'year' => $currentYear],
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
