<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$ops = App\Models\User::all();
foreach ($ops as $op) {
    if (stripos($op->name, 'Hamimah') !== false) {
        echo "Found Hamimah: \n";
        echo "ID: " . $op->id . "\n";
        echo "Tipe: " . $op->tipe . "\n";
        echo "Role: " . $op->role . "\n";
        echo "Session_locked: " . ($op->session_locked ? 'true' : 'false') . "\n";
        $sess = cache()->get("session_singkong_{$op->id}");
        echo "Cache: ";
        var_dump($sess);
    }
}
