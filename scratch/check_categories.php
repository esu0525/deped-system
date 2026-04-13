<?php

use App\Models\Employee;

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$categories = Employee::distinct()->pluck('category')->toArray();
echo "Employee categories: " . implode(', ', $categories) . "\n";
