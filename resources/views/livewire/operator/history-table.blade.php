<div wire:poll.5s>
    <h5 class="text-xl font-bold mb-4 text-gray-900 dark:text-white">Riwayat Penimbangan</h5>
    <div class="relative overflow-x-auto shadow-md sm:rounded-lg">
        <table class="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400">
            <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                <tr>
                    <th scope="col" class="px-6 py-3">Tanggal Penimbangan</th>
                    <th scope="col" class="px-6 py-3">Produk</th>
                    <th scope="col" class="px-6 py-3">Kode Produksi</th>
                    <th scope="col" class="px-6 py-3">Berat</th>
                    <th scope="col" class="px-6 py-3">Tanggal Expired</th>
                    <th scope="col" class="px-6 py-3">Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach($penimbangans as $p)
                <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                    <td class="px-6 py-4 font-medium text-gray-900 dark:text-white">{{ $p->created_at->format('d M Y H:i:s') }}</td>
                    <td class="px-6 py-4 font-medium text-gray-900 dark:text-white">{{ $p->produk->nama_produk }}</td>
                    <td class="px-6 py-4">
                        <span class="font-mono text-xs">{{ $p->kode_produksi_display }}</span>
                    </td>
                    <td class="px-6 py-4 font-bold text-gray-900 dark:text-white">
                        @if($p->berat > 0)
                            {{ number_format($p->berat, 3) }} <span class="text-xs font-normal text-gray-400">kg</span>
                        @else
                            <span class="text-gray-400 italic text-xs">Belum ditimbang</span>
                        @endif
                    </td>

                    <td class="px-6 py-4">
                        @if($p->tanggal_expired)
                            {{ \Carbon\Carbon::parse($p->tanggal_expired)->format('d M Y') }}
                        @else
                            <span class="text-gray-400 italic">Tidak ada</span>
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

    <!-- Flowbite Pagination -->
    <div class="mt-4">
        {{ $penimbangans->links('livewire.flowbite-pagination') }}
    </div>
</div>
