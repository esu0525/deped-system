<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Department;
use App\Models\LeaveType;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Create Departments
        $depts = [
            ['name' => 'Office of the Schools Division Superintendent', 'code' => 'OSDS'],
            ['name' => 'Curriculum Implementation Division', 'code' => 'CID'],
            ['name' => 'School Governance and Operations Division', 'code' => 'SGOD'],
            ['name' => 'Administrative Section', 'code' => 'ADMIN'],
            ['name' => 'Finance Section', 'code' => 'FINANCE'],
        ];

        foreach ($depts as $dept) {
            Department::updateOrCreate(['code' => $dept['code']], $dept);
        }

        // 2. Create Leave Types
        $types = [
            ['name' => 'Vacation Leave', 'code' => 'VL', 'is_earnable' => true, 'monthly_credit' => 1.25],
            ['name' => 'Sick Leave', 'code' => 'SL', 'is_earnable' => true, 'monthly_credit' => 1.25],
            ['name' => 'Forced Leave', 'code' => 'FL', 'is_earnable' => false, 'monthly_credit' => 0],
            ['name' => 'Special Privilege Leave', 'code' => 'SPL', 'is_earnable' => false, 'monthly_credit' => 0],
        ];

        foreach ($types as $type) {
            LeaveType::updateOrCreate(['code' => $type['code']], $type);
        }

        // 3. Create Default Users
        User::updateOrCreate(
        ['email' => 'admin@deped.gov.ph'],
        [
            'name' => 'System Admin',
            'username' => 'admin',
            'password' => Hash::make('password123'),
            'role' => 'super_admin',
            'email_verified_at' => now(),
        ]
        );

        User::updateOrCreate(
        ['email' => 'hr@deped.gov.ph'],
        [
            'name' => 'HR Administrator',
            'username' => 'hr_admin',
            'password' => Hash::make('password123'),
            'role' => 'hr_admin',
            'email_verified_at' => now(),
        ]
        );

        User::updateOrCreate(
        ['email' => 'encoder@deped.gov.ph'],
        [
            'name' => 'Data Encoder',
            'username' => 'encoder',
            'password' => Hash::make('password123'),
            'role' => 'encoder',
            'email_verified_at' => now(),
        ]
        );
    }
}
