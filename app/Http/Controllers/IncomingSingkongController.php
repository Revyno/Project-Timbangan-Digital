<?php

namespace App\Http\Controllers;

use App\Models\IncomingSingkong;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class IncomingSingkongController extends Controller
{
    public function dashboard(Request $request)
    {
        $user = Auth::user();

        // Admin sees read-only view with filters
        if ($user->isAdmin()) {
            return $this->adminView($request);
        }

        // Check session lock
        if ($user->isSessionLocked()) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            return redirect()->route('login')->withErrors([
                'email' => 'Sesi Anda telah berakhir untuk hari ini. Silahkan login kembali besok.',
            ]);
        }

        $activeSession = cache()->get("session_singkong_{$user->id}");

        $totalShift = IncomingSingkong::where('user_id', $user->id)
            ->whereDate('created_at', today())
            ->where('status', 'selesai')
            ->count();

        $totalBerat = IncomingSingkong::where('user_id', $user->id)
            ->whereDate('created_at', today())
            ->where('status', 'selesai')
            ->sum('berat');

        $history = IncomingSingkong::where('user_id', $user->id)
            ->whereDate('created_at', today())
            ->orderByDesc('created_at')
            ->paginate(10);

        return \Inertia\Inertia::render('Incoming/Singkong', [
            'activeSession'   => $activeSession ? (object)$activeSession : null,
            'totalShift'      => $totalShift,
            'totalBerat'      => $totalBerat,
            'history'         => $history,
            'jenisOptions'    => IncomingSingkong::jenisOptions(),
            'asalOptions'     => IncomingSingkong::asalOptions(),
            'supplierOptions' => IncomingSingkong::supplierOptions(),
        ]);
    }

    private function adminView(Request $request)
    {
        $query = IncomingSingkong::query();

        if ($request->filled('tanggal_mulai')) {
            $query->whereDate('tanggal_penimbangan', '>=', $request->tanggal_mulai);
        }
        if ($request->filled('tanggal_selesai')) {
            $query->whereDate('tanggal_penimbangan', '<=', $request->tanggal_selesai);
        }
        if ($request->filled('supplier')) {
            $query->where('nama_supplier', $request->supplier);
        }
        if ($request->filled('jenis')) {
            $query->where('jenis_singkong', $request->jenis);
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

        $operators = \App\Models\User::where('tipe', 'incoming_singkong')->select('id', 'name')->get();

        return \Inertia\Inertia::render('Dashboard/DataView', [
            'title'           => 'Incoming Singkong',
            'subtitle'        => 'Data penimbangan singkong masuk (Read Only)',
            'penimbangans'    => $history,
            'stats'           => $stats,
            'produks'         => [],
            'shifts'          => \App\Models\User::where('tipe', 'incoming_singkong')->whereNotNull('shift')->distinct()->orderBy('shift')->pluck('shift'),
            'operators'       => $operators,
            'filters'         => $request->only(['tanggal_mulai', 'tanggal_selesai', 'supplier', 'jenis', 'shift', 'operator']),
            'exportRoute'     => 'admin.incoming.singkong.export',
            'jenisOptions'    => IncomingSingkong::jenisOptions(),
            'supplierOptions' => IncomingSingkong::supplierOptions(),
        ]);
    }

    public function start(Request $request)
    {
        $validated = $request->validate([
            'no_surat' => 'required|string',
            'nama_supplier' => 'required|string',
            'asal' => 'required|string',
            'nama_sopir' => 'required|string',
            'nomor_plat' => 'required|string',
            'jenis_singkong' => 'required|string',
            'kode_produksi' => 'required|string',
        ]);

        $user = Auth::user();

        cache()->put("session_singkong_{$user->id}", $validated, now()->addHours(12));

        return redirect()->route('incoming.singkong.dashboard')
            ->with('success', "Sesi penimbangan singkong [{$validated['kode_produksi']}] dimulai.");
    }

    public function nextSession()
    {
        $user = Auth::user();
        cache()->forget("session_singkong_{$user->id}");
        
        return redirect()->route('incoming.singkong.dashboard')->with('success', 'Sesi produk selesai. Silahkan mulai sesi produk baru.');
    }

    public function stop(Request $request)
    {
        $user = Auth::user();
        cache()->forget("session_singkong_{$user->id}");

        // Lock operator session
        $user->update(['session_locked' => true]);

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('status', 'Sesi penimbangan telah dihentikan. Silahkan login kembali besok.');
    }

    public function export(Request $request)
    {
        $query = IncomingSingkong::with('user')->orderBy('created_at', 'desc');

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
        if ($request->filled('operator')) {
            $query->where('user_id', $request->operator);
        }

        $records = $query->get();
        $filename = "incoming_singkong_pasuruan_" . now()->format('Ymd_His') . ".xls";

        // Get summary from first record for header info
        $first = $records->first();
        $last = $records->last();

        $supplierName = $first->nama_supplier ?? '-';
        $asal = $first->asal ?? '-';
        $sopir = $first->nama_sopir ?? '-';
        $plat = $first->nomor_plat ?? '-';
        $noSurat = $first->no_surat ?? '-';
        $jenisSingkong = $first->jenis_singkong ?? '-';
        $tanggalPenimbangan = $first ? $first->created_at->format('d/m/Y') : '-';
        $startPenimbangan = $last ? $last->created_at->format('H:i:s') : '-';
        $endPenimbangan = $first ? $first->created_at->format('H:i:s') : '-';
        $jumlahPenimbangan = $records->count();
        $totalBerat = number_format($records->sum('berat') / 1000, 3);

        $html = '
        <html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel">
        <head><meta charset="UTF-8">
        <style>
            body { font-family: Arial, sans-serif; font-size: 11pt; }
            .title { font-size: 18pt; font-weight: bold; color: #0d6c67; text-align: center; padding: 10px; }
            .info-box { border: 1px solid #333; padding: 8px; vertical-align: top; }
            .info-label { font-weight: bold; font-size: 11pt; margin-bottom: 4px; }
            .info-value { font-size: 10pt; }
            .header-row { background-color: #0d6c67; color: white; font-weight: bold; font-size: 10pt; text-align: center; }
            .data-row td { border: 1px solid #ccc; padding: 4px 6px; font-size: 10pt; }
            .data-row:nth-child(even) { background-color: #f2f2f2; }
            td, th { mso-number-format:\@; }
        </style>
        </head>
        <body>
        <table>
            <tr><td colspan="9" class="title">INCOMING SINGKONG PASURUAN</td></tr>
            <tr><td colspan="9"></td></tr>
            <tr>
                <td colspan="4" class="info-box">
                    <b>INFO SUPPLIER</b><br>
                    Nama Supplier : ' . $supplierName . '<br>
                    Asal : ' . $asal . '
                </td>
                <td></td>
                <td colspan="4" class="info-box">
                    <b>INFORMASI KENDARAAN</b><br>
                    Nama Sopir : ' . $sopir . '<br>
                    Nomor Plat : ' . $plat . '
                </td>
            </tr>
            <tr>
                <td colspan="4" class="info-box">
                    <b>INFO ADMINISTRASI</b><br>
                    No Surat : ' . $noSurat . '<br>
                    Tanggal Penimbangan : ' . $tanggalPenimbangan . '<br>
                    Jenis Singkong : ' . $jenisSingkong . '
                </td>
                <td></td>
                <td colspan="4" class="info-box">
                    <b>DATA PENIMBANGAN</b><br>
                    Start Penimbangan : ' . $startPenimbangan . '<br>
                    Penimbangan Terakhir : ' . $endPenimbangan . '<br>
                    Jumlah Penimbangan : <b>' . $jumlahPenimbangan . '</b><br>
                    Total Penimbangan : <b>' . $totalBerat . ' kg</b>
                </td>
            </tr>
            <tr><td colspan="9"></td></tr>
            <tr class="header-row">
                <th>No</th>
                <th>Jam Penimbangan</th>
                <th>No. Surat</th>
                <th>Nama Supplier</th>
                <th>Asal</th>
                <th>Nama Sopir</th>
                <th>Nomor Plat</th>
                <th>Jenis Singkong</th>
                <th>Hasil</th>
            </tr>';

        $no = $records->count();
        foreach ($records as $r) {
            $html .= '
            <tr class="data-row">
                <td>' . $no . '</td>
                <td>' . $r->created_at->format('d/m/Y H:i:s') . '</td>
                <td>' . $r->no_surat . '</td>
                <td>' . $r->nama_supplier . '</td>
                <td>' . $r->asal . '</td>
                <td>' . $r->nama_sopir . '</td>
                <td>' . $r->nomor_plat . '</td>
                <td>' . $r->jenis_singkong . '</td>
                <td>' . number_format($r->berat, 0) . ' kg</td>
            </tr>';
            $no--;
        }

        $html .= '</table></body></html>';

        return response($html)
            ->header('Content-Type', 'application/vnd.ms-excel')
            ->header('Content-Disposition', "attachment; filename=\"{$filename}\"");
    }
}
