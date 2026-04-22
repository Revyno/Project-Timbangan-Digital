@extends('layouts.app')

@section('title', 'Tambah Produk')
@section('page-title', '+ Tambah Produk Baru')

@section('content')
<div style="max-width:500px;">
    <div class="card">
        <form method="POST" action="{{ route('produks.store') }}">
            @csrf
            <div class="form-group">
                <label class="form-label">Nama Produk</label>
                <input type="text" name="nama_produk" class="form-control" placeholder="Contoh: Pupuk Organik 50kg" required value="{{ old('nama_produk') }}">
                @error('nama_produk')<div class="form-error">{{ $message }}</div>@enderror
            </div>
            <div class="form-group">
                <label class="form-label">Target Berat (kg)</label>
                <input type="number" step="0.01" name="target_berat" class="form-control" placeholder="50.00" required value="{{ old('target_berat') }}">
                @error('target_berat')<div class="form-error">{{ $message }}</div>@enderror
            </div>
            <div style="display:flex; gap:1rem; margin-top:1.5rem;">
                <button type="submit" class="btn btn-primary" style="flex:1; justify-content:center;">Simpan Produk</button>
                <a href="{{ route('produks.index') }}" class="btn btn-outline">Batal</a>
            </div>
        </form>
    </div>
</div>
@endsection
