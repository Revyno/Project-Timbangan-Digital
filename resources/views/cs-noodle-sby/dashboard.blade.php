@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        <div>
            <h2 class="text-2xl font-black text-gray-800">Carton Sealer (Noodle)</h2>
            <p class="text-sm text-gray-500 mt-1">Dashboard operator CS Noodle Surabaya</p>
        </div>
        <div class="bg-white border border-gray-200 rounded-2xl shadow-sm p-5 flex items-center gap-4">
            <div class="p-3 bg-blue-50 rounded-xl">
                <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>
            </div>
            <div>
                <p class="text-xs font-medium text-gray-400 uppercase tracking-wide">Total Penimbangan</p>
                <p class="text-2xl font-black text-gray-800">{{ $totalShift }}</p>
            </div>
        </div>
        <div class="bg-white border border-gray-200 rounded-2xl shadow-sm p-5 flex items-center gap-4">
            <div class="p-3 bg-green-50 rounded-xl">
                <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3"/></svg>
            </div>
            <div>
                <p class="text-xs font-medium text-gray-400 uppercase tracking-wide">Total Berat</p>
                <p class="text-2xl font-black text-gray-800">{{ number_format($totalBerat, 2) }} <span class="text-sm font-medium text-gray-400">kg</span></p>
            </div>
        </div>
    </div>

    <!-- Session Control -->
    <div class="relative z-10 bg-white border border-gray-200 rounded-2xl shadow-md p-6">
        <div class="flex items-center gap-3 mb-6">
            <div class="p-2 bg-blue-50 rounded-lg border border-blue-200">
                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"/></svg>
            </div>
            <h3 class="text-lg font-bold text-gray-800">Kontrol Sesi Penimbangan</h3>
        </div>

        @if($errors->any())
            <div class="mb-4 p-4 text-sm text-red-800 rounded-2xl bg-red-50 border border-red-100" role="alert">
                <ul class="list-disc list-inside">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if($activeSession)
            <div class="p-5 bg-emerald-500/10 border border-green-200 rounded-2xl">
                <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                    <div>
                        <span class="text-[10px] font-black text-green-600 uppercase tracking-widest">Sesi Aktif</span>
                        <h4 class="text-2xl font-black text-gray-800 mt-1">{{ $activeSession->produk->nama_produk }}</h4>
                        <div class="flex flex-wrap gap-4 mt-2">
                            <div class="flex items-center gap-2">
                                <span class="text-xs text-gray-500">KP:</span>
                                <span class="text-xs font-bold text-gray-800">{{ $activeSession->kode_produksi }}</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="text-xs text-gray-500">Expired:</span>
                                <span class="text-xs font-bold text-gray-800">{{ $activeSession->tanggal_expired->format('d/m/Y') }}</span>
                            </div>
                        </div>
                    </div>
                <div class="flex flex-col md:flex-row gap-3">
                    <!-- Tombol Ganti Produk -->
                    <form action="{{ route('cs-noodle-sby.next') }}" method="POST" class="w-full md:w-auto">
                        @csrf
                        <button type="submit" class="px-6 py-3 bg-green-600 hover:bg-green-700 text-white font-bold rounded-xl transition-all shadow-lg shadow-green-500/20 flex items-center justify-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                            Ganti Produk
                        </button>
                    </form>

                    <!-- Tombol Berhenti Total -->
                    <form action="{{ route('cs-noodle-sby.stop') }}" method="POST" class="w-full md:w-auto">
                        @csrf
                        <button type="submit" onclick="return confirm('Akhiri shift dan kunci akun sampai besok?')" class="px-6 py-3 bg-red-600 hover:bg-red-700 text-white font-bold rounded-xl transition-all shadow-lg shadow-red-500/20 flex items-center justify-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                            Selesai Shift
                        </button>
                    </form>
                </div>
                </div>
                <div class="mt-4 p-3 bg-emerald-500/10 rounded-xl border border-emerald-500/20">
                    <p class="text-sm text-green-600 font-medium flex items-center gap-2">
                        <svg class="w-4 h-4 animate-pulse" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-11a1 1 0 10-2 0v2H7a1 1 0 100 2h2v2a1 1 0 102 0v-2h2a1 1 0 100-2h-2V7z" clip-rule="evenodd"/></svg>
                        Sistem siap menerima data berat dari timbangan IoT.
                    </p>
                </div>
            </div>
        @else
            <form action="{{ route('cs-noodle-sby.store') }}" method="POST" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                @csrf
                <div>
                    <label class="block text-xs font-bold text-gray-700">produk_cs_noodle_select</label>
                        <select name="produk_id" id="produk_cs_noodle_select" required class="w-full bg-gray-50 border border-gray-300 text-gray-800 text-sm rounded-xl focus:ring-blue-500 focus:border-blue-500 p-3">
                        <option value="">-- Cari atau Pilih Produk --</option>
                        @foreach($produks as $p)
                            <option value="{{ $p->id }}" {{ (isset($lastSession['produk_id']) && $lastSession['produk_id'] == $p->id) ? 'selected' : '' }}>{{ $p->nama_produk }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-700">Kode Produksi</label>
<input type="text" name="kode_produksi" required value="{{ $lastSession['kode_produksi'] ?? '' }}" placeholder="Contoh: KP-20240428-001" class="w-full bg-gray-50 border border-gray-300 text-gray-800 text-sm rounded-xl focus:ring-blue-500 focus:border-blue-500 p-3 placeholder-gray-400">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-700">Tanggal Expired</label>
<input type="date" name="tanggal_expired" required value="{{ $lastSession['tanggal_expired'] ?? '' }}" class="w-full bg-gray-50 border border-gray-300 text-gray-800 text-sm rounded-xl focus:ring-blue-500 focus:border-blue-500 p-3">
                </div>
                <div class="md:col-span-3">
                    <button type="submit" class="w-full md:w-auto px-10 py-3 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-xl transition-all shadow-lg shadow-orange-500/20 flex items-center justify-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        Mulai Menimbang
                    </button>
                </div>
            </form>
        @endif
    </div>

    <!-- History -->
    <div class="relative z-0 bg-white border border-gray-200 rounded-2xl shadow-md overflow-hidden backdrop-blur-sm">
        <div class="p-6 border-b border-gray-200">
            <h5 class="text-xl font-black text-gray-800">Riwayat Penimbangan Hari Ini</h5>
        </div>
        <div class="relative overflow-x-auto">
            <table class="w-full text-sm text-left text-gray-600">
                <thead class="text-xs text-gray-500 uppercase bg-gray-50">
                    <tr>
                        <th class="px-4 py-3">No</th>
                        <th class="px-4 py-3">Jam</th>
                        <th class="px-4 py-3">Produk</th>
                        <th class="px-4 py-3">Kode Produksi</th>
                        <th class="px-4 py-3">Berat</th>
                        <th class="px-4 py-3">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($history as $i => $h)
                    <tr class="bg-white border-b border-gray-200 hover transition-colors">
                        <td class="px-4 py-3 text-gray-500">{{ $history->firstItem() + $i }}</td>
                        <td class="px-4 py-3 text-gray-800 font-medium">{{ $h->created_at->format('d/m/Y H:i:s') }}</td>
                        <td class="px-4 py-3 font-medium text-gray-800">{{ $h->produk->nama_produk }}</td>
                        <td class="px-4 py-3"><span class="font-mono text-xs text-blue-600">{{ $h->kode_produksi_display ?? $h->kode_produksi }}</span></td>
                        <td class="px-4 py-3 font-bold text-gray-800">
                            @if($h->berat > 0)
                                {{ number_format($h->berat, 3) }} <span class="text-xs font-normal text-gray-400">kg</span>
                            @else
                                <span class="text-gray-400 italic text-xs">Belum ditimbang</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            @if($h->status == 'selesai')
                                <span class="bg-green-50 text-green-600 text-xs font-medium px-2.5 py-0.5 rounded-lg border border-green-200">Selesai</span>
                            @elseif($h->status == 'menunggu')
                                <span class="bg-yellow-500/20 text-yellow-400 text-xs font-medium px-2.5 py-0.5 rounded-lg border border-yellow-500/30">Menunggu</span>
                            @else
                                <span class="bg-red-500/20 text-red-400 text-xs font-medium px-2.5 py-0.5 rounded-lg border border-red-500/30">Invalid</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                    @if($history->isEmpty())
                    <tr><td colspan="6" class="px-4 py-8 text-center text-gray-400">Belum ada data penimbangan hari ini.</td></tr>
                    @endif
                </tbody>
            </table>
        </div>
        <div class="p-4">{{ $history->links() }}</div>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener("DOMContentLoaded", function() {
        if (document.getElementById('produk_cs_noodle_select')) {
            new TomSelect('#produk_cs_noodle_select', { create: false, sortField: { field: "text", direction: "asc" }, placeholder: "-- Cari atau Pilih Produk --" });
        }
    });
    @if(session('success'))
        Swal.fire({ icon: 'success', title: 'Berhasil!', text: "{{ session('success') }}", timer: 3000, showConfirmButton: false });
    @endif
    @if(session('error'))
        Swal.fire({ icon: 'error', title: 'Gagal!', text: "{{ session('error') }}" });
    @endif
</script>
@endpush
@endsection
