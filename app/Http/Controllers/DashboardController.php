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

        // Sanitize 'page' query parameter
        if ($request->has('page') && (!is_numeric($request->page) || $request->page < 1)) {
            return redirect()->to($request->url());
        }

        if ($user->isAdmin()) {
            return $this->adminDashboard($request);
        }

        // Check session lock for operators
        if ($user->session_locked) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            return redirect()->route('login')->withErrors([
                'email' => 'Sesi Anda telah berakhir. Silahkan hubungi admin untuk mengaktifkan kembali.',
            ]);
        }

        // --- Operator Personal Overview ---
        $stats = [
            'total' => Penimbangan::where('user_id', $user->id)->count(),
            'selesai' => Penimbangan::where('user_id', $user->id)->where('status', 'selesai')->count(),
            'total_berat' => Penimbangan::where('user_id', $user->id)->where('status', 'selesai')->sum('berat'),
            'invalid' => Penimbangan::where('user_id', $user->id)->where('status', 'invalid')->count(),
        ];

        $moduleStats = Penimbangan::join('users', 'penimbangans.user_id', '=', 'users.id')
            ->where('penimbangans.user_id', $user->id)
            ->select('users.tipe', \DB::raw('count(*) as total'), \DB::raw('sum(berat) as total_berat'))
            ->groupBy('users.tipe')
            ->get()
            ->keyBy('tipe');

        $moduleNames = [
            'fg' => 'Pasuruan FG',
            'fg_psn' => 'Pasuruan PSN',
            'fg_surabaya' => 'Surabaya Formulasi',
            'cs_noodle_sby' => 'CS Noodle Surabaya',
            'cs_fg_sby' => 'CS FG Surabaya',
            'incoming_singkong' => 'Incoming Singkong',
            'incoming_rmpm' => 'Incoming RMPM',
        ];

        $recentPenimbangans = Penimbangan::with(['produk'])
            ->where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        return \Inertia\Inertia::render('Dashboard/OperatorOverview', compact('stats', 'moduleStats', 'moduleNames', 'recentPenimbangans'));
    }

    private function adminDashboard(Request $request)
    {
        $baseQuery = Penimbangan::query();

        if ($request->filled('tanggal_mulai')) {
            $baseQuery->whereDate('tanggal_penimbangan', '>=', $request->tanggal_mulai);
        }
        if ($request->filled('tanggal_selesai')) {
            $baseQuery->whereDate('tanggal_penimbangan', '<=', $request->tanggal_selesai);
        }
        if ($request->filled('shift')) {
            $baseQuery->whereHas('user', function($q) use ($request) {
                $q->where('shift', $request->shift);
            });
        }
        if ($request->filled('produk')) {
            $baseQuery->where('produk_id', $request->produk);
        }

        // Global Stats
        $stats = [
            'total' => (clone $baseQuery)->count(),
            'selesai' => (clone $baseQuery)->where('status', 'selesai')->count(),
            'total_berat' => (clone $baseQuery)->where('status', 'selesai')->sum('berat'),
            'invalid' => (clone $baseQuery)->where('status', 'invalid')->count(),
        ];

        // Module Breakdown Stats
        $moduleStats = Penimbangan::join('users', 'penimbangans.user_id', '=', 'users.id')
            ->select('users.tipe', \DB::raw('count(*) as total'), \DB::raw('sum(berat) as total_berat'))
            ->groupBy('users.tipe')
            ->get()
            ->keyBy('tipe');

        // Map internal types to human readable names
        $moduleNames = [
            'fg' => 'Pasuruan FG',
            'fg_psn' => 'Pasuruan PSN',
            'fg_surabaya' => 'Surabaya Formulasi',
            'cs_noodle_sby' => 'CS Noodle Surabaya',
            'cs_fg_sby' => 'CS FG Surabaya',
            'incoming_singkong' => 'Incoming Singkong',
            'incoming_rmpm' => 'Incoming RMPM',
        ];

        // Recent Activity (All modules)
        $recentPenimbangans = Penimbangan::with(['produk', 'user'])
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        $produks = Produk::orderBy('nama_produk')->get();

        return \Inertia\Inertia::render('Dashboard/Admin', compact('recentPenimbangans', 'stats', 'moduleStats', 'moduleNames', 'produks'));
    }

    public function operatorDashboard(Request $request)
    {
        $user = Auth::user();

        if ($user->isAdmin()) {
            // Admin sees the re-styled filter/data view for Pasuruan FG
            $query = Penimbangan::whereHas('user', fn($q) => $q->where('tipe', 'fg'));

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
                'title' => 'Pasuruan FG',
                'subtitle' => 'Data penimbangan Pasuruan FG (Read Only)',
                'penimbangans' => $penimbangans,
                'stats' => $stats,
                'produks' => $produks,
                'filters' => $request->only(['tanggal_mulai', 'tanggal_selesai', 'produk']),
                'exportRoute' => 'penimbangan.export',
            ]);
        }
        
        // Menghitung total penimbangan yang selesai oleh operator ini pada hari ini
        $totalShift = Penimbangan::where('user_id', $user->id)
            ->whereDate('created_at', today())
            ->where('status', 'selesai')
            ->count();

        $totalBerat = Penimbangan::where('user_id', $user->id)
            ->whereDate('created_at', today())
            ->where('status', 'selesai')
            ->sum('berat');

        // Ambil sesi aktif dari Cache
        $activePenimbangan = cache()->get("session_operator_{$user->id}");
        
        // Jika ada di cache, kita ambil model produknya agar bisa tampil nama produknya
        if ($activePenimbangan) {
            $activePenimbangan = (object) $activePenimbangan;
            $activePenimbangan->produk = Produk::find($activePenimbangan->produk_id);
            $activePenimbangan->tanggal_expired = \Carbon\Carbon::parse($activePenimbangan->tanggal_expired);
        }

        $produks = Produk::orderBy('nama_produk')->get();
        $lastSession = cache()->get("last_session_operator_{$user->id}");

        $penimbangans = Penimbangan::with('produk')
            ->where('user_id', $user->id)
            ->whereDate('created_at', today())
            ->orderByDesc('created_at')
            ->paginate(10);

        return \Inertia\Inertia::render('Dashboard/Operator', compact('produks', 'totalShift', 'totalBerat', 'activePenimbangan', 'lastSession', 'penimbangans'));
    }

    public function storePenimbangan(Request $request)
    {
        $validated = $request->validate([
            'produk_id' => 'required|exists:produks,id',
            'kode_produksi' => 'required|string',
            'tanggal_expired' => 'required|date',
        ]);

        $user = Auth::user();

        // Simpan ke Cache (Sesi Aktif)
        $sessionData = [
            'produk_id' => $validated['produk_id'],
            'kode_produksi' => $validated['kode_produksi'],
            'tanggal_expired' => $validated['tanggal_expired'],
        ];

        cache()->put("session_operator_{$user->id}", $sessionData, now()->addHours(8));
        
        // Simpan juga sebagai "Sesi Terakhir" (Untuk pre-fill otomatis nanti)
        cache()->put("last_session_operator_{$user->id}", $sessionData, now()->addDays(7));

        return redirect()->route('fg.dashboard')->with('success', "Sesi penimbangan [{$validated['kode_produksi']}] dimulai. Silahkan lakukan penimbangan.");
    }

    public function nextSession()
    {
        $user = Auth::user();
        cache()->forget("session_operator_" . $user->id);
        
        return redirect()->route('fg.dashboard')->with('success', 'Sesi produk selesai. Silahkan mulai sesi produk baru.');
    }

    public function stopShift(Request $request)
    {
        $user = Auth::user();
        cache()->forget("session_operator_" . $user->id);
        
        $user->update(['session_locked' => true]);

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('status', 'Sesi penimbangan telah dihentikan. Silahkan login kembali besok.');
    }

    public function export(Request $request)
    {
        $query = Penimbangan::with(['produk', 'user', 'device'])
            ->orderByDesc('created_at');

        // Apply same filters as dashboard
        if ($request->filled('tanggal_mulai')) {
            $query->whereDate('tanggal_penimbangan', '>=', $request->tanggal_mulai);
        }
        if ($request->filled('tanggal_selesai')) {
            $query->whereDate('tanggal_penimbangan', '<=', $request->tanggal_selesai);
        }
        if ($request->filled('shift')) {
            $query->whereHas('user', function($q) use ($request) {
                $q->where('shift', $request->shift);
            });
        }
        if ($request->filled('produk')) {
            $query->where('produk_id', $request->produk);
        }

        $records = $query->get();
        $filename = "rekap_penimbangan_" . now()->format('Ymd_His') . ".csv";

        $headers = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=$filename",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $columns = ['Produk', 'Operator', 'Shift', 'Kode Produksi', 'Tanggal Expired', 'Berat (kg)', 'Status'];

        $callback = function() use($records, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            foreach ($records as $r) {
                fputcsv($file, [
                    $r->produk->nama_produk,
                    $r->user->name,
                    'Shift ' . ($r->user->shift ?? '-'),
                    $r->kode_produksi_display,
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
