<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\LeaveCard;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class EmployeeSyncController extends Controller
{
    /**
     * Endpoint for syncing a single employee.
     * Expects fields: emp_id, name, gender, position, category, type_of_employment, address, email, contact_number, profile_picture
     */
    public function sync(Request $request)
    {
        // Prevent UserObserver from triggering outbound syncs back to Transmittal System during import
        config(['syncing_from_external' => true]);

        // 1. Basic API Key Security
        $apiKey = $request->header('X-API-KEY');
        if ($apiKey !== env('API_SYNC_KEY')) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $validated = $request->validate([
            'data' => 'required|array',
            'action' => 'required|string|in:upsert,delete',
        ]);

        $data = $validated['data'];
        $action = $validated['action'];

        // Build full_name in DepEd format: Lastname, Firstname M.I. Suffix
        $rawLastName  = trim($data['last_name'] ?? '');
        $rawFirstName = trim($data['first_name'] ?? '');
        $rawMiddle    = trim($data['middle_name'] ?? '');
        $rawSuffix    = trim($data['suffix'] ?? '');

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
        // Fallback: if individual parts were not provided, use the raw name field
        if (empty($rawLastName) && empty($rawFirstName)) {
            $formattedName = $data['name'] ?? $data['full_name'] ?? '';
        }

        // Map Employment Status to DepEd new values
        $incomingStatus = $data['employment_status'] ?? $data['type_of_employment'] ?? '';
        $empStatus = null; // Default to none as requested
        if (stripos($incomingStatus, 'Permanent') !== false || stripos($incomingStatus, 'Regular') !== false) {
            $empStatus = 'Regular';
        } elseif (stripos($incomingStatus, 'Contract') !== false || stripos($incomingStatus, 'COS') !== false) {
            $empStatus = 'Contractual';
        }

        // Map inputs to model fields
        $mappedData = [
            'employee_id' => $data['emp_id'] ?? $data['employee_id'] ?? null,
            'full_name' => $formattedName,
            'first_name' => $rawFirstName,
            'last_name' => $rawLastName,
            'middle_name' => $rawMiddle,
            'suffix' => $rawSuffix,
            'gender' => $data['gender'] ?? null,
            'position' => $data['position'] ?? null,
            'category' => $data['category'] ?? 'National',
            'employment_status' => $empStatus,
            'address' => $data['address'] ?? null,
            'email' => $data['email'] ?? null,
            'contact_number' => $data['contact_number'] ?? null,
            'agency' => ($data['agency'] ?: ($data['department'] ?: ($data['school'] ?? null))),
            'profile_picture' => $data['profile_picture'] ?? null,
            'date_hired' => $data['date_hired'] ?? now()->format('Y-m-d'),
            'status' => $data['status'] ?? 'Active',
        ];

        if (!$mappedData['employee_id']) {
            return response()->json(['message' => 'Employee ID (emp_id) is required'], 422);
        }

        // Handle Department/Agency linking
        $departmentId = null;
        if (!empty($mappedData['agency'])) {
            $dept = \App\Models\Department::firstOrCreate(
                ['name' => $mappedData['agency']],
                ['is_active' => true]
            );
            $departmentId = $dept->id;
        }
        // ----------------------------------

        if (!$mappedData['employee_id']) {
            return response()->json(['message' => 'Employee ID (emp_id) is required'], 422);
        }

        if ($action === 'delete') {
            $employee = Employee::where('employee_id', $mappedData['employee_id'])->first();
            if ($employee) {
                if ($employee->user) {
                    $employee->user->delete();
                }
                $employee->delete();
                return response()->json(['message' => 'Employee deleted successfully']);
            }
            return response()->json(['message' => 'Employee not found'], 404);
        }

        // Handle Profile Picture (Disabled as requested to speed up sync)
        $mappedData['profile_picture'] = null;

        // Upsert logic
        return DB::transaction(function () use ($mappedData, $departmentId) {

            // 1. Handle User
            $email = $mappedData['email'];
            $emailHash = hash_hmac('sha256', strtolower($email), config('app.key'));
            $user = User::where('email_searchable', $emailHash)->first();

            $firstName = $mappedData['first_name'];
            $lastName = $mappedData['last_name'];
            $middleName = $mappedData['middle_name'];

            if (!$user) {
                // Generate fallback password: # + First 2 letters of Lastname + d3P3d
                // e.g., Abadilla -> #Abd3P3d
                $cleanLastName = preg_replace('/[^A-Za-z]/', '', $lastName);
                $prefix = ucfirst(strtolower(substr($cleanLastName, 0, 2)));
                $fallbackPassword = "#" . $prefix . "d3P3d";

                $user = User::create([
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'middle_name' => $middleName,
                    'suffix' => $mappedData['suffix'],
                    'email' => $email,
                    'email_searchable' => $emailHash,
                    'password' => Hash::make($fallbackPassword),
                    'role' => 'employee',
                    'is_active' => true,
                ]);
            } else {
                $user->update([
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'middle_name' => $middleName,
                    'suffix' => $mappedData['suffix'],
                ]);
            }


            // 2. Handle Employee
            $employeeData = $mappedData;
            $employeeData['user_id'] = $user->id;
            $employeeData['department_id'] = $departmentId;

            $employee = Employee::updateOrCreate(
                ['employee_id' => $mappedData['employee_id']],
                $employeeData
            );


            // 3. Initialize Leave Ledger (LeaveCard) if new
            $currentYear = now()->year;
            $leaveCard = LeaveCard::firstOrCreate(
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

            return response()->json([
                'message' => 'Employee synced successfully',
                'employee_id' => $employee->employee_id,
                'leave_card_initialized' => $leaveCard->wasRecentlyCreated
            ]);
        });
    }

    /**
     * Endpoint for bulk syncing employees.
     */
    public function syncBulk(Request $request)
    {
        set_time_limit(3600); // Allow up to 1 hour for bulk processing
        // Prevent UserObserver from triggering outbound syncs back to Transmittal System during import
        config(['syncing_from_external' => true]);

        // 1. Basic API Key Security
        $apiKey = $request->header('X-API-KEY');
        if ($apiKey !== env('API_SYNC_KEY')) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $validated = $request->validate([
            'employees' => 'required|array',
            'action' => 'sometimes|string|in:upsert,delete',
        ]);

        $action = $request->input('action', 'upsert');
        $results = [];

        foreach ($validated['employees'] as $employeeData) {
            // Mock request for each employee to reuse sync logic
            $mockRequest = new Request();
            $mockRequest->headers->set('X-API-KEY', $apiKey);
            $mockRequest->replace(['data' => $employeeData, 'action' => $action]);

            try {
                $response = $this->sync($mockRequest);
                $results[] = [
                    'emp_id' => $employeeData['emp_id'] ?? $employeeData['employee_id'] ?? 'unknown',
                    'status' => 'success',
                    'data' => $response->getData()
                ];
            } catch (\Exception $e) {
                $results[] = [
                    'emp_id' => $employeeData['emp_id'] ?? $employeeData['employee_id'] ?? 'unknown',
                    'status' => 'error',
                    'message' => $e->getMessage()
                ];
            }
        }

        return response()->json([
            'message' => 'Bulk sync completed',
            'results' => $results
        ]);
    }
}

