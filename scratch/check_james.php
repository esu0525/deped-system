<?php

use App\Models\User;

// Bootstrap Laravel
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$user = User::where('last_name', 'Pisngot')->where('first_name', 'James')->first();
if ($user) {
    echo "James ID in deped-system: {$user->id}\n";
    echo "James Assignment in deped-system: " . ($user->assign ?? 'null') . "\n";
} else {
    echo "James not found in deped-system.\n";
}
