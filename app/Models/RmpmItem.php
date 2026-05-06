<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RmpmItem extends Model
{
    protected $fillable = ['type', 'nama_barang'];

    /**
     * Get all options by type (e.g., 'nama_barang' or 'asal').
     */
    public static function optionsByType(string $type): array
    {
        return static::where('type', $type)
            ->orderBy('nama_barang')
            ->pluck('nama_barang')
            ->toArray();
    }
}
