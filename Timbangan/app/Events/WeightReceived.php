<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Broadcast hasil timbangan ke dashboard real-time.
 *
 * Sengaja memakai ShouldBroadcast (ter-QUEUE), BUKAN ShouldBroadcastNow, supaya
 * broadcast tidak dieksekusi sinkron di dalam request/ingest — request cepat
 * selesai dan Reverb dipukul oleh queue worker, bukan oleh setiap hit HTTP.
 * (Butuh queue worker berjalan: service docker `queue` / `php artisan queue:work`.)
 */
class WeightReceived implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $module;
    public $weight;
    public $operator;
    public $product;
    public $kode_produksi;
    public $status;
    public $ip_address;

    // Field tambahan untuk alur HMI (Bahan Baku / Formulasi).
    public $menu;
    public $nama_item;
    public $kode_batch;
    public $timbangan_ke;
    public $target;

    /**
     * @param  string  $module
     * @param  array   $data
     */
    public function __construct($module, $data)
    {
        $this->module        = $module;
        $this->weight        = $data['weight'] ?? 0;
        $this->operator      = $data['operator'] ?? 'Unknown';
        $this->product       = $data['product'] ?? 'Unknown';
        $this->kode_produksi = $data['kode_produksi'] ?? '-';
        $this->status        = $data['status'] ?? 'selesai';
        $this->ip_address    = $data['ip_address'] ?? 'Unknown IP';

        // Alias HMI (fallback ke field lama agar dashboard lama tetap jalan).
        $this->menu         = $data['menu'] ?? $module;
        $this->nama_item    = $data['nama_item'] ?? ($data['product'] ?? null);
        $this->kode_batch   = $data['kode_batch'] ?? ($data['kode_produksi'] ?? null);
        $this->timbangan_ke = $data['timbangan_ke'] ?? null;
        $this->target       = $data['target'] ?? null;
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('iot-weights'),
            new Channel('iot-weights.' . $this->module),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'WeightReceived';
    }
}
