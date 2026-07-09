<?php

namespace App\Http\Controllers;

use App\Events\ScaleReading;
use App\Jobs\ForwardWeighingsBatch;
use App\Models\Device;
use App\Models\HmiWeighing;
use App\Models\IncomingRmpm;
use App\Models\Produk;
use App\Services\HmiBroadcaster;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;

/**
 * Kiosk HMI untuk operator — 2 template acuan: Bahan Baku & Formulasi.
 *
 * Alur:
 *   - GET  /hmi-display/{menu}          -> render halaman hmi-display (Inertia).
 *   - POST /hmi-display/{menu}/live     -> berat live (ephemeral, broadcast saja).
 *   - POST /hmi-display/{menu}/print    -> simpan 1 baris + (role local) antre forward
 *                                    atau (role online) langsung broadcast.
 */
class HmiController extends Controller
{
    /** Halaman Bahan Baku (Incoming). */
    public function bahanBaku(Request $request)
    {
        return Inertia::render('Hmi/BahanBaku', [
            'menu'      => 'bahan-baku',
            'materials' => $this->materials(),
            'context'   => $this->operatorContext($request),
            'history'   => $this->todayHistory('bahan-baku'),
        ]);
    }

    /** Halaman Formulasi. */
    public function formulasi(Request $request)
    {
        return Inertia::render('Hmi/Formulasi', [
            'menu'      => 'formulasi',
            'products'  => $this->products(),
            'materials' => $this->materials(),
            'context'   => $this->operatorContext($request),
            'history'   => $this->todayHistory('formulasi'),
        ]);
    }

    /**
     * Berat live dari timbangan -> broadcast ke channel LAN-only `scale.{device}`.
     * TIDAK menyentuh DB. Di-throttle per device agar tidak membanjiri Reverb.
     */
    public function live(Request $request, string $menu)
    {
        $this->assertMenu($menu);

        $validated = $request->validate([
            'device' => 'required|string|max:100',
            'weight' => 'required|numeric',
            'unit'   => 'nullable|string|max:8',
        ]);

        $throttleSeconds = max(1, (int) ceil(((int) config('hmi.live.throttle_ms', 200)) / 1000));

        // add() atomik: true bila key belum ada. Bila masih ada -> di-throttle.
        if (! Cache::add("live-throttle:{$validated['device']}", true, $throttleSeconds)) {
            return response()->noContent(); // 204, lewati broadcast.
        }

        broadcast(new ScaleReading(
            $validated['device'],
            $menu,
            (float) $validated['weight'],
            $validated['unit'] ?? 'kg',
        ));

        return response()->noContent();
    }

    /**
     * PRINT — satu-satunya pemicu persist + kirim ke server.
     */
    public function print(Request $request, string $menu)
    {
        $this->assertMenu($menu);

        $validated = $request->validate([
            'nama_item'    => 'required|string|max:255',
            'berat'        => 'required|numeric',
            'target'       => 'nullable|numeric',
            'unit'         => 'nullable|string|max:8',
            'kode_batch'   => 'nullable|string|max:255',
            'produk'       => 'nullable|string|max:255',
            'karu_name'    => 'nullable|string|max:255',
            'shift'        => 'nullable|string|max:50',
            'timbangan_ke' => 'nullable|integer|min:1',
            'device'       => 'nullable|string|max:100',
        ]);

        $user   = Auth::user();
        $target = isset($validated['target']) ? (float) $validated['target'] : null;
        $berat  = (float) $validated['berat'];

        $weighing = HmiWeighing::create([
            'menu'          => $menu,
            'device_id'     => $this->resolveDeviceId($validated['device'] ?? null),
            'user_id'       => $user?->id,
            'operator_name' => $user?->name ?? 'Operator',
            'karu_name'     => $validated['karu_name'] ?? null,
            'shift'         => $validated['shift'] ?? $user?->shift,
            'tanggal'       => now()->toDateString(),
            'produk'        => $validated['produk'] ?? null,
            'nama_item'     => $validated['nama_item'],
            'kode_batch'    => $validated['kode_batch'] ?? null,
            'target'        => $target,
            'berat'         => $berat,
            'unit'          => $validated['unit'] ?? 'kg',
            'selisih'       => $target !== null ? round($berat - $target, 3) : null,
            'timbangan_ke'  => $validated['timbangan_ke'] ?? $this->nextTimbanganKe($menu, $user?->name),
            'sync_status'   => 'pending',
        ]);

        if (config('hmi.role') === 'online') {
            // All-in-one: tidak ada langkah forward — langsung tandai synced + broadcast.
            $weighing->update(['sync_status' => 'synced', 'synced_at' => now()]);
            HmiBroadcaster::weighing($weighing);
        } else {
            // Edge: biarkan pending, picu forward batch (di-guard WithoutOverlapping).
            ForwardWeighingsBatch::dispatch();
        }

        return response()->json([
            'status'   => 'ok',
            'weighing' => $weighing->fresh(),
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────

    private function assertMenu(string $menu): void
    {
        abort_unless(in_array($menu, config('hmi.menus', []), true), 404);
    }

    private function operatorContext(Request $request): array
    {
        $user = Auth::user();

        return [
            'operator'  => $user?->name,
            'shift'     => $user?->shift,
            'tanggal'   => now()->toDateString(),
            // Key channel live per-kiosk. Bisa dioverride ?device=CODE (mis. HMI fisik).
            'deviceKey' => $request->query('device', 'op-' . ($user?->id ?? 'x')),
        ];
    }

    private function materials(): array
    {
        // Palet bahan HMI mengikuti mockup (config/hmi.php). Fallback ke daftar
        // master IncomingRmpm bila config sengaja dikosongkan.
        $items = config('hmi.materials', []);

        if (! empty($items)) {
            return array_values($items);
        }

        try {
            return array_values(IncomingRmpm::namaBarangOptions());
        } catch (\Throwable $e) {
            return [];
        }
    }

    private function products(): array
    {
        $produks = Produk::orderBy('nama_produk')->get(['id', 'nama_produk', 'target_berat']);

        if ($produks->isNotEmpty()) {
            return $produks->map(fn ($p) => [
                'id'     => $p->id,
                'nama'   => $p->nama_produk,
                'target' => $p->target_berat !== null ? (float) $p->target_berat : null,
            ])->all();
        }

        // Fallback: daftar produk dari config (tanpa target).
        return collect(config('hmi.products', []))
            ->map(fn ($nama) => ['id' => null, 'nama' => $nama, 'target' => null])
            ->all();
    }

    private function todayHistory(string $menu): array
    {
        $user = Auth::user();

        return HmiWeighing::forMenu($menu)
            ->whereDate('tanggal', today())
            ->when($user, fn ($q) => $q->where('operator_name', $user->name))
            ->orderByDesc('id')
            ->limit(50)
            ->get()
            ->all();
    }

    private function nextTimbanganKe(string $menu, ?string $operator): int
    {
        return HmiWeighing::forMenu($menu)
            ->whereDate('tanggal', today())
            ->when($operator, fn ($q) => $q->where('operator_name', $operator))
            ->count() + 1;
    }

    private function resolveDeviceId(?string $code): ?int
    {
        if (empty($code)) {
            return null;
        }

        return Device::where('device_code', $code)
            ->orWhere('device_token', $code)
            ->value('id');
    }
}
