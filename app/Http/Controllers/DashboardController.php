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

        // Sanitize 'page' query parameter to prevent numeric error on empty string (?page)
        if ($request->has('page') && (!is_numeric($request->page) || $request->page < 1)) {
            return redirect()->to($request->url());
        }

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
        
        // Menghitung total penimbangan yang selesai oleh operator ini pada hari ini
        $totalShift = Penimbangan::where('user_id', $user->id)
            ->whereDate('created_at', today())
            ->where('status', 'selesai')
            ->count();

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

        return view('dashboard.operator', compact('produks', 'totalShift', 'activePenimbangan', 'lastSession'));
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

        return redirect()->route('dashboard')->with('success', "Sesi penimbangan [{$validated['kode_produksi']}] dimulai. Silahkan lakukan penimbangan.");
    }

    public function stopPenimbangan()
    {
        cache()->forget("session_operator_" . Auth::id());
        return redirect()->route('dashboard')->with('success', 'Sesi penimbangan telah dihentikan.');
    }

    public function export(Request $request)
    {
        $query = Penimbangan::with(['produk', 'user', 'device'])
            ->orderByDesc('created_at');

        // Apply same filters as dashboard
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
