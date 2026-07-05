<?php

namespace Tests\Unit;

use App\Models\Penimbangan;
use PHPUnit\Framework\TestCase;

class PenimbanganModelTest extends TestCase
{
    public function test_kode_produksi_display_strips_unique_suffix(): void
    {
        $p = new Penimbangan(['kode_produksi' => 'KP-2026-001#1717000000']);

        $this->assertSame('KP-2026-001', $p->kode_produksi_display);
    }

    public function test_kode_produksi_display_without_suffix_is_unchanged(): void
    {
        $p = new Penimbangan(['kode_produksi' => 'KP-2026-002']);

        $this->assertSame('KP-2026-002', $p->kode_produksi_display);
    }
}
