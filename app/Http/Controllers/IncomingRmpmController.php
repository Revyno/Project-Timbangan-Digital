<?php

namespace App\Http\Controllers;

use App\Models\IncomingRmpm;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class IncomingRmpmController extends Controller
{
    public function dashboard(Request $request)
    {
        $user = Auth::user();

        // Admin sees read-only view with filters
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

        $activeSession = cache()->get("session_rmpm_{$user->id}");

        $totalShift = IncomingRmpm::where('user_id', $user->id)
            ->whereDate('created_at', today())
            ->where('status', 'selesai')
            ->count();

        $totalBerat = IncomingRmpm::where('user_id', $user->id)
            ->whereDate('created_at', today())
            ->where('status', 'selesai')
            ->sum('berat');

        $history = IncomingRmpm::where('user_id', $user->id)
            ->whereDate('created_at', today())
            ->orderByDesc('created_at')
            ->paginate(10);

        return \Inertia\Inertia::render('Incoming/Rmpm', [
            'activeSession' => $activeSession ? (object)$activeSession : null,
            'totalShift' => $totalShift,
            'totalBerat' => $totalBerat,
            'history' => $history,
        ]);
    }

    private function adminView(Request $request)
    {
        $query = IncomingRmpm::query();

        if ($request->filled('tanggal_mulai')) {
            $query->whereDate('tanggal_kedatangan', '>=', $request->tanggal_mulai);
        }
        if ($request->filled('tanggal_selesai')) {
            $query->whereDate('tanggal_kedatangan', '<=', $request->tanggal_selesai);
        }
        if ($request->filled('jenis_barang')) {
            $query->where('jenis_barang', $request->jenis_barang);
        }

        $history = $query->with('user')
            ->orderByDesc('created_at')
            ->paginate(15)
            ->withQueryString();

        $stats = [
            'total' => (clone $query)->count(),
            'total_berat' => (clone $query)->where('status', 'selesai')->sum('berat'),
        ];

        return \Inertia\Inertia::render('Dashboard/DataView', [
            'title' => 'Incoming RMPM',
            'subtitle' => 'Data penimbangan RMPM masuk (Read Only)',
            'penimbangans' => $history,
            'stats' => $stats,
            'produks' => [],
            'filters' => $request->only(['tanggal_mulai', 'tanggal_selesai', 'jenis_barang']),
            'exportRoute' => 'incoming.rmpm.export',
        ]);
    }

    public function start(Request $request)
    {
        $validated = $request->validate([
            'tanggal_kedatangan' => 'required|date',
            'nama_barang' => 'required|string',
            'jenis_barang' => 'required|in:raw_material,packaging_material,lainnya',
            'asal' => 'required|string',
            'nama_supplier' => 'required|string',
            'no_surat' => 'required|string',
            'nama_sopir' => 'required|string',
            'nomor_plat' => 'required|string',
            'total_qty' => 'required|integer|min:1',
            'kode_batch' => 'nullable|string',
            'expired_date' => 'nullable|date',
        ]);

        $user = Auth::user();
        $validated['petugas_penerima'] = $user->name;

        cache()->put("session_rmpm_{$user->id}", $validated, now()->addHours(12));

        return redirect()->route('incoming.rmpm.dashboard')
            ->with('success', "Sesi penimbangan RMPM [{$validated['nama_barang']}] dimulai.");
    }

    public function nextSession()
    {
        $user = Auth::user();
        cache()->forget("session_rmpm_{$user->id}");
        
        return redirect()->route('incoming.rmpm.dashboard')->with('success', 'Sesi produk selesai. Silahkan mulai sesi produk baru.');
    }

    public function stop(Request $request)
    {
        $user = Auth::user();
        cache()->forget("session_rmpm_{$user->id}");

        $user->update(['session_locked' => true]);

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('status', 'Sesi penimbangan telah dihentikan. Silahkan login kembali besok.');
    }

    public function export(Request $request)
    {
        $query = IncomingRmpm::with('user')->orderByDesc('created_at');

        if ($request->filled('tanggal_mulai')) {
            $query->whereDate('tanggal_kedatangan', '>=', $request->tanggal_mulai);
        }
        if ($request->filled('tanggal_selesai')) {
            $query->whereDate('tanggal_kedatangan', '<=', $request->tanggal_selesai);
        }

        $records = $query->get();
        $filename = "rekap_incoming_rmpm_" . now()->format('Ymd_His') . ".csv";

        $headers = [
            "Content-type" => "text/csv",
            "Content-Disposition" => "attachment; filename=$filename",
        ];

        $columns = ['Tanggal', 'Petugas', 'Nama Barang', 'Jenis', 'Asal', 'Supplier', 'No Surat', 'Sopir', 'Plat', 'Qty', 'Batch', 'Expired', 'Berat (kg)'];

        $callback = function () use ($records, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);
            foreach ($records as $r) {
                fputcsv($file, [
                    $r->created_at->format('d/m/Y H:i:s'),
                    $r->petugas_penerima,
                    $r->nama_barang,
                    $r->jenis_barang,
                    $r->asal,
                    $r->nama_supplier,
                    $r->no_surat,
                    $r->nama_sopir,
                    $r->nomor_plat,
                    $r->total_qty,
                    $r->kode_batch ?? '-',
                    $r->expired_date ? $r->expired_date->format('d/m/Y') : '-',
                    number_format($r->berat, 3),
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
