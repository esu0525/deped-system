<?php

namespace App\Console\Commands;

use App\Models\Employee;
use Illuminate\Console\Command;

class ReformatEmployeeNames extends Command
{
    protected $signature   = 'employees:reformat-names
                                {--dry-run : Preview changes without saving}';
    protected $description = 'Reformat all employee full_name to: Lastname, Firstname M.I. Suffix';

    public function handle(): int
    {
        $dryRun    = $this->option('dry-run');
        $employees = Employee::with('user')->get();

        $this->info("Processing {$employees->count()} employees...");
        $this->newLine();

        $updated = 0;
        $skipped = 0;

        foreach ($employees as $employee) {
            $user = $employee->user;

            // Prefer individual parts from the linked user record
            if ($user && (!empty($user->last_name) || !empty($user->first_name))) {
                $lastName      = trim($user->last_name ?? '');
                $firstName     = trim($user->first_name ?? '');
                $middleName    = trim($user->middle_name ?? '');
                $suffix        = trim($user->suffix ?? '');
            } else {
                // No linked user — skip (nothing to reformat from)
                $this->warn("  [SKIP] #{$employee->employee_id} — {$employee->full_name} (no user record)");
                $skipped++;
                continue;
            }

            $middleInitial = !empty($middleName)
                ? strtoupper(mb_substr($middleName, 0, 1)) . '.'
                : '';

            $formatted = $lastName;
            if (!empty($firstName))     $formatted .= ', ' . $firstName;
            if (!empty($middleInitial)) $formatted .= ' ' . $middleInitial;
            if (!empty($suffix))        $formatted .= ' ' . $suffix;

            $old = $employee->full_name;

            if ($old === $formatted) {
                $skipped++;
                continue;
            }

            $this->line("  <fg=yellow>[{$employee->employee_id}]</> {$old}");
            $this->line("          → <fg=green>{$formatted}</>");

            if (!$dryRun) {
                $employee->updateQuietly(['full_name' => $formatted]);
            }

            $updated++;
        }

        $this->newLine();

        if ($dryRun) {
            $this->warn("DRY RUN — no changes were saved.");
        } else {
            $this->info("Done! Updated: {$updated} | Skipped/unchanged: {$skipped}");
        }

        return self::SUCCESS;
    }
}
