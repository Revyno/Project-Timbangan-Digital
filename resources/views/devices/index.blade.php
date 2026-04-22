@extends('layouts.app')

@section('title', 'Manajemen Device')
@section('page-title', '📡 Manajemen Device IoT')

@section('content')
<div class="card-header" style="margin-bottom:1rem;">
    <a href="{{ route('devices.create') }}" class="btn btn-primary">+ Tambah Device</a>
</div>

<div class="card">
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Status</th>
                    <th>Nama Device</th>
                    <th>Kode / Token</th>
                    <th>Last Online</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($devices as $d)
                @php $isOnline = $d->isOnline(); @endphp
                <tr>
                    <td>
                        <span class="dot {{ $isOnline ? 'dot-online' : 'dot-offline' }}"></span>
                        <span class="badge {{ $isOnline ? 'badge-online' : 'badge-offline' }}" style="margin-left:.5rem;">
                            {{ $isOnline ? 'Online' : 'Offline' }}
                        </span>
                    </td>
                    <td>
                        <div style="font-weight:600;">{{ $d->device_name }}</div>
                        <div style="font-size:.7rem; color:var(--text2);">{{ $d->is_active ? 'Active' : 'Disabled' }}</div>
                    </td>
                    <td>
                        <div style="font-size:.75rem; color:var(--text2);">Code: {{ $d->device_code }}</div>
                        <div style="font-family:monospace; font-size:.7rem; color:var(--primary); padding-top:.2rem;">
                            Token: {{ substr($d->device_token, 0, 15) }}...
                        </div>
                    </td>
                    <td style="font-size:.8rem;">
                        {{ $d->last_online ? $d->last_online->diffForHumans() : 'Never' }}
                    </td>
                    <td>
                        <div style="display:flex; gap:.4rem;">
                            <form action="{{ route('devices.regenerate-token', $d) }}" method="POST" onsubmit="return confirm('Regenerate token?')">
                                @csrf
                                <button type="submit" class="btn btn-outline btn-sm" title="Regenerate Token">🔄 Token</button>
                            </form>
                            <a href="{{ route('devices.edit', $d) }}" class="btn btn-outline btn-sm">Edit</a>
                            <form action="{{ route('devices.destroy', $d) }}" method="POST" style="display:inline;" onsubmit="return confirm('Hapus device?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-danger btn-sm">Hapus</button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" style="text-align:center;color:var(--text2);padding:2rem;">Belum ada device terdaftar.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
