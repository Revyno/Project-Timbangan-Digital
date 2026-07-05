<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Produk extends Model
{
    use HasFactory;

    protected $fillable = ['nama_produk', 'target_berat'];

    public function penimbangans()
    {
        return $this->hasMany(Penimbangan::class);
    }
}
