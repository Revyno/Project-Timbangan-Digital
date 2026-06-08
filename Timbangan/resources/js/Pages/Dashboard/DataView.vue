<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow
} from '@/Components/ui/table';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import {
    Pagination,
    PaginationContent,
    PaginationEllipsis,
    PaginationFirst,
    PaginationItem,
    PaginationLast,
    PaginationNext,
    PaginationPrevious,
} from '@/Components/ui/pagination';
import {
    Weight,
    Calendar,
    Filter,
    FileDown,
    RotateCcw,
    TrendingUp,
    Package,
    Users,
    Search,
    ChevronUp,
    ChevronDown,
} from 'lucide-vue-next';

const props = defineProps({
    title:           String,
    subtitle:        String,
    penimbangans:    Object,
    stats:           Object,
    produks:         Array,
    filters:         Object,
    exportRoute:     String,
    shifts:          Array,
    operators:       Array,
    jenisOptions:    { type: Array, default: () => [] },
    supplierOptions: { type: Array, default: () => [] },
});

const filterForm = ref({
    tanggal_mulai:   props.filters?.tanggal_mulai   || new Date().toISOString().split('T')[0],
    tanggal_selesai: props.filters?.tanggal_selesai || new Date().toISOString().split('T')[0],
    produk:          props.filters?.produk          || '',
    shift:           props.filters?.shift           || '',
    operator:        props.filters?.operator        || '',
    supplier:        props.filters?.supplier        || '',
    jenis:           props.filters?.jenis           || '',
});

const showFilters = ref(true);

const applyFilter = () => {
    router.get(window.location.pathname, filterForm.value, { preserveState: true, preserveScroll: true });
};

const resetFilter = () => {
    filterForm.value = {
        tanggal_mulai:   new Date().toISOString().split('T')[0],
        tanggal_selesai: new Date().toISOString().split('T')[0],
        produk:          '',
        shift:           '',
        operator:        '',
        supplier:        '',
        jenis:           '',
    };
    applyFilter();
};

const formatWeight = (weight) => {
    return new Intl.NumberFormat('id-ID', { minimumFractionDigits: 3, maximumFractionDigits: 3 }).format(weight) + ' kg';
};

const formatDate = (date) => {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('id-ID', { day: '2-digit', month: '2-digit', year: 'numeric' });
};

const formatDateTime = (date) => {
    return new Date(date).toLocaleTimeString('id-ID', {
        day: '2-digit', month: '2-digit', year: 'numeric',
        hour: '2-digit', minute: '2-digit', second: '2-digit'
    });
};

const handlePageChange = (page) => {
    if (page) {
        router.get(window.location.pathname, { ...filterForm.value, page }, { preserveState: true, preserveScroll: true });
    }
};

const isSingkong = computed(() => props.jenisOptions && props.jenisOptions.length > 0);
</script>

