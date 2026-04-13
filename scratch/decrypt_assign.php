<?php

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Crypt;

// Manual Decryption Script for 'assign' column in deped-system

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "Starting decryption of 'assign' column...\n";

$users = DB::table('users')->get();
$count = 0;

foreach ($users as $user) {
    if (empty($user->assign)) continue;

    try {
        // Attempt to decrypt
        $decrypted = Crypt::decryptString($user->assign);
        
        // Update directly in DB to bypass model casts (model might still have the cast)
        DB::table('users')->where('id', $user->id)->update([
            'assign' => $decrypted
        ]);
        
        echo "Decrypted ID {$user->id}: {$decrypted}\n";
        $count++;
    } catch (\Exception $e) {
        // If it fails, it might already be decrypted or invalid
        echo "Failed or already decrypted for ID {$user->id}\n";
    }
}

echo "Finished. Decrypted {$count} records.\n";
