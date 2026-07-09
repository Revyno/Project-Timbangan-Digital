<?php

namespace App\Services;

use App\Events\WeightReceived;
use App\Models\HmiWeighing;

/**
 * Satu tempat untuk memetakan HmiWeighing -> event WeightReceived, dipakai baik
 * oleh SyncController (server online menerima sync) maupun oleh kiosk mode
 * all-in-one (role=online). Broadcast-nya ter-queue (lihat WeightReceived).
 */
class HmiBroadcaster
{
    public static function weighing(HmiWeighing $w): void
    {
        // Pakai event() (bukan broadcast()) — event ini ShouldBroadcast sehingga
        // dispatcher tetap mem-broadcast-nya via queue, tapi jadi bisa di-assert
        // dengan Event::fake() di test.
        event(new WeightReceived($w->menu, [
            'weight'        => (float) $w->berat,
            'operator'      => $w->operator_name,
            'product'       => $w->produk ?? $w->nama_item,
            'kode_produksi' => $w->kode_batch ?? '-',
            'status'        => 'selesai',
            'ip_address'    => $w->site,
            'menu'          => $w->menu,
            'nama_item'     => $w->nama_item,
            'kode_batch'    => $w->kode_batch,
            'timbangan_ke'  => $w->timbangan_ke,
            'target'        => $w->target !== null ? (float) $w->target : null,
        ]));
    }
}