<template>
    <Head :title="title" />

    <AuthenticatedLayout>
        <div class="space-y-6">

            <!-- ── Page Header ── -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-black text-gray-900 tracking-tight">{{ title }}</h1>
                    <p class="mt-1 text-sm text-gray-500">{{ subtitle }}</p>
                </div>
                <Button
                    as="a"
                    :href="route(exportRoute, filterForm)"
                    class="inline-flex items-center gap-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold px-5 py-2.5 rounded-xl shadow-md shadow-emerald-200 transition-all"
                >
                    <FileDown class="w-4 h-4" />
                    Export CSV
                </Button>
            </div>

            <!-- ── Stats Cards ── -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="bg-gradient-to-br from-indigo-600 to-violet-700 rounded-2xl p-5 text-white shadow-lg shadow-indigo-200">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-xs font-bold uppercase tracking-widest opacity-75">Total Penimbangan</span>
                        <div class="p-2 bg-white/20 rounded-lg">
                            <Package class="w-4 h-4" />
                        </div>
                    </div>
                    <p class="text-4xl font-black">{{ stats.total }}</p>
                    <p class="text-xs opacity-70 mt-1">Records dalam periode ini</p>
                </div>

                <div class="bg-gradient-to-br from-blue-600 to-cyan-600 rounded-2xl p-5 text-white shadow-lg shadow-blue-200">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-xs font-bold uppercase tracking-widest opacity-75">Total Berat</span>
                        <div class="p-2 bg-white/20 rounded-lg">
                            <Weight class="w-4 h-4" />
                        </div>
                    </div>
                    <p class="text-3xl font-black">{{ formatWeight(stats.total_berat) }}</p>
                    <p class="text-xs opacity-70 mt-1">Akumulasi berat selesai</p>
                </div>

                <div class="bg-gradient-to-br from-emerald-600 to-teal-600 rounded-2xl p-5 text-white shadow-lg shadow-emerald-200">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-xs font-bold uppercase tracking-widest opacity-75">Periode Filter</span>
                        <div class="p-2 bg-white/20 rounded-lg">
                            <Calendar class="w-4 h-4" />
                        </div>
                    </div>
                    <p class="text-sm font-bold">{{ filterForm.tanggal_mulai }}</p>
                    <p class="text-xs opacity-70 mt-0.5">s/d</p>
                    <p class="text-sm font-bold">{{ filterForm.tanggal_selesai }}</p>
                </div>
            </div>

            <!-- ── Filter Panel ── -->
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                <button
                    @click="showFilters = !showFilters"
                    class="w-full flex items-center justify-between px-6 py-4 hover:bg-gray-50 transition-colors"
                >
                    <span class="flex items-center gap-2 font-bold text-gray-800">
                        <Filter class="w-4 h-4 text-indigo-500" />
                        Filter & Pencarian
                    </span>
                    <ChevronUp v-if="showFilters" class="w-4 h-4 text-gray-400" />
                    <ChevronDown v-else class="w-4 h-4 text-gray-400" />
                </button>

                <div v-show="showFilters" class="px-6 pb-6 border-t border-gray-100">
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 pt-4">
                        <!-- Tanggal Mulai -->
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-1.5">Tanggal Mulai</label>
                            <input
                                v-model="filterForm.tanggal_mulai"
                                type="date"
                                class="block w-full p-2.5 text-sm text-gray-900 bg-gray-50 border border-gray-200 rounded-xl focus:ring-indigo-500 focus:border-indigo-500 transition-all"
                            >
                        </div>
                        <!-- Tanggal Selesai -->
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-1.5">Tanggal Selesai</label>
                            <input
                                v-model="filterForm.tanggal_selesai"
                                type="date"
                                class="block w-full p-2.5 text-sm text-gray-900 bg-gray-50 border border-gray-200 rounded-xl focus:ring-indigo-500 focus:border-indigo-500 transition-all"
                            >
                        </div>
                        <!-- Produk (FG) -->
                        <div v-if="produks && produks.length > 0">
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-1.5">Produk</label>
                            <select v-model="filterForm.produk" class="block w-full p-2.5 text-sm text-gray-900 bg-gray-50 border border-gray-200 rounded-xl focus:ring-indigo-500 focus:border-indigo-500 transition-all">
                                <option value="">Semua Produk</option>
                                <option v-for="p in produks" :key="p.id" :value="p.id">{{ p.nama_produk }}</option>
                            </select>
                        </div>
                        <!-- Shift -->
                        <div v-if="shifts && shifts.length > 0">
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-1.5">Shift</label>
                            <select v-model="filterForm.shift" class="block w-full p-2.5 text-sm text-gray-900 bg-gray-50 border border-gray-200 rounded-xl focus:ring-indigo-500 focus:border-indigo-500 transition-all">
                                <option value="">Semua Shift</option>
                                <option v-for="s in shifts" :key="s" :value="s">{{ s }}</option>
                            </select>
                        </div>
                        <!-- Operator -->
                        <div v-if="operators && operators.length > 0">
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-1.5">Operator</label>
                            <select v-model="filterForm.operator" class="block w-full p-2.5 text-sm text-gray-900 bg-gray-50 border border-gray-200 rounded-xl focus:ring-indigo-500 focus:border-indigo-500 transition-all">
                                <option value="">Semua Operator</option>
                                <option v-for="op in operators" :key="op.id" :value="op.id">{{ op.name }}</option>
                            </select>
                        </div>
                        <!-- Supplier (Singkong) -->
                        <div v-if="supplierOptions && supplierOptions.length > 0">
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-1.5">Supplier</label>
                            <select v-model="filterForm.supplier" class="block w-full p-2.5 text-sm text-gray-900 bg-gray-50 border border-gray-200 rounded-xl focus:ring-indigo-500 focus:border-indigo-500 transition-all">
                                <option value="">Semua Supplier</option>
                                <option v-for="s in supplierOptions" :key="s" :value="s">{{ s }}</option>
                            </select>
                        </div>
                        <!-- Jenis Singkong -->
                        <div v-if="jenisOptions && jenisOptions.length > 0">
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-1.5">Jenis Singkong</label>
                            <select v-model="filterForm.jenis" class="block w-full p-2.5 text-sm text-gray-900 bg-gray-50 border border-gray-200 rounded-xl focus:ring-indigo-500 focus:border-indigo-500 transition-all">
                                <option value="">Semua Jenis</option>
                                <option v-for="j in jenisOptions" :key="j" :value="j">{{ j }}</option>
                            </select>
                        </div>
                    </div>

                    <div class="flex flex-col sm:flex-row gap-3 mt-5">
                        <Button @click="applyFilter" class="flex-1 sm:flex-none px-8 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-xl shadow-md shadow-indigo-100 transition-all">
                            <Search class="w-4 h-4 mr-2" /> Terapkan Filter
                        </Button>
                        <Button @click="resetFilter" variant="outline" class="flex-1 sm:flex-none px-6 py-2.5 font-bold rounded-xl border-gray-200 text-gray-600">
                            <RotateCcw class="w-4 h-4 mr-2" /> Reset
                        </Button>
                    </div>
                </div>
            </div>

            <!-- ── Data Table ── -->
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                    <div>
                        <h2 class="text-base font-bold text-gray-900">Tabel Data</h2>
                        <p class="text-xs text-gray-400 mt-0.5">
                            Menampilkan {{ penimbangans.from || 0 }}–{{ penimbangans.to || 0 }} dari {{ penimbangans.total }} data
                        </p>
                    </div>
                    <Badge class="bg-indigo-50 text-indigo-700 border-none font-bold text-xs px-3 py-1">
                        Halaman {{ penimbangans.current_page }} / {{ penimbangans.last_page }}
                    </Badge>
                </div>

                <div class="overflow-x-auto">
                    <Table>
                        <TableHeader class="bg-gray-50">
                            <TableRow>
                                <TableHead class="px-4 py-3 text-xs font-bold text-gray-500 uppercase whitespace-nowrap">No.</TableHead>
                                <TableHead class="px-4 py-3 text-xs font-bold text-gray-500 uppercase whitespace-nowrap">Tanggal</TableHead>
                                <!-- Singkong columns -->
                                <template v-if="isSingkong">
                                    <TableHead class="px-4 py-3 text-xs font-bold text-gray-500 uppercase whitespace-nowrap">No Surat</TableHead>
                                    <TableHead class="px-4 py-3 text-xs font-bold text-gray-500 uppercase whitespace-nowrap">Supplier</TableHead>
                                    <TableHead class="px-4 py-3 text-xs font-bold text-gray-500 uppercase whitespace-nowrap">Asal</TableHead>
                                    <TableHead class="px-4 py-3 text-xs font-bold text-gray-500 uppercase whitespace-nowrap">Jenis</TableHead>
                                    <TableHead class="px-4 py-3 text-xs font-bold text-gray-500 uppercase whitespace-nowrap">Sopir</TableHead>
                                    <TableHead class="px-4 py-3 text-xs font-bold text-gray-500 uppercase whitespace-nowrap">No. Plat</TableHead>
                                </template>
                                <!-- FG columns -->
                                <template v-else>
                                    <TableHead class="px-4 py-3 text-xs font-bold text-gray-500 uppercase whitespace-nowrap">Produk</TableHead>
                                    <TableHead class="px-4 py-3 text-xs font-bold text-gray-500 uppercase whitespace-nowrap">Kode Produksi</TableHead>
                                    <TableHead class="px-4 py-3 text-xs font-bold text-gray-500 uppercase whitespace-nowrap">Expired</TableHead>
                                </template>
                                <TableHead class="px-4 py-3 text-xs font-bold text-gray-500 uppercase whitespace-nowrap">Operator</TableHead>
                                <TableHead class="px-4 py-3 text-xs font-bold text-gray-500 uppercase whitespace-nowrap">Berat</TableHead>
                                <TableHead class="px-4 py-3 text-xs font-bold text-gray-500 uppercase whitespace-nowrap">Status</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow
                                v-for="(p, index) in penimbangans.data"
                                :key="p.id"
                                class="hover:bg-indigo-50/30 transition-colors border-b border-gray-50"
                            >
                                <TableCell class="px-4 py-3.5 text-xs font-bold text-gray-400 whitespace-nowrap">
                                    {{ (penimbangans.current_page - 1) * penimbangans.per_page + index + 1 }}
                                </TableCell>
                                <TableCell class="px-4 py-3.5 text-xs text-gray-600 whitespace-nowrap">
                                    {{ formatDateTime(p.created_at) }}
                                </TableCell>

                                <!-- Singkong cells -->
                                <template v-if="isSingkong">
                                    <TableCell class="px-4 py-3.5 font-mono text-xs text-gray-700">{{ p.no_surat }}</TableCell>
                                    <TableCell class="px-4 py-3.5 font-bold text-gray-900 text-sm">{{ p.nama_supplier }}</TableCell>
                                    <TableCell class="px-4 py-3.5 text-sm text-gray-600">{{ p.asal }}</TableCell>
                                    <TableCell class="px-4 py-3.5">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-amber-100 text-amber-800">
                                            {{ p.jenis_singkong }}
                                        </span>
                                    </TableCell>
                                    <TableCell class="px-4 py-3.5 text-sm text-gray-700">{{ p.nama_sopir }}</TableCell>
                                    <TableCell class="px-4 py-3.5 font-mono text-xs text-gray-500">{{ p.nomor_plat }}</TableCell>
                                </template>
                                <!-- FG cells -->
                                <template v-else>
                                    <TableCell class="px-4 py-3.5 font-semibold text-gray-900 text-sm whitespace-nowrap">{{ p.produk?.nama_produk || '-' }}</TableCell>
                                    <TableCell class="px-4 py-3.5">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-lg font-mono text-xs bg-indigo-50 text-indigo-700 border border-indigo-100">
                                            {{ p.kode_produksi_display || p.kode_produksi || '-' }}
                                        </span>
                                    </TableCell>
                                    <TableCell class="px-4 py-3.5 text-sm text-gray-600 whitespace-nowrap">{{ formatDate(p.tanggal_expired) }}</TableCell>
                                </template>

                                <TableCell class="px-4 py-3.5">
                                    <div class="text-sm font-semibold text-gray-800">{{ p.user?.name || '-' }}</div>
                                    <div v-if="p.user?.shift" class="text-xs text-gray-400 mt-0.5">Shift {{ p.user.shift }}</div>
                                </TableCell>
                                <TableCell class="px-4 py-3.5 font-black text-gray-900 whitespace-nowrap">
                                    {{ formatWeight(p.berat) }}
                                </TableCell>
                                <TableCell class="px-4 py-3.5">
                                    <span v-if="p.status === 'selesai'" class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-700">
                                        ✓ Selesai
                                    </span>
                                    <span v-else class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-amber-100 text-amber-700">
                                        ⏳ Menunggu
                                    </span>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </div>

                <!-- Empty State -->
                <div v-if="penimbangans.data.length === 0" class="p-16 text-center">
                    <div class="w-16 h-16 bg-gray-100 rounded-2xl flex items-center justify-center mx-auto mb-4">
                        <Package class="w-8 h-8 text-gray-300" />
                    </div>
                    <p class="text-gray-500 font-bold">Tidak ada data</p>
                    <p class="text-gray-400 text-sm mt-1">Coba ubah filter tanggal atau reset filter.</p>
                </div>

                <!-- Pagination -->
                <div v-if="penimbangans.last_page > 1" class="flex flex-col sm:flex-row items-center justify-between px-6 py-4 border-t border-gray-100 gap-4">
                    <p class="text-sm text-gray-500">
                        Menampilkan <span class="font-bold text-gray-700">{{ penimbangans.from }}</span>
                        – <span class="font-bold text-gray-700">{{ penimbangans.to }}</span>
                        dari <span class="font-bold text-gray-700">{{ penimbangans.total }}</span> data
                    </p>
                    <Pagination
                        :total="penimbangans.total"
                        :items-per-page="penimbangans.per_page"
                        :sibling-count="1"
                        show-edges
                        :default-page="penimbangans.current_page"
                        @update:page="handlePageChange"
                    >
                        <PaginationContent class="flex items-center gap-1">
                            <PaginationFirst />
                            <PaginationPrevious />

                            <template v-for="(item, index) in penimbangans.links.slice(1, -1)" :key="index">
                                <PaginationItem>
                                    <Button
                                        v-if="item.url"
                                        class="w-9 h-9 p-0 text-sm"
                                        :variant="item.active ? 'default' : 'outline'"
                                        @click="handlePageChange(Number(item.label))"
                                    >
                                        {{ item.label }}
                                    </Button>
                                    <PaginationEllipsis v-else />
                                </PaginationItem>
                            </template>

                            <PaginationNext />
                            <PaginationLast />
                        </PaginationContent>
                    </Pagination>
                </div>
            </div>

        </div>
    </AuthenticatedLayout>
</template>
