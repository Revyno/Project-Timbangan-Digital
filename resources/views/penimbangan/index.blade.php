@extends('layouts.app')
@section('page-title', 'Data Penimbangan')
@section('content')
<div class="card-header" style="margin-bottom:1rem;">
    @if(auth()->user()->isOperator())
    <a href="{{ route('penimbangan.create') }}" class="btn btn-primary">+ Tambah Penimbangan</a>
    @endif
</div>
<div class="card">
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Tanggal</th>
                    <th>Kode Produksi</th>
                    <th>Produk</th>
                    <th>Berat</th>
                    <th>Selisih</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($penimbangans as $p)
                <tr>
                    <td>{{ $p->tanggal->format('d/m/Y') }}</td>
                    <td style="font-weight: 600;">{{ $p->kode_produksi }}</td>
                    <td>{{ $p->produk->nama_produk ?? '-' }}</td>
                    <td style="font-weight: 600;">{{ $p->berat }} kg</td>
                    <td>
                        <span style="color: {{ $p->selisih >= 0 ? 'var(--success)' : 'var(--danger)' }}">
                            {{ $p->selisih >= 0 ? '+' : '' }}{{ $p->selisih }}
                        </span>
                    </td>
                    <td><span class="badge {{ $p->status == 'selesai' ? 'badge-success' : 'badge-warning' }}">{{ ucfirst($p->status) }}</span></td>
                    <td>
                        <a href="{{ route('penimbangan.show', $p) }}" class="btn btn-outline btn-sm">Detail</a>
                    </td>
                </tr>
                @empty
                <tr><td colspan="7" style="text-align:center; padding:2rem; color:var(--text2);">Belum ada data penimbangan.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div style="margin-top:1rem;">
        {{ $penimbangans->links() }}
    </div>
</div>
@endsection
