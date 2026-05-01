@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-black text-gray-800">Finished Good (PSN)</h2>
            <p class="text-sm text-gray-500 mt-1">Data penimbangan FG PSN (Read Only)</p>
        </div>
        <div class="flex flex-wrap items-center gap-4">
            <!-- Flowbite Stats Card 1 -->
            <div class="flex items-center p-3 bg-white border border-gray-200 rounded-lg shadow-sm hover:bg-gray-50 dark:bg-gray-800 dark:border-gray-700 dark:hover:bg-gray-700">
                <div class="p-2 mr-3 bg-green-100 rounded-full dark:bg-green-900">
                    <svg class="w-4 h-4 text-green-600 dark:text-green-300" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M10 0a10 10 0 1 0 10 10A10.011 10.011 0 0 0 10 0Zm3.982 13.982a1 1 0 0 1-1.414 0l-3.274-3.274A1.012 1.012 0 0 1 9 10V6a1 1 0 0 1 2 0v3.586l2.982 2.982a1 1 0 0 1 0 1.414Z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Total Items</p>
                    <p class="text-sm font-bold text-gray-900 dark:text-white">{{ $stats['total'] }} items</p>
                </div>
            </div>

            <!-- Flowbite Stats Card 2 -->
            <div class="flex items-center p-3 bg-white border border-gray-200 rounded-lg shadow-sm hover:bg-gray-50 dark:bg-gray-800 dark:border-gray-700 dark:hover:bg-gray-700">
                <div class="p-2 mr-3 bg-blue-100 rounded-full dark:bg-blue-900">
                    <svg class="w-4 h-4 text-blue-600 dark:text-blue-300" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 20 20">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3"/>
                    </svg>
                </div>
                <div>
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Total Berat</p>
                    <p class="text-sm font-bold text-gray-900 dark:text-white">{{ number_format($stats['total_berat'], 3) }} kg</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="p-6 bg-white border border-gray-200 rounded-2xl shadow-sm">
        <form method="GET" action="{{ route('fg-psn.dashboard') }}" class="space-y-4">
            <div class="grid grid-cols-1 gap-4">
                <div>
                    <label class="block mb-1.5 text-sm font-semibold text-gray-600">Tanggal Mulai</label>
                    <input type="date" name="tanggal_mulai" value="{{ request('tanggal_mulai', now()->format('Y-m-d')) }}" 
                           class="bg-white border border-gray-300 text-gray-900 text-sm rounded-xl focus:ring-blue-500 focus:border-blue-500 block w-full p-3 shadow-sm transition-all">
                </div>
                <div>
                    <label class="block mb-1.5 text-sm font-semibold text-gray-600">Tanggal Selesai</label>
                    <input type="date" name="tanggal_selesai" value="{{ request('tanggal_selesai', now()->format('Y-m-d')) }}" 
                           class="bg-white border border-gray-300 text-gray-900 text-sm rounded-xl focus:ring-blue-500 focus:border-blue-500 block w-full p-3 shadow-sm transition-all">
                </div>
                <div>
                    <label class="block mb-1.5 text-sm font-semibold text-gray-600">Pilih</label>
                    <select name="produk" class="bg-white border border-gray-300 text-gray-900 text-sm rounded-xl focus:ring-blue-500 focus:border-blue-500 block w-full p-3 shadow-sm transition-all">
                        <option value="">Semua</option>
                        @foreach($produks as $p)
                            <option value="{{ $p->id }}" {{ request('produk') == $p->id ? 'selected' : '' }}>{{ $p->nama_produk }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            
            <div class="grid grid-cols-2 gap-3">
                <button type="submit" class="w-full py-3.5 px-4 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-xl transition-all shadow-lg shadow-blue-500/20">
                    Filter
                </button>
                <a href="{{ route('fg-psn.export', request()->all()) }}" 
                   class="w-full py-3.5 px-4 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl text-center transition-all shadow-lg shadow-emerald-500/20">
                    CSV
                </a>
            </div>

            <div class="flex">
                <a href="{{ route('fg-psn.dashboard') }}" 
                   class="px-6 py-2.5 bg-gray-50 border border-gray-300 text-gray-600 font-bold rounded-xl text-sm hover:bg-gray-100 transition-all">
                    Reset
                </a>
            </div>
        </form>
    </div>

    <!-- Table -->
    <div class="relative overflow-x-auto shadow-lg rounded-xl border border-gray-200">
        <table class="w-full text-sm text-left text-gray-600">
            <thead class="text-xs text-gray-500 uppercase bg-white/90">
                <tr>
                    <th class="px-4 py-3">No.</th>
                    <th class="px-4 py-3">Tanggal</th>
                    <th class="px-4 py-3">Produk</th>
                    <th class="px-4 py-3">Operator</th>
                    <th class="px-4 py-3">Kode Produksi</th>
                    <th class="px-4 py-3">Expired</th>
                    <th class="px-4 py-3">Berat</th>
                    <th class="px-4 py-3">Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach($penimbangans as $p)
                <tr class="bg-white/50 border-b border-gray-200 hover transition-colors">
                    <td class="px-4 py-3 text-gray-400 font-bold">{{ ($penimbangans->currentPage() - 1) * $penimbangans->perPage() + $loop->iteration }}</td>
                    <td class="px-4 py-3 text-gray-800 font-medium">{{ $p->created_at->format('d/m/Y H:i:s') }}</td>
                    <td class="px-4 py-3 font-medium text-gray-800">{{ $p->produk->nama_produk }}</td>
                    <td class="px-4 py-3">{{ $p->user->name }}</td>
                    <td class="px-4 py-3"><span class="font-mono text-xs text-blue-600">{{ $p->kode_produksi_display ?? $p->kode_produksi }}</span></td>
                    <td class="px-4 py-3">{{ $p->tanggal_expired ? $p->tanggal_expired->format('d/m/Y') : '-' }}</td>
                    <td class="px-4 py-3 font-bold text-gray-800">{{ number_format($p->berat, 3) }} kg</td>
                    <td class="px-4 py-3">
                        @if($p->status == 'selesai')
                            <span class="bg-green-50 text-green-600 text-xs font-medium px-2.5 py-0.5 rounded-lg border border-green-200">Selesai</span>
                        @elseif($p->status == 'menunggu')
                            <span class="bg-yellow-500/20 text-yellow-400 text-xs font-medium px-2.5 py-0.5 rounded-lg border border-yellow-500/30">Menunggu</span>
                        @else
                            <span class="bg-red-500/20 text-red-400 text-xs font-medium px-2.5 py-0.5 rounded-lg border border-red-500/30">Invalid</span>
                        @endif
                    </td>
                </tr>
                @endforeach
                @if($penimbangans->isEmpty())
                <tr><td colspan="7" class="px-4 py-8 text-center text-gray-400">Belum ada data.</td></tr>
                @endif
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $penimbangans->links() }}</div>
</div>
@endsection
