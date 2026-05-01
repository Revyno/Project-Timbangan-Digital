@extends('layouts.app')

@section('content')
<div class="space-y-8">
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-3xl font-black text-gray-900 tracking-tight">Overview Panel</h1>
            <p class="text-gray-500 font-medium">Real-time overall operational summary across all locations.</p>
        </div>
        <div class="flex flex-col md:flex-row items-start md:items-center gap-4">
            <div class="flex items-center gap-3">
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
             <a href="{{ route('penimbangan.export', request()->all()) }}" class="inline-flex items-center justify-center px-5 py-3 text-sm font-medium text-center text-white bg-blue-700 rounded-lg hover:bg-blue-800 focus:ring-4 focus:ring-blue-300 dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800 transition-all">
                <svg class="w-4 h-4 mr-2" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 20 20">
                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 13V4M7 14H5a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v9a1 1 0 0 1-1 1H7Zm1-11V2a1 1 0 0 0-1-1H7a1 1 0 0 0-1 1v1h2Z"/>
                </svg>
                Export CSV
            </a>
        </div>
    </div>

    <!-- Global Summary Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
        <!-- Flowbite Card 1 -->
        <div class="block p-6 bg-white border border-gray-200 rounded-lg shadow hover:bg-gray-50 dark:bg-gray-800 dark:border-gray-700 dark:hover:bg-gray-700 transition-all flex items-center gap-4">
            <div class="w-16 h-16 bg-blue-50 text-blue-600 rounded-lg flex items-center justify-center shrink-0 dark:bg-blue-900 dark:text-blue-300">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>
            </div>
            <div>
                <h5 class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-1 dark:text-gray-500">Total Penimbangan</h5>
                <div class="flex items-baseline gap-1">
                    <p class="text-3xl font-black text-gray-900 leading-none dark:text-white">{{ number_format($stats['total']) }}</p>
                    <span class="text-xs font-bold text-gray-400 uppercase dark:text-gray-500">Items</span>
                </div>
            </div>
        </div>

        <!-- Flowbite Card 2 -->
        <div class="block p-6 bg-white border border-gray-200 rounded-lg shadow hover:bg-gray-50 dark:bg-gray-800 dark:border-gray-700 dark:hover:bg-gray-700 transition-all flex items-center gap-4">
            <div class="w-16 h-16 bg-emerald-50 text-emerald-600 rounded-lg flex items-center justify-center shrink-0 dark:bg-emerald-900 dark:text-emerald-300">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3"/></svg>
            </div>
            <div>
                <h5 class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-1 dark:text-gray-500">Total Berat</h5>
                <div class="flex items-baseline gap-1">
                    <p class="text-3xl font-black text-gray-900 leading-none dark:text-white">{{ number_format($stats['total_berat'], 3) }}</p>
                    <span class="text-xs font-bold text-gray-400 uppercase dark:text-gray-500">KG</span>
                </div>
            </div>
        </div>

        <!-- Flowbite Card 3 -->
        <div class="block p-6 bg-white border border-gray-200 rounded-lg shadow hover:bg-gray-50 dark:bg-gray-800 dark:border-gray-700 dark:hover:bg-gray-700 transition-all flex items-center gap-4">
            @php
                $successRate = $stats['total'] > 0 ? ($stats['selesai'] / $stats['total']) * 100 : 0;
            @endphp
            <div class="w-16 h-16 bg-indigo-50 text-indigo-600 rounded-lg flex items-center justify-center shrink-0 dark:bg-indigo-900 dark:text-indigo-300">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <div>
                <h5 class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-1 dark:text-gray-500">Tingkat Berhasil</h5>
                <p class="text-3xl font-black text-gray-900 leading-none dark:text-white">{{ number_format($successRate, 1) }}%</p>
                <span class="text-[10px] font-bold text-indigo-600 mt-1 block tracking-tight dark:text-indigo-400">({{ $stats['selesai'] }}/{{ $stats['total'] }})</span>
            </div>
        </div>

        <!-- Flowbite Card 4 -->
        <div class="block p-6 bg-white border border-gray-200 rounded-lg shadow hover:bg-gray-50 dark:bg-gray-800 dark:border-gray-700 dark:hover:bg-gray-700 transition-all flex items-center gap-4">
            <div class="w-16 h-16 bg-rose-50 text-rose-600 rounded-lg flex items-center justify-center shrink-0 dark:bg-rose-900 dark:text-rose-300">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <div>
                <h5 class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-1 dark:text-gray-500">Status Invalid</h5>
                <div class="flex items-baseline gap-1">
                    <p class="text-3xl font-black text-gray-900 leading-none dark:text-white">{{ number_format($stats['invalid']) }}</p>
                    <span class="text-xs font-bold text-rose-500 uppercase dark:text-rose-400">Records</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Module Breakdown Section -->
    <div class="space-y-4">
        <div class="flex items-center gap-3">
            <div class="h-8 w-1.5 bg-blue-600 rounded-full"></div>
            <h2 class="text-xl font-black text-gray-900 uppercase tracking-tight dark:text-white">Breakdown Per Modul</h2>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($moduleNames as $type => $name)
                @php
                    $mStats = $moduleStats->get($type);
                @endphp
                <!-- Flowbite Module Card -->
                <div class="max-w-sm p-6 bg-white border border-gray-200 rounded-lg shadow dark:bg-gray-800 dark:border-gray-700 flex flex-col justify-between hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                    <div>
                        <div class="flex justify-between items-start mb-4">
                            <h3 class="text-lg font-black text-gray-800 leading-tight dark:text-white">{{ $name }}</h3>
                            <span class="bg-blue-100 text-blue-800 text-[10px] font-black px-2.5 py-0.5 rounded-full dark:bg-blue-900 dark:text-blue-300 uppercase">{{ $type }}</span>
                        </div>
                        
                        <div class="space-y-2">
                            <!-- Compact Flowbite Style Card for Items -->
                            <div class="flex items-center p-2.5 bg-gray-50 border border-gray-100 rounded-lg dark:bg-gray-700 dark:border-gray-600">
                                <div class="w-8 h-8 bg-green-100 text-green-600 rounded-lg flex items-center justify-center shrink-0 mr-3 dark:bg-green-900 dark:text-green-300">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>
                                </div>
                                <div>
                                    <p class="text-[9px] font-bold text-gray-400 uppercase tracking-widest dark:text-gray-500">Penimbangan</p>
                                    <p class="text-sm font-black text-gray-900 dark:text-white">{{ number_format($mStats ? $mStats->total : 0) }} <span class="text-[9px] text-gray-400">Items</span></p>
                                </div>
                            </div>

                            <!-- Compact Flowbite Style Card for Weight -->
                            <div class="flex items-center p-2.5 bg-gray-50 border border-gray-100 rounded-lg dark:bg-gray-700 dark:border-gray-600">
                                <div class="w-8 h-8 bg-blue-100 text-blue-600 rounded-lg flex items-center justify-center shrink-0 mr-3 dark:bg-blue-900 dark:text-blue-300">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3"/></svg>
                                </div>
                                <div>
                                    <p class="text-[9px] font-bold text-gray-400 uppercase tracking-widest dark:text-gray-500">Total Berat</p>
                                    <p class="text-sm font-black text-gray-900 dark:text-white">{{ number_format($mStats ? $mStats->total_berat : 0, 3) }} <span class="text-[9px] text-gray-400">KG</span></p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="mt-4 pt-3 border-t border-gray-100 dark:border-gray-600 flex items-center justify-between">
                         <div class="flex items-center">
                            <span class="flex w-2 h-2 bg-blue-600 rounded-full mr-1.5"></span>
                            <span class="text-[10px] font-bold text-blue-600 uppercase dark:text-blue-400">Live Status</span>
                         </div>
                         @php
                            $routeMap = [
                                'fg' => 'fg.dashboard',
                                'fg_psn' => 'fg-psn.dashboard',
                                'fg_surabaya' => 'fg-surabaya.dashboard',
                                'cs_noodle_sby' => 'cs-noodle-sby.dashboard',
                                'cs_fg_sby' => 'cs-fg-sby.dashboard',
                                'incoming_singkong' => 'incoming.singkong.dashboard',
                                'incoming_rmpm' => 'incoming.rmpm.dashboard',
                            ];
                            $targetRoute = $routeMap[$type] ?? 'dashboard';
                         @endphp
                         <a href="{{ Route::has($targetRoute) ? route($targetRoute) : '#' }}" class="text-[10px] font-bold text-gray-500 hover:text-blue-600 uppercase transition-colors">View Details →</a>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <!-- Grafik Surabaya Modules -->
    <!-- <div class="space-y-4 mt-8">
        <div class="flex items-center gap-3">
            <div class="h-8 w-1.5 bg-indigo-500 rounded-full"></div>
            <h2 class="text-xl font-black text-gray-900 uppercase tracking-tight">Grafik Penimbangan Surabaya</h2>
        </div>
        <div class="bg-white border border-gray-100 rounded-3xl p-6 shadow-sm">
            <div id="surabayaChart"></div>
        </div>
    </div> -->

    <!-- Recent Activity Table -->
    <div class="space-y-4">
        <div class="flex items-center gap-3">
            <div class="h-8 w-1.5 bg-gray-800 rounded-full"></div>
            <h2 class="text-xl font-black text-gray-900 uppercase tracking-tight">Aktivitas Terakhir (Semua Modul)</h2>
        </div>

        <div class="bg-white border border-gray-100 rounded-3xl shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="text-[10px] font-black text-gray-400 uppercase tracking-widest bg-gray-50">
                        <tr>
                            <th class="px-6 py-4">No.</th>
                            <th class="px-6 py-4">Waktu</th>
                            <th class="px-6 py-4">Modul</th>
                            <th class="px-6 py-4">Produk</th>
                            <th class="px-6 py-4">Operator</th>
                            <th class="px-6 py-4">Berat</th>
                            <th class="px-6 py-4 text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @foreach($recentPenimbangans as $p)
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4 font-bold text-gray-400">{{ $loop->iteration }}</td>
                            <td class="px-6 py-4 whitespace-nowrap font-medium text-gray-500">
                                {{ $p->created_at->format('H:i:s') }}
                                <span class="block text-[10px] text-gray-300">{{ $p->created_at->format('d/m/Y') }}</span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="px-2 py-1 bg-gray-100 text-[10px] font-bold text-gray-600 rounded-md uppercase">
                                    {{ $moduleNames[$p->user->tipe] ?? $p->user->tipe }}
                                </span>
                            </td>
                            <td class="px-6 py-4 font-bold text-gray-900">{{ $p->produk->nama_produk }}</td>
                            <td class="px-6 py-4 text-gray-600">
                                {{ $p->user->name }}
                                <span class="block text-[10px] text-gray-400 italic">Shift {{ $p->user->shift ?? '-' }}</span>
                            </td>
                            <td class="px-6 py-4 font-black text-gray-900">
                                {{ number_format($p->berat, 3) }} <span class="text-[10px] font-normal text-gray-400 uppercase">kg</span>
                            </td>
                            <td class="px-6 py-4 text-center">
                                @if($p->status == 'selesai')
                                    <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-emerald-50 text-emerald-600">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                    </span>
                                @elseif($p->status == 'menunggu')
                                    <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-amber-50 text-amber-600 animate-pulse">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 8v4l3 3"/></svg>
                                    </span>
                                @else
                                    <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-rose-50 text-rose-600">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12"/></svg>
                                    </span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if($recentPenimbangans->isEmpty())
                <div class="p-12 text-center">
                    <p class="text-gray-400 font-medium italic">Belum ada aktivitas penimbangan hari ini.</p>
                </div>
            @endif
        </div>
    </div>
</div>


@endsection
