<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SettingsUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $status;
    public $kode_produksi;
    public $nama_produk;
    public $operator;
    public $berat;

    /**
     * Create a new event instance.
     */
    public function __construct($data)
    {
        $this->status = $data['status'] ?? 'idle';
        $this->kode_produksi = $data['kode_produksi'] ?? '-';
        $this->nama_produk = $data['nama_produk'] ?? '-';
        $this->operator = $data['operator'] ?? '-';
        $this->berat = $data['berat'] ?? '-';
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('iot-channel'),
        ];
    }
}
