@extends('layouts.app')

@section('title', 'Manajemen Produk')
@section('page-title', '📦 Manajemen Produk')

@section('content')
<div class="card-header" style="margin-bottom:1rem;">
    <a href="{{ route('produks.create') }}" class="btn btn-primary">+ Tambah Produk</a>
</div>

<div class="card">
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Nama Produk</th>
                    <th>Target Berat (kg)</th>
                    <th>Total Penimbangan</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($produks as $p)
                <tr>
                    <td style="font-weight:600;">{{ $p->nama_produk }}</td>
                    <td>{{ $p->target_berat }} kg</td>
                    <td><span class="badge badge-info">{{ $p->penimbangans_count }} Data</span></td>
                    <td>
                        <a href="{{ route('produks.edit', $p) }}" class="btn btn-outline btn-sm">Edit</a>
                        <form action="{{ route('produks.destroy', $p) }}" method="POST" style="display:inline;" onsubmit="return confirm('Hapus produk ini?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-danger btn-sm">Hapus</button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="4" style="text-align:center;color:var(--text2);padding:2rem;">Belum ada data produk.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
