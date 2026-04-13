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

        // 2. Create Leave Types (CSC Official)
        $types = [
            ['name' => 'Vacation Leave', 'code' => 'VL', 'is_earnable' => true, 'monthly_credit' => 1.25, 'description' => 'Sec. 51, Rule XVI, Omnibus Rules Implementing E.O. No. 292'],
            ['name' => 'Mandatory/Forced Leave', 'code' => 'FL', 'is_earnable' => false, 'monthly_credit' => 0, 'description' => 'Sec. 25, Rule XVI, Omnibus Rules Implementing E.O. No. 292'],
            ['name' => 'Sick Leave', 'code' => 'SL', 'is_earnable' => true, 'monthly_credit' => 1.25, 'description' => 'Sec. 43, Rule XVI, Omnibus Rules Implementing E.O. No. 292'],
            ['name' => 'Maternity Leave', 'code' => 'ML', 'is_earnable' => false, 'monthly_credit' => 0, 'description' => 'R.A. No. 11210 / IRR issued by CSC, DOLE, and SSS'],
            ['name' => 'Paternity Leave', 'code' => 'PL', 'is_earnable' => false, 'monthly_credit' => 0, 'description' => 'R.A. No. 8187 / CSC MC No. 71, s. 1998, as amended'],
            ['name' => 'Special Privilege Leave', 'code' => 'SPL', 'is_earnable' => false, 'monthly_credit' => 0, 'description' => 'Sec. 21, Rule XVI, Omnibus Rules Implementing E.O. No. 292'],
            ['name' => 'Solo Parent Leave', 'code' => 'SoP', 'is_earnable' => false, 'monthly_credit' => 0, 'description' => 'R.A. No. 8972 / CSC MC No. 15, s. 2005'],
            ['name' => 'Study Leave', 'code' => 'STL', 'is_earnable' => false, 'monthly_credit' => 0, 'description' => 'Sec. 68, Rule XVI, Omnibus Rules Implementing E.O. No. 292'],
            ['name' => '10-Day VAWC Leave', 'code' => 'VAWC', 'is_earnable' => false, 'monthly_credit' => 0, 'description' => 'R.A. No. 9262 / CSC MC No. 15, s. 2005'],
            ['name' => 'Rehabilitation Privilege', 'code' => 'RL', 'is_earnable' => false, 'monthly_credit' => 0, 'description' => 'Sec. 55, Rule XVI, Omnibus Rules Implementing E.O. No. 292'],
            ['name' => 'Special Leave Benefits for Women', 'code' => 'SLW', 'is_earnable' => false, 'monthly_credit' => 0, 'description' => 'R.A. No. 9710 / CSC MC No. 25, s. 2010'],
            ['name' => 'Special Emergency (Calamity) Leave', 'code' => 'SEC', 'is_earnable' => false, 'monthly_credit' => 0, 'description' => 'CSC MC No. 2, s. 2012, as amended'],
            ['name' => 'Adoption Leave', 'code' => 'AL', 'is_earnable' => false, 'monthly_credit' => 0, 'description' => 'R.A. No. 8552'],
            ['name' => 'Others', 'code' => 'OTH', 'is_earnable' => false, 'monthly_credit' => 0, 'description' => 'Other types of leave'],
        ];

        foreach ($types as $type) {
            LeaveType::updateOrCreate(['code' => $type['code']], $type);
        }

        // 3. Create Default Users
        $users = [
            [
                'first_name' => 'System',
                'last_name' => 'Admin',
                'email' => 'admin@deped.gov.ph',
                'password' => Hash::make('password123'),
                'role' => 'super_admin',
                'email_verified_at' => now(),
            ],
            [
                'first_name' => 'HR',
                'last_name' => 'Administrator',
                'email' => 'hr@deped.gov.ph',
                'password' => Hash::make('password123'),
                'role' => 'hr_admin',
                'email_verified_at' => now(),
            ],
            [
                'first_name' => 'Data',
                'last_name' => 'Encoder',
                'email' => 'encoder@deped.gov.ph',
                'password' => Hash::make('password123'),
                'role' => 'encoder',
                'email_verified_at' => now(),
            ],
            [
                'first_name' => 'Ace',
                'last_name' => 'Lucinicio',
                'email' => 'acelucinicio@gmail.com',
                'password' => Hash::make('deped123'),
                'role' => 'super_admin',
                'email_verified_at' => now(),
            ],
        ];

        // Disable events during seeding to prevent sync errors on domestic/live servers
        User::withoutEvents(function () use ($users) {
            foreach ($users as $userData) {
                $userData['email_searchable'] = User::generateEmailHash($userData['email']);
                User::updateOrCreate(
                    ['email_searchable' => $userData['email_searchable']], 
                    $userData
                );
            }
        });
    }
}
