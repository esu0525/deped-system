<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

try {
    $user = User::updateOrCreate(
    ['email' => 'acelucinicio@gmail.com'],
    [
        'name' => 'Ace Dev Admin',
        'username' => 'acedev',
        'password' => Hash::make('Ace Dev'),
        'role' => 'super_admin'
    ]
    );

    // Ensure super_admin role exists
    if (!Role::where('name', 'super_admin')->exists()) {
        Role::create(['name' => 'super_admin']);
    }

    $user->assignRole('super_admin');
    echo "User [acelucinicio@gmail.com] created and assigned to [super_admin] role.";
}
catch (\Exception $e) {
    echo "Error: " . $e->getMessage();
}
