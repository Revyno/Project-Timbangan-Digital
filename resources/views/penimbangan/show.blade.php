@extends('layouts.app')
@section('page-title', 'Detail Penimbangan')
@section('content')
<div class="card" style="max-width:600px;">
    <div class="card-header">
        <div class="card-title">Data Penimbangan {{ $penimbangan->kode_produksi_display }}</div>
        <span class="badge {{ $penimbangan->status == 'selesai' ? 'badge-success' : 'badge-warning' }}">{{ ucfirst($penimbangan->status) }}</span>
    </div>
    <div style="display:grid; grid-template-columns: 1fr 1fr; gap:1rem;">
        <div>
            <div class="form-label">Kode Produksi</div>
            <div style="font-weight:600;">{{ $penimbangan->kode_produksi_display }}</div>
        </div>
        <div>
            <div class="form-label">Tanggal</div>
            <div style="font-weight:600;">{{ $penimbangan->tanggal->format('d F Y') }}</div>
        </div>
        <div>
            <div class="form-label">Produk</div>
            <div style="font-weight:600;">{{ $penimbangan->produk->nama_produk ?? '-' }}</div>
        </div>
        <div>
            <div class="form-label">Petugas</div>
            <div style="font-weight:600;">{{ $penimbangan->user->name ?? '-' }} (Shift {{ $penimbangan->user->shift }})</div>
        </div>
    </div>
    
    <div style="margin-top:2rem; padding-top:2rem; border-top:1px solid var(--border); text-align:center;">
        <div style="font-size:3rem; font-weight:800; color:var(--primary);">{{ $penimbangan->berat }} <span style="font-size:1rem; color:var(--text2);">kg</span></div>
        <div style="font-size:.8rem; color:var(--text2);">Berat Aktual</div>
    </div>
    
    <div style="margin-top:2rem;">
        <a href="{{ route('penimbangan.index') }}" class="btn btn-outline" style="width:100%; justify-content:center;">Kembali</a>
    </div>
</div>
@endsection
