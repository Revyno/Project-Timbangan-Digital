@extends('layouts.app')

@section('title', 'Tambah Device')
@section('page-title', '📡 Register New Device')

@section('content')
<div style="max-width:500px;">
    <div class="card">
        <form method="POST" action="{{ route('devices.store') }}">
            @csrf
            <div class="form-group">
                <label class="form-label">Kode Device</label>
                <input type="text" name="device_code" class="form-control" placeholder="Contoh: DVC-001" required value="{{ old('device_code') }}">
                @error('device_code')<div class="form-error">{{ $message }}</div>@enderror
            </div>
            <div class="form-group">
                <label class="form-label">Nama Device</label>
                <input type="text" name="device_name" class="form-control" placeholder="Contoh: Timbangan Gudang A" required value="{{ old('device_name') }}">
                @error('device_name')<div class="form-error">{{ $message }}</div>@enderror
            </div>
            <p style="font-size:.7rem; color:var(--text2); margin-bottom:1rem;">
                * Token akan digenerate otomatis setelah penyimpanan.
            </p>
            <div style="display:flex; gap:1rem; margin-top:1.5rem;">
                <button type="submit" class="btn btn-primary" style="flex:1; justify-content:center;">Simpan Device</button>
                <a href="{{ route('devices.index') }}" class="btn btn-outline">Batal</a>
            </div>
        </form>
    </div>
</div>
@endsection
