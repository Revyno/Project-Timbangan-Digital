<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class WeightReceived implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $module;
    public $weight;
    public $operator;
    public $product;
    public $kode_produksi;
    public $status;
    public $ip_address;

    /**
     * Create a new event instance.
     */
    public function __construct($module, $data)
    {
        $this->module = $module;
        $this->weight = $data['weight'] ?? 0;
        $this->operator = $data['operator'] ?? 'Unknown';
        $this->product = $data['product'] ?? 'Unknown';
        $this->kode_produksi = $data['kode_produksi'] ?? '-';
        $this->status = $data['status'] ?? 'selesai';
        $this->ip_address = $data['ip_address'] ?? 'Unknown IP';
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
