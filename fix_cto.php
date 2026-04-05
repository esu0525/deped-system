<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\LeaveTransaction;

foreach(LeaveTransaction::where('period', 'LIKE', '%CTO:%')->get() as $t) {
    if (preg_match('/CTO:\s*(.*)/i', $t->period, $matches)) {
        $title = trim($matches[1]);
        // Remove trailing "X days"
        $title = preg_replace('/\s\d+(\.\d+)?\sday(s)?$/i', '', $title);
        $t->update(['cto_title' => $title]);
        echo "Updated ID {$t->id} with title: $title\n";
    }
}
echo "Done.\n";
