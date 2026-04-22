@extends('layouts.app')

@section('content')
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Input Form -->
    <div class="lg:col-span-1 p-6 bg-white border border-gray-200 rounded-lg shadow-sm dark:bg-gray-800 dark:border-gray-700 h-fit">
        <div class="flex items-center justify-between mb-4">
            <h5 class="text-xl font-bold text-gray-900 dark:text-white">Penimbangan Baru</h5>
        </div>

        @if(session('success'))
            <div class="p-4 mb-4 text-sm text-green-800 rounded-lg bg-green-50 dark:bg-gray-800 dark:text-green-400" role="alert">
                {{ session('success') }}
            </div>
        @endif

        <form method="POST" action="{{ route('penimbangan.store') }}">
            @csrf
            <div class="mb-4">
                <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Produk (Bahan Baku)</label>
                <select name="produk_id" required class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    <option value="">Pilih Produk...</option>
                    @foreach($produks as $p)
                        <option value="{{ $p->id }}">{{ $p->nama_produk }}</option>
                    @endforeach
                </select>
            </div>

            <div class="mb-6">
                <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Tanggal Expired</label>
                <input type="date" name="tanggal_expired" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
            </div>

            <button type="submit" class="w-full text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800">Simpan & Tunggu Timbangan</button>
        </form>
    </div>

    <!-- History Table -->
    <div class="lg:col-span-2">
        <livewire:operator.history-table />
    </div>
</div>
@endsection

