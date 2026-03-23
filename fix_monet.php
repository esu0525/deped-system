<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\LeaveTransaction;

// Find Monetization transactions with missing total in sl_wop
$transactions = LeaveTransaction::where('period', 'LIKE', '%Monetization%')
    ->where(function($q) {
        $q->whereNull('sl_wop')->orWhere('sl_wop', 0);
    })->get();

foreach ($transactions as $t) {
    if (isset($t->vl_used) && isset($t->sl_used)) {
        $total = $t->vl_used + $t->sl_used;
        $t->update(['sl_wop' => $total]);
        echo "Fixed transaction {$t->id} for {$t->period} - Set sl_wop back to {$total}\n";
    }
}
