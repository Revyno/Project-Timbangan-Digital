@extends('layouts.app')

@section('title', 'Edit Produk')
@section('page-title', '✏️ Edit Produk')

@section('content')
<div style="max-width:500px;">
    <div class="card">
        <form method="POST" action="{{ route('produks.update', $produk) }}">
            @csrf @method('PUT')
            <div class="form-group">
                <label class="form-label">Nama Produk</label>
                <input type="text" name="nama_produk" class="form-control" required value="{{ old('nama_produk', $produk->nama_produk) }}">
                @error('nama_produk')<div class="form-error">{{ $message }}</div>@enderror
            </div>
            <div style="display:flex; gap:1rem; margin-top:1.5rem;">
                <button type="submit" class="btn btn-primary" style="flex:1; justify-content:center;">Update Produk</button>
                <a href="{{ route('produks.index') }}" class="btn btn-outline">Batal</a>
            </div>
        </form>
    </div>
</div>
@endsection
