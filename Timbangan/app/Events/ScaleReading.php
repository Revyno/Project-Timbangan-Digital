<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Berat "live" dari timbangan ke HMI — EPHEMERAL.
 *
 * TIDAK disimpan ke DB dan TIDAK di-forward ke server online. Hanya di-broadcast
 * ke channel LAN-only `scale.{device}` supaya angka di HMI bergerak. Memakai
 * ShouldBroadcastNow (langsung, tanpa antre DB) demi latensi rendah; banjir
 * dicegah oleh throttle per-device di controller (config hmi.live.throttle_ms).
 */
class ScaleReading implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $device;
    public $menu;
    public $weight;
    public $unit;

    public function __construct(string $device, string $menu, float $weight, string $unit = 'kg')
    {
        $this->device = $device;
        $this->menu   = $menu;
        $this->weight = $weight;
        $this->unit   = $unit;
    }

    public function broadcastOn(): array
    {
        return [new Channel('scale.' . $this->device)];
    }

    public function broadcastAs(): string
    {
        return 'ScaleReading';
    }
}
