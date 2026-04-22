@extends('layouts.app')

@section('title', 'Edit Device')
@section('page-title', '✏️ Edit Device Settings')

@section('content')
<div style="max-width:500px;">
    <div class="card">
        <form method="POST" action="{{ route('devices.update', $device) }}">
            @csrf @method('PUT')
            <div class="form-group">
                <label class="form-label">Kode Device</label>
                <input type="text" class="form-control" value="{{ $device->device_code }}" disabled style="opacity:.6;">
                <p style="font-size:.65rem; color:var(--text2);">Kode device tidak dapat diubah.</p>
            </div>
            <div class="form-group">
                <label class="form-label">Nama Device</label>
                <input type="text" name="device_name" class="form-control" required value="{{ old('device_name', $device->device_name) }}">
                @error('device_name')<div class="form-error">{{ $message }}</div>@enderror
            </div>
            <div class="form-group">
                <label class="form-label">Status</label>
                <div style="display:flex; gap:1rem; padding-top:.5rem;">
                    <label style="display:flex; align-items:center; gap:.5rem; cursor:pointer;">
                        <input type="radio" name="is_active" value="1" {{ $device->is_active ? 'checked' : '' }}> Aktif
                    </label>
                    <label style="display:flex; align-items:center; gap:.5rem; cursor:pointer;">
                        <input type="radio" name="is_active" value="0" {{ !$device->is_active ? 'checked' : '' }}> Nonaktif
                    </label>
                </div>
            </div>
            <div style="display:flex; gap:1rem; margin-top:1.5rem;">
                <button type="submit" class="btn btn-primary" style="flex:1; justify-content:center;">Update Settings</button>
                <a href="{{ route('devices.index') }}" class="btn btn-outline">Batal</a>
            </div>
        </form>
    </div>
</div>
@endsection
