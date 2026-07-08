<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class HmiWeighing extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'menu',
        'site',
        'device_id',
        'user_id',
        'operator_name',
        'karu_name',
        'shift',
        'tanggal',
        'produk',
        'nama_item',
        'kode_batch',
        'target',
        'berat',
        'unit',
        'selisih',
        'timbangan_ke',
        'sync_status',
        'synced_at',
        'online_id',
    ];

    protected $casts = [
        'tanggal'      => 'date',
        'synced_at'    => 'datetime',
        'target'       => 'decimal:3',
        'berat'        => 'decimal:3',
        'selisih'      => 'decimal:3',
        'timbangan_ke' => 'integer',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function (HmiWeighing $weighing) {
            if (empty($weighing->uuid)) {
                $weighing->uuid = (string) Str::uuid();
            }
            if (empty($weighing->site)) {
                $weighing->site = config('hmi.site', 'default');
            }
        });
    }

    /** Baris yang belum tersinkron ke server online. */
    public function scopePendingSync(Builder $query): Builder
    {
        return $query->where('sync_status', 'pending');
    }

    public function scopeForMenu(Builder $query, string $menu): Builder
    {
        return $query->where('menu', $menu);
    }

    /**
     * Payload yang dikirim ke server online. Sengaja TANPA device_id/user_id
     * (id lokal tidak berlaku di online); identitas dibawa lewat operator_name.
     */
    public function toSyncPayload(): array
    {
        return [
            'uuid'          => $this->uuid,
            'menu'          => $this->menu,
            'site'          => $this->site,
            'operator_name' => $this->operator_name,
            'karu_name'     => $this->karu_name,
            'shift'         => $this->shift,
            'tanggal'       => optional($this->tanggal)->toDateString(),
            'produk'        => $this->produk,
            'nama_item'     => $this->nama_item,
            'kode_batch'    => $this->kode_batch,
            'target'        => $this->target !== null ? (float) $this->target : null,
            'berat'         => (float) $this->berat,
            'unit'          => $this->unit,
            'selisih'       => $this->selisih !== null ? (float) $this->selisih : null,
            'timbangan_ke'  => $this->timbangan_ke,
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function device()
    {
        return $this->belongsTo(Device::class);
    }
}
