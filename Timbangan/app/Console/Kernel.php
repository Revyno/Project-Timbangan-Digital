<?php

namespace App\Console;

use App\Jobs\ForwardWeighingsBatch;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected function schedule(Schedule $schedule): void
    {
        // Server edge: forward baris pending ke server online tiap menit.
        // Jaring pengaman bila online sempat down saat PRINT terjadi; job juga
        // di-dispatch langsung saat PRINT. WithoutOverlapping mencegah tumpang tindih.
        if (config('hmi.role') === 'local') {
            $schedule->job(new ForwardWeighingsBatch)
                ->everyMinute()
                ->withoutOverlapping();
        }
    }

    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');
        require base_path('routes/console.php');
    }
}
