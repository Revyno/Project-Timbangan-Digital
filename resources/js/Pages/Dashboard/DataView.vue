<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import {
    Card,
    CardContent,
    CardHeader,
    CardTitle
} from '@/components/ui/card';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow
} from '@/components/ui/table';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Weight,
    Calendar,
    Filter,
    FileDown,
    RotateCcw,
    ChevronLeft,
    ChevronRight,
    Search
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
    // Incoming Singkong-specific
    jenisOptions:    { type: Array, default: () => [] },
    supplierOptions: { type: Array, default: () => [] },
});

const filterForm = ref({
    tanggal_mulai:   props.filters.tanggal_mulai   || new Date().toISOString().split('T')[0],
    tanggal_selesai: props.filters.tanggal_selesai || new Date().toISOString().split('T')[0],
    produk:          props.filters.produk          || '',
    shift:           props.filters.shift           || '',
    operator:        props.filters.operator        || '',
    supplier:        props.filters.supplier        || '',
    jenis:           props.filters.jenis           || '',
});

const applyFilter = () => {
    router.get(window.location.pathname, filterForm.value, { preserveState: true });
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
    return new Date(date).toLocaleTimeString('id-ID', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit', second: '2-digit' });
};
</script>

<template>
    <Head :title="title" />

    <AuthenticatedLayout>
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-2xl font-black text-gray-800">{{ title }}</h2>
                    <p class="mt-1 text-sm text-gray-500">{{ subtitle }}</p>
                </div>
                <div class="flex flex-wrap items-center gap-4">
                    <Card class="flex items-center p-3 transition-colors bg-white border border-gray-200 rounded-lg shadow-sm hover:bg-gray-50">
                        <div class="p-2 mr-3 bg-green-100 rounded-full">
                            <Calendar class="w-4 h-4 text-green-600" />
                        </div>
                        <div>
                            <p class="text-xs font-medium text-gray-500 uppercase">Total Items</p>
                            <p class="text-sm font-bold text-gray-900">{{ stats.total }} items</p>
                        </div>
                    </Card>

                    <Card class="flex items-center p-3 transition-colors bg-white border border-gray-200 rounded-lg shadow-sm hover:bg-gray-50">
                        <div class="p-2 mr-3 bg-blue-100 rounded-full">
                            <Weight class="w-4 h-4 text-blue-600" />
                        </div>
                        <div>
                            <p class="text-xs font-medium text-gray-500 uppercase">Total Berat</p>
                            <p class="text-sm font-bold text-gray-900">{{ formatWeight(stats.total_berat) }}</p>
                        </div>
                    </Card>
                </div>
            </div>

            <!-- Filters -->
            <Card class="p-6 bg-white border border-gray-200 shadow-sm rounded-2xl">
                <div class="grid grid-cols-1 gap-4 mb-4 md:grid-cols-3">
                    <div>
                        <label class="block mb-1.5 text-sm font-semibold text-gray-600">Tanggal Mulai</label>
                        <input v-model="filterForm.tanggal_mulai" type="date" class="block w-full p-3 text-sm text-gray-900 transition-all bg-white border border-gray-300 shadow-sm rounded-xl focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <div>
                        <label class="block mb-1.5 text-sm font-semibold text-gray-600">Tanggal Selesai</label>
                        <input v-model="filterForm.tanggal_selesai" type="date" class="block w-full p-3 text-sm text-gray-900 transition-all bg-white border border-gray-300 shadow-sm rounded-xl focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <!-- Filter Produk (FG modules) -->
                    <div v-if="produks && produks.length > 0">
                        <label class="block mb-1.5 text-sm font-semibold text-gray-600">Produk</label>
                        <select v-model="filterForm.produk" class="block w-full p-3 text-sm text-gray-900 transition-all bg-white border border-gray-300 shadow-sm rounded-xl focus:ring-blue-500 focus:border-blue-500">
                            <option value="">Semua</option>
                            <option v-for="p in produks" :key="p.id" :value="p.id">{{ p.nama_produk }}</option>
                        </select>
                    </div>
                    <!-- filter  shift  -->
                    <div v-if="shifts && shifts.length > 0">
                        <label class="block mb-1.5 text-sm font-semibold text-gray-600">Shift</label>
                        <select v-model="filterForm.shift" class="block w-full p-3 text-sm text-gray-900 transition-all bg-white border border-gray-300 shadow-sm rounded-xl focus:ring-blue-500 focus:border-blue-500">
                            <option value="">Semua Shift</option>
                            <option v-for="s in shifts" :key="s" :value="s">{{ s }}</option>
                        </select>
                    </div>

                    <!-- Filter Operator -->
                    <div v-if="operators && operators.length > 0">
                        <label class="block mb-1.5 text-sm font-semibold text-gray-600">Operator</label>
                        <select v-model="filterForm.operator" class="block w-full p-3 text-sm text-gray-900 transition-all bg-white border border-gray-300 shadow-sm rounded-xl focus:ring-blue-500 focus:border-blue-500">
                            <option value="">Semua Operator</option>
                            <option v-for="op in operators" :key="op.id" :value="op.id">{{ op.name }}</option>
                        </select>
                    </div>

                    <!-- Filter Supplier (Singkong) -->
                    <div v-if="supplierOptions && supplierOptions.length > 0">
                        <label class="block mb-1.5 text-sm font-semibold text-gray-600">Supplier</label>
                        <select v-model="filterForm.supplier" class="block w-full p-3 text-sm text-gray-900 transition-all bg-white border border-gray-300 shadow-sm rounded-xl focus:ring-blue-500 focus:border-blue-500">
                            <option value="">Semua Supplier</option>
                            <option v-for="s in supplierOptions" :key="s" :value="s">{{ s }}</option>
                        </select>
                    </div>

                    <!-- Filter Jenis Singkong -->
                    <div v-if="jenisOptions && jenisOptions.length > 0">
                        <label class="block mb-1.5 text-sm font-semibold text-gray-600">Jenis Singkong</label>
                        <select v-model="filterForm.jenis" class="block w-full p-3 text-sm text-gray-900 transition-all bg-white border border-gray-300 shadow-sm rounded-xl focus:ring-blue-500 focus:border-blue-500">
                            <option value="">Semua Jenis</option>
                            <option v-for="j in jenisOptions" :key="j" :value="j">{{ j }}</option>
                        </select>
                    </div>
                </div>

                <div class="flex flex-col gap-3 sm:flex-row">
                    <Button @click="applyFilter" class="flex-1 py-6 font-bold text-white bg-blue-600 hover:bg-blue-700 rounded-xl">
                        <Filter class="w-4 h-4 mr-2" />
                        Filter Data
                    </Button>
                    <Button as="a" :href="route(exportRoute, filterForm)" class="flex-1 py-6 font-bold text-white bg-emerald-600 hover:bg-emerald-700 rounded-xl">
                        <FileDown class="w-4 h-4 mr-2" />
                        Export CSV
                    </Button>
                    <Button @click="resetFilter" variant="outline" class="px-8 py-6 font-bold text-gray-600 border-gray-300 rounded-xl">
                        <RotateCcw class="w-4 h-4 mr-2" />
                        Reset
                    </Button>
                </div>
            </Card>

            <!-- Table -->
            <div class="overflow-hidden bg-white border border-gray-200 shadow-sm rounded-2xl">
                <Table>
                    <TableHeader class="bg-gray-50">
                        <TableRow>
                            <TableHead class="px-4 py-3">No.</TableHead>
                            <TableHead class="px-4 py-3">Tanggal</TableHead>
                            <!-- Singkong specific columns -->
                            <template v-if="jenisOptions && jenisOptions.length > 0">
                                <TableHead class="px-4 py-3">No Surat</TableHead>
                                <TableHead class="px-4 py-3">Supplier</TableHead>
                                <TableHead class="px-4 py-3">Asal</TableHead>
                                <TableHead class="px-4 py-3">Jenis</TableHead>
                                <TableHead class="px-4 py-3">Sopir</TableHead>
                                <TableHead class="px-4 py-3">No. Plat</TableHead>
                            </template>
                            <!-- Generic FG columns -->
                            <template v-else>
                                <TableHead class="px-4 py-3">Produk</TableHead>
                                <TableHead class="px-4 py-3">Kode Produksi</TableHead>
                                <TableHead class="px-4 py-3">Expired</TableHead>
                            </template>
                            <TableHead class="px-4 py-3">Operator</TableHead>
                            <TableHead class="px-4 py-3">Berat</TableHead>
                            <TableHead class="px-4 py-3">Status</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-for="(p, index) in penimbangans.data" :key="p.id" class="transition-colors hover:bg-gray-50">
                            <TableCell class="px-4 py-3 font-bold text-gray-400">
                                {{ (penimbangans.current_page - 1) * penimbangans.per_page + index + 1 }}
                            </TableCell>
                            <TableCell class="px-4 py-3 font-medium text-gray-800 whitespace-nowrap">{{ formatDateTime(p.created_at) }}</TableCell>

                            <!-- Singkong specific cells -->
                            <template v-if="jenisOptions && jenisOptions.length > 0">
                                <TableCell class="px-4 py-3 font-mono text-xs">{{ p.no_surat }}</TableCell>
                                <TableCell class="px-4 py-3 font-bold text-gray-800">{{ p.nama_supplier }}</TableCell>
                                <TableCell class="px-4 py-3">{{ p.asal }}</TableCell>
                                <TableCell class="px-4 py-3">{{ p.jenis_singkong }}</TableCell>
                                <TableCell class="px-4 py-3">{{ p.nama_sopir }}</TableCell>
                                <TableCell class="px-4 py-3 font-mono text-xs">{{ p.nomor_plat }}</TableCell>
                            </template>
                            <!-- Generic FG cells -->
                            <template v-else>
                                <TableCell class="px-4 py-3 font-medium text-gray-800">{{ p.produk?.nama_produk }}</TableCell>
                                <TableCell class="px-4 py-3">
                                    <span class="px-2 py-1 font-mono text-xs text-blue-500 rounded bg-blue-50">
                                        {{ p.kode_produksi_display || p.kode_produksi }}
                                    </span>
                                </TableCell>
                                <TableCell class="px-4 py-3 whitespace-nowrap">{{ formatDate(p.tanggal_expired) }}</TableCell>
                            </template>

                            <TableCell class="px-4 py-3">
                                <div>{{ p.user?.name }}</div>
                                <div v-if="p.user?.shift" class="text-xs text-gray-500">Shift {{ p.user.shift }}</div>
                            </TableCell>
                            <TableCell class="px-4 py-3 font-bold text-gray-800 whitespace-nowrap">{{ formatWeight(p.berat) }}</TableCell>
                            <TableCell class="px-4 py-3">
                                <Badge v-if="p.status == 'selesai'" class="text-green-700 bg-green-100 border-green-200 hover:bg-green-100">Selesai</Badge>
                                <Badge v-else class="text-yellow-700 bg-yellow-100 border-yellow-200 hover:bg-yellow-100">Menunggu</Badge>
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>

                <div v-if="penimbangans.data.length === 0" class="p-12 text-center text-gray-400">
                    Belum ada data.
                </div>

                <!-- Pagination -->
                <div class="flex items-center justify-between px-6 py-4 border-t border-gray-100">
                    <p class="text-sm text-gray-500">
                        Showing {{ penimbangans.from }} to {{ penimbangans.to }} of {{ penimbangans.total }} results
                    </p>
                    <div class="flex gap-2">
                        <Button :disabled="!penimbangans.prev_page_url" @click="router.get(penimbangans.prev_page_url)" variant="outline" size="sm">
                            <ChevronLeft class="w-4 h-4 mr-1" /> Previous
                        </Button>
                        <Button :disabled="!penimbangans.next_page_url" @click="router.get(penimbangans.next_page_url)" variant="outline" size="sm">
                            Next <ChevronRight class="w-4 h-4 ml-1" />
                        </Button>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
