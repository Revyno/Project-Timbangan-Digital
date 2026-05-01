@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <!-- Status Overview -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <!-- Operator Card -->
        <div class="p-5 bg-gradient-to-br from-blue-600 to-indigo-700 rounded-2xl shadow-lg text-white">
            <div class="flex items-center gap-4 mb-4">
                <div class="p-3 bg-white/20 rounded-xl">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                </div>
                <div>
                    <h5 class="text-xs font-bold uppercase tracking-widest opacity-70">Operator Aktif</h5>
                    <p class="text-xl font-black">{{ Auth::user()->name }}</p>
                </div>
            </div>
            <div class="text-xs bg-black/20 p-3 rounded-xl border border-white/10">
                <div class="flex justify-between mb-1">
                    <span>Shift Saat Ini:</span>
                    <span class="font-bold">{{ Auth::user()->shift }}</span>
                </div>
                <div class="flex justify-between">
                    <span>Waktu Shift:</span>
                    <span class="font-bold">{{ Auth::user()->shift_start }} - {{ Auth::user()->shift_end }}</span>
                </div>
            </div>
        </div>

        <!-- Manual Session Control -->
        <div class="md:col-span-2 p-6 bg-white border border-gray-200 rounded-3xl shadow-xl">
            <div class="flex items-center gap-3 mb-6">
                <div class="p-2 bg-indigo-100 rounded-lg">
                    <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"/></svg>
                </div>
                <h3 class="text-lg font-bold text-gray-900">Kontrol Sesi Penimbangan</h3>
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

            @if($activePenimbangan)
                <!-- Active Session State -->
                <div class="p-5 bg-emerald-50 border border-emerald-100 rounded-2xl">
                    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                        <div>
                            <span class="text-[10px] font-black text-emerald-600 uppercase tracking-widest">Sesi Aktif</span>
                            <h4 class="text-2xl font-black text-gray-900 mt-1">{{ $activePenimbangan->produk->nama_produk }}</h4>
                            <div class="flex flex-wrap gap-4 mt-2">
                                <div class="flex items-center gap-2">
                                    <span class="text-xs text-gray-500">KP:</span>
                                    <span class="text-xs font-bold text-gray-900">{{ $activePenimbangan->kode_produksi }}</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="text-xs text-gray-500">Expired:</span>
                                    <span class="text-xs font-bold text-gray-900">{{ $activePenimbangan->tanggal_expired->format('d/m/Y') }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="flex flex-col sm:flex-row gap-3 w-full md:w-auto">
                            <!-- Tombol Ganti Produk (Stay Logged In) -->
                            <form action="{{ route('penimbangan.next') }}" method="POST" class="w-full sm:w-auto">
                                @csrf
                                <button type="submit" class="w-full px-6 py-3 bg-green-600 hover:bg-green-700 text-white font-bold rounded-xl transition-all shadow-lg shadow-green-500/20 flex items-center justify-center gap-2">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                    Ganti Produk
                                </button>
                            </form>

                            <!-- Tombol Berhenti Total (Logout & Lock) -->
                            <form action="{{ route('penimbangan.stop') }}" method="POST" class="w-full sm:w-auto">
                                @csrf
                                <button type="submit" onclick="return confirm('Apakah Anda yakin ingin mengakhiri shift? Akun akan dikunci sampai besok.')" class="w-full px-6 py-3 bg-red-600 hover:bg-red-700 text-white font-bold rounded-xl transition-all shadow-lg shadow-red-200 flex items-center justify-center gap-2">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                                    Selesai Shift
                                </button>
                            </form>
                        </div>
                    </div>
                    <div class="mt-4 p-3 bg-white/50 rounded-xl border border-emerald-100">
                        <p class="text-sm text-emerald-700 font-medium flex items-center gap-2">
                            <svg class="w-4 h-4 animate-pulse" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-11a1 1 0 10-2 0v2H7a1 1 0 100 2h2v2a1 1 0 102 0v-2h2a1 1 0 100-2h-2V7z" clip-rule="evenodd"></path></svg>
                            Sistem siap menerima data berat dari timbangan IoT untuk LOT ini.
                        </p>
                    </div>
                </div>
            @else
                <!-- Start New Session Form -->
                <form action="{{ route('penimbangan.store') }}" method="POST" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    @csrf
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-2 ml-1">Pilih Produk</label>
                        <select name="produk_id" required class="w-full bg-gray-50 border border-gray-200 text-gray-900 text-sm rounded-xl focus:ring-indigo-500 focus:border-indigo-500 block p-3">
                            <option value="">-- Pilih Produk --</option>
                            @foreach($produks as $p)
                                <option value="{{ $p->id }}" {{ (isset($lastSession['produk_id']) && $lastSession['produk_id'] == $p->id) ? 'selected' : '' }}>{{ $p->nama_produk }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-2 ml-1">Kode Produksi (KP)</label>
                        <input type="text" name="kode_produksi" required value="{{ $lastSession['kode_produksi'] ?? '' }}" placeholder="Contoh: KP-20240423-001" class="w-full bg-gray-50 border border-gray-200 text-gray-900 text-sm rounded-xl focus:ring-indigo-500 focus:border-indigo-500 block p-3">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-2 ml-1">Tanggal Expired</label>
                        <input type="date" name="tanggal_expired" required value="{{ $lastSession['tanggal_expired'] ?? '' }}" class="w-full bg-gray-50 border border-gray-200 text-gray-900 text-sm rounded-xl focus:ring-indigo-500 focus:border-indigo-500 block p-3">
                    </div>
                    <div class="md:col-span-3">
                        <button type="submit" class="w-full md:w-auto px-10 py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-xl transition-all shadow-lg shadow-indigo-200 flex items-center justify-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            Mulai Menimbang
                        </button>
                    </div>
                </form>
            @endif
        </div>

        <!-- Quick Stats -->
        <div class="p-5 bg-white border border-gray-200 rounded-2xl shadow-lg">
            <h5 class="text-xs font-bold text-gray-500 uppercase tracking-widest mb-4">Total Produksi (Shift Ini)</h5>
            <div class="flex items-baseline gap-2">
                <span class="text-4xl font-black text-gray-900">{{ $totalShift }}</span>
                <span class="text-sm font-bold text-gray-400 uppercase">Items</span>
            </div>
            <p class="text-[10px] text-emerald-600 font-bold mt-2 flex items-center gap-1">
                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
                Semua sistem berjalan normal
            </p>
        </div>
    </div>

    <!-- Live History -->
    <div class="bg-white border border-gray-200 rounded-3xl shadow-xl overflow-hidden">
        <div class="p-6 border-b border-gray-100 flex items-center justify-between">
            <div>
                <h5 class="text-xl font-black text-gray-900">Monitoring Penimbangan Real-Time</h5>
                <p class="text-xs text-gray-500 mt-1">Data dari IoT akan langsung muncul di sini tanpa refresh halaman.</p>
            </div>
        
        </div>
        <div class="p-6">
            <livewire:operator.history-table />
        </div>
    </div>
</div>
@endsection
