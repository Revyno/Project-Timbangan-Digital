<?php

namespace App\Http\Controllers;

use App\Models\Penimbangan;
use App\Models\Produk;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FgSurabayaController extends Controller
{
    public function dashboard(Request $request)
    {
        $user = Auth::user();

        if ($user->isAdmin()) {
            return $this->adminView($request);
        }

        if ($user->isSessionLocked()) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            return redirect()->route('login')->withErrors([
                'email' => 'Sesi Anda telah berakhir untuk hari ini. Silahkan login kembali besok.',
            ]);
        }

        $activeSession = cache()->get("session_fg_sby_{$user->id}");

        if ($activeSession) {
            $activeSession = (object) $activeSession;
            $activeSession->produk = Produk::find($activeSession->produk_id);
            $activeSession->tanggal_expired = \Carbon\Carbon::parse($activeSession->tanggal_expired);
        }

        $totalShift = Penimbangan::where('user_id', $user->id)
            ->whereDate('created_at', today())
            ->where('status', 'selesai')
            ->count();

        $totalBerat = Penimbangan::where('user_id', $user->id)
            ->whereDate('created_at', today())
            ->where('status', 'selesai')
            ->sum('berat');

        $produks = Produk::orderBy('nama_produk')->get();
        $lastSession = cache()->get("last_session_fg_sby_{$user->id}");

        $history = Penimbangan::with('produk')
            ->where('user_id', $user->id)
            ->whereDate('created_at', today())
            ->orderByDesc('created_at')
            ->paginate(10);

        return \Inertia\Inertia::render('Dashboard/Operator', [
            'activePenimbangan' => $activeSession,
            'totalShift' => $totalShift,
            'totalBerat' => $totalBerat,
            'produks' => $produks,
            'lastSession' => $lastSession,
            'penimbangans' => $history,
            'storeRoute' => 'fg-surabaya.store',
            'nextRoute' => 'fg-surabaya.next',
            'stopRoute' => 'fg-surabaya.stop',
        ]);
    }

    private function adminView(Request $request)
    {
        $query = Penimbangan::whereHas('user', fn($q) => $q->where('tipe', 'fg_surabaya'));

        if ($request->filled('tanggal_mulai')) {
            $query->whereDate('tanggal_penimbangan', '>=', $request->tanggal_mulai);
        }
        if ($request->filled('tanggal_selesai')) {
            $query->whereDate('tanggal_penimbangan', '<=', $request->tanggal_selesai);
        }
        if ($request->filled('produk')) {
            $query->where('produk_id', $request->produk);
        }

        $penimbangans = (clone $query)->with(['produk', 'user'])
            ->orderByDesc('created_at')
            ->paginate(15)
            ->withQueryString();

        $stats = [
            'total' => (clone $query)->count(),
            'total_berat' => (clone $query)->where('status', 'selesai')->sum('berat'),
        ];

        $produks = Produk::orderBy('nama_produk')->get();

        return \Inertia\Inertia::render('Dashboard/DataView', [
            'title' => 'Surabaya Formulasi',
            'subtitle' => 'Data penimbangan Surabaya Formulasi (Read Only)',
            'penimbangans' => $penimbangans,
            'stats' => $stats,
            'produks' => $produks,
            'filters' => $request->only(['tanggal_mulai', 'tanggal_selesai', 'produk']),
            'exportRoute' => 'fg-surabaya.export',
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'produk_id' => 'required|exists:produks,id',
            'kode_produksi' => 'required|string',
            'tanggal_expired' => 'required|date',
        ]);

        $user = Auth::user();

        $sessionData = [
            'produk_id' => $validated['produk_id'],
            'kode_produksi' => $validated['kode_produksi'],
            'tanggal_expired' => $validated['tanggal_expired'],
        ];

        cache()->put("session_fg_sby_{$user->id}", $sessionData, now()->addHours(12));
        cache()->put("last_session_fg_sby_{$user->id}", $sessionData, now()->addDays(7));

        return redirect()->route('fg-surabaya.dashboard')
            ->with('success', "Sesi penimbangan Formulasi Surabaya [{$validated['kode_produksi']}] dimulai.");
    }

    public function nextSession()
    {
        $user = Auth::user();
        cache()->forget("session_fg_sby_{$user->id}");
        
        return redirect()->route('fg-surabaya.dashboard')->with('success', 'Sesi produk selesai. Silahkan mulai sesi produk baru.');
    }

    public function stop(Request $request)
    {
        $user = Auth::user();
        cache()->forget("session_fg_sby_{$user->id}");

        $user->update(['session_locked' => true]);

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('status', 'Sesi penimbangan telah dihentikan. Silahkan login kembali besok.');
    }

    public function export(Request $request)
    {
        $query = Penimbangan::with(['produk', 'user'])
            ->whereHas('user', fn($q) => $q->where('tipe', 'fg_surabaya'))
            ->orderByDesc('created_at');

        if ($request->filled('tanggal_mulai')) {
            $query->whereDate('tanggal_penimbangan', '>=', $request->tanggal_mulai);
        }
        if ($request->filled('tanggal_selesai')) {
            $query->whereDate('tanggal_penimbangan', '<=', $request->tanggal_selesai);
        }

        $records = $query->get();
        $filename = "rekap_formulasi_surabaya_" . now()->format('Ymd_His') . ".csv";

        $headers = [
            "Content-type" => "text/csv",
            "Content-Disposition" => "attachment; filename=$filename",
        ];

        $columns = ['Tanggal', 'Produk', 'Operator', 'Kode Produksi', 'Expired', 'Berat (kg)', 'Status'];

        $callback = function () use ($records, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);
            foreach ($records as $r) {
                fputcsv($file, [
                    $r->created_at->format('d/m/Y H:i:s'),
                    $r->produk->nama_produk,
                    $r->user->name,
                    $r->kode_produksi_display ?? $r->kode_produksi,
                    $r->tanggal_expired ? $r->tanggal_expired->format('d/m/Y') : '-',
                    number_format($r->berat, 3),
                    $r->status,
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
