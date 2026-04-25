<?php

namespace App\Http\Controllers;

use App\Models\Produk;
use Illuminate\Http\Request;

class ProdukController extends Controller
{
    public function index()
    {
        $produks = Produk::withCount('penimbangans')->orderBy('nama_produk')->get();
        return view('produks.index', compact('produks'));
    }

    public function create()
    {
        return view('produks.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama_produk'  => 'required|string|max:100',
        ]);

        Produk::create($validated);

        return redirect()->route('produks.index')
            ->with('success', 'Produk berhasil ditambahkan.');
    }

    public function edit(Produk $produk)
    {
        return view('produks.edit', compact('produk'));
    }

    public function update(Request $request, Produk $produk)
    {
        $validated = $request->validate([
            'nama_produk'  => 'required|string|max:100',
        ]);
        $produk->update($validated);

        return redirect()->route('produks.index')
            ->with('success', 'Produk berhasil diperbarui.');
    }

    public function destroy(Produk $produk)
    {
        $produk->delete();
        return redirect()->route('produks.index')
            ->with('success', 'Produk berhasil dihapus.');
    }
}
