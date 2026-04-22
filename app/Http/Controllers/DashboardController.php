<?php

namespace App\Http\Controllers;

use App\Models\Device;
use App\Models\Penimbangan;
use App\Models\Produk;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();

        if ($user->isAdmin()) {
            return $this->adminDashboard($request);
        }

        return $this->operatorDashboard();
    }

    private function adminDashboard(Request $request)
    {
        $query = Penimbangan::with(['produk', 'user', 'device'])
            ->orderByDesc('created_at');

        // Filters
        if ($request->filled('tanggal')) {
            $query->whereDate('tanggal_penimbangan', $request->tanggal);
        }
        if ($request->filled('shift')) {
            $query->whereHas('user', function($q) use ($request) {
                $q->where('shift', $request->shift);
            });
        }
        if ($request->filled('produk')) {
            $query->where('produk_id', $request->produk);
        }

        $penimbangans = $query->paginate(5)->withQueryString();

        // Stats
        $stats = [
            'total' => Penimbangan::count(),
            'menunggu' => Penimbangan::where('status', 'menunggu')->count(),
            'selesai' => Penimbangan::where('status', 'selesai')->count(),
            'invalid' => Penimbangan::where('status', 'invalid')->count(),
        ];

        $produks = Produk::orderBy('nama_produk')->get();
        $devices = Device::all();

        return view('dashboard.admin', compact('penimbangans', 'stats', 'produks', 'devices'));
    }

    private function operatorDashboard()
    {
        $user = Auth::user();
        $penimbangans = Penimbangan::with(['produk', 'user'])
            ->where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->paginate(5);

        $produks = Produk::orderBy('nama_produk')->get();

        return view('dashboard.operator', compact('penimbangans', 'produks'));
    }

    public function storePenimbangan(Request $request)
    {
        $validated = $request->validate([
            'produk_id' => 'required|exists:produks,id',
            'tanggal_expired' => 'nullable|date',
        ]);

        // Auto-generate Kode Produksi
        // Format: LOT-YYYYMMDD-COUNT
        $date = now()->format('Ymd');
        $count = Penimbangan::whereDate('created_at', today())->count() + 1;
        $kode_produksi = 'LOT-' . $date . '-' . str_pad($count, 3, '0', STR_PAD_LEFT);

        // Ensure uniqueness just in case
        while (Penimbangan::where('kode_produksi', $kode_produksi)->exists()) {
            $count++;
            $kode_produksi = 'LOT-' . $date . '-' . str_pad($count, 3, '0', STR_PAD_LEFT);
        }

        Penimbangan::create([
            'tanggal_penimbangan' => now()->format('Y-m-d'),
            'produk_id' => $validated['produk_id'],
            'user_id' => Auth::id(),
            'kode_produksi' => $kode_produksi,
            'tanggal_expired' => $validated['tanggal_expired'] ?? null,
            'status' => 'menunggu',
            'berat' => 0,
            'selisih' => 0,
        ]);

        return back()->with('success', "Data [{$kode_produksi}] berhasil ditambahkan. Menunggu data dari Arduino.");
    }
}
