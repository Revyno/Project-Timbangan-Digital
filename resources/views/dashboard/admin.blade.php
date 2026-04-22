@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <!-- Meta refresh for 5 seconds polling if desired, or JS below -->
    <script>
        // Optional: Simple JS polling to refresh page every 5s if on dashboard
        // setTimeout(() => { window.location.reload(); }, 5000);
    </script>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="p-4 bg-white border border-gray-200 rounded-lg shadow-sm dark:bg-gray-800 dark:border-gray-700">
            <h5 class="text-xs font-medium text-gray-500 uppercase dark:text-gray-400">Total Penimbangan</h5>
            <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $stats['total'] }}</p>
        </div>
        <div class="p-4 bg-white border border-gray-200 rounded-lg shadow-sm dark:bg-gray-800 dark:border-gray-700">
            <h5 class="text-xs font-medium text-yellow-500 uppercase">Menunggu</h5>
            <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $stats['menunggu'] }}</p>
        </div>
        <div class="p-4 bg-white border border-gray-200 rounded-lg shadow-sm dark:bg-gray-800 dark:border-gray-700">
            <h5 class="text-xs font-medium text-green-500 uppercase">Selesai</h5>
            <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $stats['selesai'] }}</p>
        </div>
        <div class="p-4 bg-white border border-gray-200 rounded-lg shadow-sm dark:bg-gray-800 dark:border-gray-700">
            <h5 class="text-xs font-medium text-red-500 uppercase">Invalid</h5>
            <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $stats['invalid'] }}</p>
        </div>
    </div>

    <!-- Filters -->
    <div class="p-4 bg-white border border-gray-200 rounded-lg shadow-sm dark:bg-gray-800 dark:border-gray-700">
        <form method="GET" action="{{ route('dashboard') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
            <div>
                <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Tanggal</label>
                <input type="date" name="tanggal" value="{{ request('tanggal', now()->format('Y-m-d')) }}" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
            </div>
            <div>
                <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Shift</label>
                <select name="shift" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    <option value="">Semua Shift</option>
                    <option value="1" {{ request('shift') == '1' ? 'selected' : '' }}>Shift 1</option>
                    <option value="2" {{ request('shift') == '2' ? 'selected' : '' }}>Shift 2</option>
                    <option value="3" {{ request('shift') == '3' ? 'selected' : '' }}>Shift 3</option>
                </select>
            </div>
            <div>
                <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Produk</label>
                <select name="produk" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    <option value="">Semua Produk</option>
                    @foreach($produks as $p)
                        <option value="{{ $p->id }}" {{ request('produk') == $p->id ? 'selected' : '' }}>{{ $p->nama_produk }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex gap-2">
                <button type="submit" class="text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 dark:bg-blue-600 dark:hover:bg-blue-700 focus:outline-none dark:focus:ring-blue-800 w-full">Filter</button>
                <a href="{{ route('dashboard') }}" class="text-gray-900 bg-white border border-gray-300 focus:outline-none hover:bg-gray-100 focus:ring-4 focus:ring-gray-100 font-medium rounded-lg text-sm px-5 py-2.5 dark:bg-gray-800 dark:text-white dark:border-gray-600 dark:hover:bg-gray-700 dark:hover:border-gray-600 dark:focus:ring-gray-700">Reset</a>
            </div>
        </form>
    </div>

    <!-- Table -->
    <div class="relative overflow-x-auto shadow-md sm:rounded-lg">
        <table class="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400">
            <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                <tr>
                    <th scope="col" class="px-6 py-3">Produk</th>
                    <th scope="col" class="px-6 py-3">Pegawai</th>
                    <th scope="col" class="px-6 py-3">Shift</th>
                    <th scope="col" class="px-6 py-3">Kode Produksi</th>
                    <th scope="col" class="px-6 py-3">Tanggal</th>
                    <th scope="col" class="px-6 py-3">Expired</th>
                    <th scope="col" class="px-6 py-3">Berat</th>
                    <th scope="col" class="px-6 py-3">Selisih</th>
                    <th scope="col" class="px-6 py-3">Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach($penimbangans as $p)
                <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                    <td class="px-6 py-4 font-medium text-gray-900 dark:text-white">{{ $p->produk->nama_produk }}</td>
                    <td class="px-6 py-4">{{ $p->user->name }}</td>
                    <td class="px-6 py-4">Shift {{ $p->user->shift ?? '-' }}</td>
                    <td class="px-6 py-4">
                        <span class="font-mono text-xs">{{ $p->kode_produksi }}</span>
                    </td>
                    <td class="px-6 py-4">{{ \Carbon\Carbon::parse($p->tanggal_penimbangan)->format('d/m/Y') }}</td>
                    <td class="px-6 py-4">
                        @if($p->tanggal_expired)
                            {{ \Carbon\Carbon::parse($p->tanggal_expired)->format('d/m/Y') }}
                        @else
                            <span class="text-gray-400 italic">N/A</span>
                        @endif
                    </td>
                    <td class="px-6 py-4 font-bold text-gray-900 dark:text-white">
                      @if ($p->berat == 'kg')
                            <span class="bg-red-100 text-red-800 text-xs font-medium px-2.5 py-0.5 rounded dark:bg-red-900 dark:text-red-300">Kg</span>
                        @elseif ($p->berat != null)
                         <span class="bg-green-100 text-white text-xs font-medium px-2.5 py-0.5 rounded dark:bg-red-900 dark:text-white">Error</span>
                        @endif
                    </td>
                    <td class="px-6 py-4">
                        @if($p->selisih == 'kg')
                            <span class="bg-red-100 text-red-800 text-xs font-medium px-2.5 py-0.5 rounded dark:bg-red-900 dark:text-red-300">Kg</span>
                        @elseif ($p->selisih != null)
                            <span class="bg-green-100 text-white text-xs font-medium px-2.5 py-0.5 rounded dark:bg-red-900 dark:text-white">Error</span>
                        @elseif($p->selisih > 0)
                            <span class="text-green-600 font-medium">+{{ number_format($p->selisih, 3) }}</span>
                        @elseif($p->selisih < 0)
                            <span class="text-red-600 font-medium">{{ number_format($p->selisih, 3) }}</span>
                        <!-- @else
                            <span class="text-gray-500">0.000</span> -->
                        @endif
                    </td>
                    <td class="px-6 py-4">
                        @if($p->status == 'menunggu')
                            <span class="bg-yellow-100 text-yellow-800 text-xs font-medium px-2.5 py-0.5 rounded dark:bg-yellow-900 dark:text-yellow-300">Menunggu</span>
                        @elseif($p->status == 'selesai')
                            <span class="bg-green-100 text-green-800 text-xs font-medium px-2.5 py-0.5 rounded dark:bg-green-900 dark:text-green-300">Selesai</span>
                        @else
                            <span class="bg-red-100 text-red-800 text-xs font-medium px-2.5 py-0.5 rounded dark:bg-red-900 dark:text-red-300">Invalid</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>

        </table>
    </div>
    <div class="mt-4">
        {{ $penimbangans->links() }}
    </div>
</div>

<script>
    // Refresh page every 10 seconds to show latest updates from Arduino
    setTimeout(function(){
       window.location.reload();
    }, 10000);
</script>
@endsection
