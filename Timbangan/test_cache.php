<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$ops = App\Models\User::where('tipe', 'incoming_singkong')->where('role', 'operator')->where('session_locked', false)->get();
echo "Active operators: " . count($ops) . PHP_EOL;

foreach ($ops as $op) {
    echo "Operator: " . $op->name . " (ID: " . $op->id . ")" . PHP_EOL;
    $sess = cache()->get("session_singkong_{$op->id}");
    var_dump($sess);
}
