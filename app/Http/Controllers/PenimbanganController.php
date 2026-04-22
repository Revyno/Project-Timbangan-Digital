<?php

namespace App\Http\Controllers;

use App\Models\Penimbangan;
use App\Models\Produk;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PenimbanganController extends Controller
{
    public function index()
    {
        $user  = Auth::user();
        $query = Penimbangan::with(['produk', 'user', 'device'])->orderByDesc('created_at');

        if ($user->isOperator()) {
            $query->where('user_id', $user->id);
        }

        $penimbangans = $query->paginate(5);
        return view('penimbangan.index', compact('penimbangans'));
    }

    public function create()
    {
        $produks = Produk::orderBy('nama_produk')->get();
        return view('penimbangan.create', compact('produks'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'produk_id'       => 'required|exists:produks,id',
            'kode_produksi'   => 'required|string|max:100|unique:penimbangans,kode_produksi',
            'tanggal_expired' => 'nullable|date',
        ]);

        Penimbangan::create([
            'tanggal'         => today(),
            'produk_id'       => $validated['produk_id'],
            'user_id'         => Auth::id(),
            'kode_produksi'   => $validated['kode_produksi'],
            'tanggal_expired' => $validated['tanggal_expired'] ?? null,
            'berat'           => 0,
            'selisih'         => 0,
            'status'          => 'menunggu',
        ]);

        return redirect()->route('penimbangan.index')
            ->with('success', 'Data penimbangan dibuat. Menunggu data timbangan dari Arduino.');
    }

    public function show(Penimbangan $penimbangan)
    {
        $user = Auth::user();
        if ($user->isOperator() && $penimbangan->user_id !== $user->id) {
            abort(403);
        }
        $penimbangan->load(['produk', 'user', 'device']);
        return view('penimbangan.show', compact('penimbangan'));
    }

    public function destroy(Penimbangan $penimbangan)
    {
        $user = Auth::user();
        if ($penimbangan->status === 'selesai') {
            return back()->with('error', 'Data yang sudah selesai tidak dapat dihapus.');
        }
        if ($user->isOperator() && $penimbangan->user_id !== $user->id) {
            abort(403);
        }
        $penimbangan->delete();
        return redirect()->route('penimbangan.index')
            ->with('success', 'Data penimbangan berhasil dihapus.');
    }
}