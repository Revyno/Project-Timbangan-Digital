<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    //
      protected $fillable = [
        'nama_produk',
        'target_berat',
    ];

    public function penimbangans()
    {
        return $this->hasMany(Penimbangans::class);
    }
}
