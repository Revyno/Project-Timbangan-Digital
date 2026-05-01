@extends('layouts.app')

@section('content')
<div class="space-y-8">
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-3xl font-black text-gray-900 tracking-tight">Personal Dashboard</h1>
            <p class="text-gray-500 font-medium">Hello, <span class="text-blue-600">{{ Auth::user()->name }}</span>. Here is your overall performance summary.</p>
        </div>
        <div class="flex items-center gap-3">
             <div class="px-4 py-2 bg-white border border-gray-100 rounded-xl shadow-sm text-xs font-bold text-gray-400 uppercase tracking-widest">
                Shift {{ Auth::user()->shift ?? '-' }}
             </div>
        </div>
    </div>

    <!-- Personal Summary Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
        <div class="p-6 bg-white border border-gray-100 rounded-3xl shadow-sm">
            <div class="w-12 h-12 bg-blue-50 text-blue-600 rounded-2xl flex items-center justify-center mb-4">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <h5 class="text-sm font-bold text-gray-400 uppercase tracking-widest mb-1">Total Penimbangan</h5>
            <p class="text-3xl font-black text-gray-900">{{ number_format($stats['total']) }}</p>
        </div>

        <div class="p-6 bg-white border border-gray-100 rounded-3xl shadow-sm">
            <div class="w-12 h-12 bg-emerald-50 text-emerald-600 rounded-2xl flex items-center justify-center mb-4">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3"/></svg>
            </div>
            <h5 class="text-sm font-bold text-gray-400 uppercase tracking-widest mb-1">Total Berat</h5>
            <p class="text-3xl font-black text-gray-900">{{ number_format($stats['total_berat'], 2) }} <span class="text-base font-medium text-gray-400">kg</span></p>
        </div>

        <div class="p-6 bg-white border border-gray-100 rounded-3xl shadow-sm">
            @php
                $successRate = $stats['total'] > 0 ? ($stats['selesai'] / $stats['total']) * 100 : 0;
            @endphp
            <div class="w-12 h-12 bg-indigo-50 text-indigo-600 rounded-2xl flex items-center justify-center mb-4">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <h5 class="text-sm font-bold text-gray-400 uppercase tracking-widest mb-1">Success Rate</h5>
            <div class="flex items-end gap-2">
                <p class="text-3xl font-black text-gray-900">{{ number_format($successRate, 1) }}%</p>
            </div>
        </div>

        <div class="p-6 bg-white border border-gray-100 rounded-3xl shadow-sm">
            <div class="w-12 h-12 bg-rose-50 text-rose-600 rounded-2xl flex items-center justify-center mb-4">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <h5 class="text-sm font-bold text-gray-400 uppercase tracking-widest mb-1">Invalid Records</h5>
            <p class="text-3xl font-black text-gray-900">{{ number_format($stats['invalid']) }}</p>
        </div>
    </div>

    <!-- Contribution per Module -->
    <div class="space-y-4">
        <!-- <div class="flex items-center gap-3">
            <div class="h-8 w-1.5 bg-blue-600 rounded-full"></div>
            <h2 class="text-xl font-black text-gray-900 uppercase tracking-tight">Your Contributions</h2>
        </div> -->
        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($moduleNames as $type => $name)
                @php
                    $mStats = $moduleStats->get($type);
                @endphp
                @if($mStats)
                <div class="bg-white border border-gray-100 rounded-3xl p-6 shadow-sm flex flex-col justify-between">
                    <div>
                        <div class="flex justify-between items-start mb-4">
                            <h3 class="text-lg font-black text-gray-800 leading-tight">{{ $name }}</h3>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Penimbangan</p>
                                <p class="text-xl font-black text-gray-900">{{ number_format($mStats->total) }}</p>
                            </div>
                            <div>
                                <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Berat (kg)</p>
                                <p class="text-xl font-black text-gray-900">{{ number_format($mStats->total_berat, 2) }}</p>
                            </div>
                        </div>
                    </div>
                </div>
                @endif
            @endforeach
        </div>
    </div>

    <!-- Recent Activity Table -->
    <div class="space-y-4">
        <div class="flex items-center gap-3">
            <div class="h-8 w-1.5 bg-gray-800 rounded-full"></div>
            <h2 class="text-xl font-black text-gray-900 uppercase tracking-tight">Your Recent Weighings</h2>
        </div>

        <div class="bg-white border border-gray-100 rounded-3xl shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="text-[10px] font-black text-gray-400 uppercase tracking-widest bg-gray-50">
                        <tr>
                            <th class="px-6 py-4">Time</th>
                            <th class="px-6 py-4">Product</th>
                            <th class="px-6 py-4">Weight</th>
                            <th class="px-6 py-4 text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @foreach($recentPenimbangans as $p)
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap font-medium text-gray-500">
                                {{ $p->created_at->format('H:i:s') }}
                                <span class="block text-[10px] text-gray-300">{{ $p->created_at->format('d/m/Y') }}</span>
                            </td>
                            <td class="px-6 py-4 font-bold text-gray-900">{{ $p->produk->nama_produk }}</td>
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
                    <p class="text-gray-400 font-medium italic">You haven't recorded any weighings yet.</p>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
