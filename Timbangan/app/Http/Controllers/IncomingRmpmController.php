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

        // Check session lock — isSessionLocked() otomatis unlock saat shift berganti
        if ($user->isSessionLocked()) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            return redirect()->route('login')->withErrors([
                'email' => 'Sesi shift Anda telah berakhir. Silahkan login kembali saat shift berikutnya dimulai.',
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
            'namaBarangOptions' => IncomingRmpm::namaBarangOptions(),
            'asalOptions' => IncomingRmpm::asalOptions(),
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
        if ($request->filled('shift')) {
            $query->whereHas('user', function($q) use ($request) {
                $q->where('shift', $request->shift);
            });
        }
        if ($request->filled('operator')) {
            $query->where('user_id', $request->operator);
        }

        $history = $query->with('user')
            ->orderByDesc('created_at')
            ->paginate(15)
            ->withQueryString();

        $stats = [
            'total' => (clone $query)->count(),
            'total_berat' => (clone $query)->where('status', 'selesai')->sum('berat'),
        ];

        $operators = \App\Models\User::where('tipe', 'incoming_rmpm')->select('id', 'name')->get();

        return \Inertia\Inertia::render('Dashboard/DataView', [
            'title' => 'Incoming RMPM',
            'subtitle' => 'Data penimbangan RMPM masuk (Read Only)',
            'penimbangans' => $history,
            'stats' => $stats,
            'produks' => [],
            'shifts' => \App\Models\User::where('tipe', 'incoming_rmpm')->whereNotNull('shift')->distinct()->orderBy('shift')->pluck('shift'),
            'operators' => $operators,
            'filters' => $request->only(['tanggal_mulai', 'tanggal_selesai', 'jenis_barang', 'shift', 'operator']),
            'exportRoute' => 'admin.incoming.rmpm.export',
        ]);
    }

    public function start(Request $request)
    {
        $validated = $request->validate([
            'tanggal_kedatangan' => 'required|date',
            'nama_barang' => 'required|string',
            'jenis_barang' => 'required|string',
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

        // Kunci sesi — waktu kunci disimpan ke cache oleh User boot observer
        $user->update(['session_locked' => true]);

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        $shiftStart = $user->shift_start ?? null;
        $nextShiftInfo = $shiftStart
            ? "Login kembali diperbolehkan mulai jam {$shiftStart} pada shift berikutnya."
            : 'Login kembali diperbolehkan pada shift berikutnya.';

        return redirect()->route('login')->with('status', "Shift selesai. {$nextShiftInfo}");
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
        if ($request->filled('shift')) {
            $query->whereHas('user', function($q) use ($request) {
                $q->where('shift', $request->shift);
            });
        }
        if ($request->filled('operator')) {
            $query->where('user_id', $request->operator);
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
