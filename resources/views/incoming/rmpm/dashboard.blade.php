@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        <div>
            <h2 class="text-2xl font-black text-gray-800">Incoming RMPM Pasuruan</h2>
            <p class="text-sm text-gray-500 mt-1">Incoming Raw Material & Packaging Material</p>
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
    <div class="bg-white border border-gray-200 rounded-2xl shadow-md p-6">
        <div class="flex items-center gap-3 mb-6">
            <div class="p-2 bg-blue-500/20 rounded-lg border border-blue-500/30">
                <svg class="w-5 h-5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
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
                        <h4 class="text-2xl font-black text-gray-800 mt-1">{{ $activeSession['nama_barang'] }}</h4>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mt-3">
                            <div><span class="text-xs text-gray-500">No Surat:</span><br><span class="text-xs font-bold text-gray-800">{{ $activeSession['no_surat'] }}</span></div>
                            <div><span class="text-xs text-gray-500">Supplier:</span><br><span class="text-xs font-bold text-gray-800">{{ $activeSession['nama_supplier'] }}</span></div>
                            <div><span class="text-xs text-gray-500">Jenis:</span><br><span class="text-xs font-bold text-gray-800">{{ str_replace('_', ' ', ucfirst($activeSession['jenis_barang'])) }}</span></div>
                            <div><span class="text-xs text-gray-500">Sopir:</span><br><span class="text-xs font-bold text-gray-800">{{ $activeSession['nama_sopir'] }} ({{ $activeSession['nomor_plat'] }})</span></div>
                            <div><span class="text-xs text-gray-500">Qty:</span><br><span class="text-xs font-bold text-gray-800">{{ $activeSession['total_qty'] }}</span></div>
                            <div><span class="text-xs text-gray-500">Batch:</span><br><span class="text-xs font-bold text-gray-800">{{ $activeSession['kode_batch'] ?? '-' }}</span></div>
                            <div><span class="text-xs text-gray-500">Expired:</span><br><span class="text-xs font-bold text-gray-800">{{ $activeSession['expired_date'] ?? '-' }}</span></div>
                        </div>
                    </div>
                <div class="flex flex-col md:flex-row gap-3">
                    <!-- Tombol Ganti Produk -->
                    <form action="{{ route('incoming.rmpm.next') }}" method="POST" class="w-full md:w-auto">
                        @csrf
                        <button type="submit" class="px-6 py-3 bg-green-600 hover:bg-green-700 text-white font-bold rounded-xl transition-all shadow-lg shadow-green-500/20 flex items-center justify-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                            Ganti Produk
                        </button>
                    </form>

                    <!-- Tombol Berhenti Total -->
                    <form action="{{ route('incoming.rmpm.stop') }}" method="POST" class="w-full md:w-auto">
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
            <form action="{{ route('incoming.rmpm.start') }}" method="POST" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                @csrf
                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase mb-2 ml-1">Tanggal Kedatangan</label>
                    <input type="date" name="tanggal_kedatangan" required value="{{ now()->format('Y-m-d') }}" class="w-full bg-gray-50 border border-gray-300 text-gray-800 text-sm rounded-xl focus:ring-blue-500 focus:border-blue-500 p-3">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase mb-2 ml-1">Nama Barang</label>
                    <select name="nama_barang" required class="w-full bg-gray-50 border border-gray-300 text-gray-800 text-sm rounded-xl focus:ring-blue-500 focus:border-blue-500 p-3">
                        <option value="">-- Pilih Barang --</option>
                        @foreach(\App\Models\IncomingRmpm::namaBarangOptions() as $b)
                            <option value="{{ $b }}">{{ $b }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase mb-2 ml-1">Jenis Barang</label>
                    <select name="jenis_barang" required class="w-full bg-gray-50 border border-gray-300 text-gray-800 text-sm rounded-xl focus:ring-blue-500 focus:border-blue-500 p-3">
                        <option value="raw_material">Raw Material</option>
                        <option value="packaging_material">Packaging Material</option>
                        <option value="lainnya">Yang Lain</option>
                    </select>
                </div>
                <div x-data="{ asalType: '' }">
                    <label class="block text-xs font-bold text-gray-700 uppercase mb-2 ml-1">Asal</label>
                    <select x-model="asalType" x-on:change="$refs.asalHidden.value = $event.target.value" class="w-full bg-gray-50 border border-gray-300 text-gray-800 text-sm rounded-xl focus:ring-blue-500 focus:border-blue-500 p-3 mb-2">
                        <option value="">-- Pilih Asal --</option>
                        @foreach(\App\Models\IncomingRmpm::asalOptions() as $a)
                            <option value="{{ $a }}">{{ $a }}</option>
                        @endforeach
                    </select>
                    <input x-show="asalType === 'Lainnya'" x-on:input="$refs.asalHidden.value = $event.target.value" type="text" placeholder="Ketik asal lainnya..." class="w-full bg-gray-50 border border-gray-300 text-gray-800 text-sm rounded-xl focus:ring-blue-500 focus:border-blue-500 p-3 placeholder-gray-400">
                    <input type="hidden" name="asal" x-ref="asalHidden" required>
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-700">Nama Supplier</label>
<input type="text" name="nama_supplier" required placeholder="Contoh: Supplier A" class="w-full bg-gray-50 border border-gray-300 text-gray-800 text-sm rounded-xl focus:ring-blue-500 focus:border-blue-500 p-3 placeholder-gray-400">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-700">No Surat</label>
<input type="text" name="no_surat" required placeholder="Contoh: TTS-LSI 00574" class="w-full bg-gray-50 border border-gray-300 text-gray-800 text-sm rounded-xl focus:ring-blue-500 focus:border-blue-500 p-3 placeholder-gray-400">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-700">Nama Sopir</label>
<input type="text" name="nama_sopir" required placeholder="Nama sopir" class="w-full bg-gray-50 border border-gray-300 text-gray-800 text-sm rounded-xl focus:ring-blue-500 focus:border-blue-500 p-3 placeholder-gray-400">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-700">Nomor Plat</label>
<input type="text" name="nomor_plat" required placeholder="Contoh: K 8827 TD" class="w-full bg-gray-50 border border-gray-300 text-gray-800 text-sm rounded-xl focus:ring-blue-500 focus:border-blue-500 p-3 placeholder-gray-400">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-700">Total Qty</label>
<input type="number" min="1" name="total_qty" required min="1" placeholder="100" class="w-full bg-gray-50 border border-gray-300 text-gray-800 text-sm rounded-xl focus:ring-blue-500 focus:border-blue-500 p-3 placeholder-gray-400">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-700">Kode Batch</label>
<input type="text" name="kode_batch" placeholder="Contoh: 1234556" class="w-full bg-gray-50 border border-gray-300 text-gray-800 text-sm rounded-xl focus:ring-blue-500 focus:border-blue-500 p-3 placeholder-gray-400">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-700">expired_date</label>
<input type="date" name="expired_date" class="w-full bg-gray-50 border border-gray-300 text-gray-800 text-sm rounded-xl focus:ring-blue-500 focus:border-blue-500 p-3">
                </div>
                <div class="md:col-span-3 mt-4">
                    <button type="submit" class="w-full md:w-auto px-10 py-4 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-xl transition-all shadow-lg shadow-blue-500/20 flex items-center justify-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        Mulai Menimbang
                    </button>
                </div>
            </form>
        @endif
    </div>

    <!-- History Table -->
    <div class="bg-white border border-gray-200 rounded-2xl shadow-md overflow-hidden backdrop-blur-sm">
        <div class="p-6 border-b border-gray-200">
            <h5 class="text-xl font-black text-gray-800">Riwayat Penimbangan Hari Ini</h5>
        </div>
        <div class="relative overflow-x-auto">
            <table class="w-full text-sm text-left text-gray-600">
                <thead class="text-xs text-gray-500 uppercase bg-gray-50">
                    <tr>
                        <th class="px-4 py-3">No</th>
                        <th class="px-4 py-3">Jam</th>
                        <th class="px-4 py-3">Petugas</th>
                        <th class="px-4 py-3">Nama Barang</th>
                        <th class="px-4 py-3">Jenis</th>
                        <th class="px-4 py-3">Supplier</th>
                        <th class="px-4 py-3">No Surat</th>
                        <th class="px-4 py-3">Qty</th>
                        <th class="px-4 py-3">Hasil</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($history as $i => $h)
                    <tr class="bg-white border-b border-gray-200 hover transition-colors">
                        <td class="px-4 py-3 text-gray-500">{{ $history->firstItem() + $i }}</td>
                        <td class="px-4 py-3 text-gray-800 font-medium">{{ $h->created_at->format('d/m/Y H:i:s') }}</td>
                        <td class="px-4 py-3">{{ $h->petugas_penerima }}</td>
                        <td class="px-4 py-3 font-medium text-gray-800">{{ $h->nama_barang }}</td>
                        <td class="px-4 py-3">{{ str_replace('_', ' ', ucfirst($h->jenis_barang)) }}</td>
                        <td class="px-4 py-3">{{ $h->nama_supplier }}</td>
                        <td class="px-4 py-3 font-mono text-xs text-blue-600">{{ $h->no_surat }}</td>
                        <td class="px-4 py-3">{{ $h->total_qty }}</td>
                        <td class="px-4 py-3 font-bold text-gray-800">{{ number_format($h->berat, 1) }} kg</td>
                    </tr>
                    @endforeach
                    @if($history->isEmpty())
                    <tr><td colspan="9" class="px-4 py-8 text-center text-gray-400">Belum ada data penimbangan hari ini.</td></tr>
                    @endif
                </tbody>
            </table>
        </div>
        <div class="p-4">
            {{ $history->links() }}
        </div>
    </div>
</div>

@push('scripts')
<script>
    @if(session('success'))
        Swal.fire({ icon: 'success', title: 'Berhasil!', text: "{{ session('success') }}", timer: 3000, showConfirmButton: false });
    @endif
    @if(session('error'))
        Swal.fire({ icon: 'error', title: 'Gagal!', text: "{{ session('error') }}" });
    @endif
</script>
@endpush
@endsection
