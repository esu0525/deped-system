<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\User;

class AccountGeneratorService
{
    /**
     * Generate email and password from employee's full name.
     *
     * Name format: "Conanan, Reymhon E." or "Lastname, Firstname M." or just "Firstname"
     *
     * Email:    lastname.firstname@deped.gov.ph (lowercase)
     * Password: #[First2LettersOfSurname]d3P3d
     *
     * Example: "Conanan, Reymhon E."
     *   Email:    conanan.reymhon@deped.gov.ph
     *   Password: #Cod3P3d
     */
    public static function generateCredentials(string $fullName): array
    {
        $parsed = self::parseName($fullName);
        $surname = $parsed['surname'];
        $firstName = $parsed['first_name'];

        // Generate email: lastname.firstname@deped.gov.ph
        $baseEmail = strtolower(
            self::sanitizeForEmail($surname) . '.' . self::sanitizeForEmail($firstName)
        );
        $email = self::ensureUniqueEmail($baseEmail . '@deped.gov.ph');

        // Generate username (same as email prefix)
        $username = explode('@', $email)[0];

        // Generate password: #[First2LettersOfSurname]d3P3d
        $first2 = mb_substr(ucfirst(strtolower($surname)), 0, 2);
        $password = '#' . $first2 . 'd3P3d';

        return [
            'email' => $email,
            'username' => $username,
            'password' => $password,
            'name' => $fullName,
        ];
    }

    /**
     * Parse the full name into surname and first name.
     *
     * Handles formats like:
     *   "Conanan, Reymhon E."  → surname: Conanan, firstName: Reymhon
     *   "Joel Colobong"        → surname: Colobong, firstName: Joel
     *   "Tyangge"              → surname: Tyangge, firstName: Tyangge
     */
    public static function parseName(string $fullName): array
    {
        $fullName = trim($fullName);

        if (str_contains($fullName, ',')) {
            // Format: "Surname, Firstname M."
            $parts = array_map('trim', explode(',', $fullName, 2));
            $surname = $parts[0];

            // Get first name (remove middle initial like "E." or "M.")
            $firstNameParts = preg_split('/\s+/', trim($parts[1] ?? ''));
            $firstName = $firstNameParts[0] ?? $surname;
        }
        else {
            // Format: "Firstname Lastname" or single name
            $parts = preg_split('/\s+/', $fullName);
            if (count($parts) >= 2) {
                $surname = end($parts);
                $firstName = $parts[0];
            }
            else {
                $surname = $parts[0];
                $firstName = $parts[0];
            }
        }

        return [
            'surname' => $surname,
            'first_name' => $firstName,
        ];
    }

    /**
     * Sanitize a name part for use in an email address.
     * Removes special characters, accents, and spaces.
     */
    private static function sanitizeForEmail(string $name): string
    {
        // Remove periods, special characters, keep only letters and hyphens
        $clean = preg_replace('/[^a-zA-Z\-]/', '', $name);
        return strtolower($clean);
    }

    /**
     * Ensure the email is unique. If it already exists, append a number.
     * e.g., conanan.reymhon@deped.gov.ph → conanan.reymhon2@deped.gov.ph
     */
    private static function ensureUniqueEmail(string $email): string
    {
        if (!User::where('email', $email)->exists()) {
            return $email;
        }

        $parts = explode('@', $email);
        $base = $parts[0];
        $domain = $parts[1];
        $counter = 2;

        while (User::where('email', "{$base}{$counter}@{$domain}")->exists()) {
            $counter++;
        }

        return "{$base}{$counter}@{$domain}";
    }

    /**
     * Create a user account for an employee using auto-generated credentials.
     * Returns the User model and the raw password for display.
     */
    public static function createAccountForEmployee(Employee $employee): array
    {
        if ($employee->user_id && $employee->user) {
            return [
                'user' => $employee->user,
                'password' => null,
                'already_exists' => true,
            ];
        }

        $credentials = self::generateCredentials($employee->full_name);

        $user = User::create([
            'name' => $employee->full_name,
            'email' => $credentials['email'],
            'password' => bcrypt($credentials['password']),
            'role' => 'employee',
            'is_active' => true,
        ]);

        $employee->update(['user_id' => $user->id]);

        return [
            'user' => $user,
            'password' => $credentials['password'],
            'already_exists' => false,
        ];
    }
}
