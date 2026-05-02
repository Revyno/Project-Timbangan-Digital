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
    title: String,
    subtitle: String,
    penimbangans: Object,
    stats: Object,
    produks: Array,
    filters: Object,
    exportRoute: String,
});

const filterForm = ref({
    tanggal_mulai: props.filters.tanggal_mulai || new Date().toISOString().split('T')[0],
    tanggal_selesai: props.filters.tanggal_selesai || new Date().toISOString().split('T')[0],
    produk: props.filters.produk || '',
});

const applyFilter = () => {
    router.get(window.location.pathname, filterForm.value, { preserveState: true });
};

const resetFilter = () => {
    filterForm.value = {
        tanggal_mulai: new Date().toISOString().split('T')[0],
        tanggal_selesai: new Date().toISOString().split('T')[0],
        produk: '',
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
                    <p class="text-sm text-gray-500 mt-1">{{ subtitle }}</p>
                </div>
                <div class="flex flex-wrap items-center gap-4">
                    <Card class="flex items-center p-3 bg-white border border-gray-200 rounded-lg shadow-sm hover:bg-gray-50 transition-colors">
                        <div class="p-2 mr-3 bg-green-100 rounded-full">
                            <Calendar class="w-4 h-4 text-green-600" />
                        </div>
                        <div>
                            <p class="text-xs font-medium text-gray-500 uppercase">Total Items</p>
                            <p class="text-sm font-bold text-gray-900">{{ stats.total }} items</p>
                        </div>
                    </Card>

                    <Card class="flex items-center p-3 bg-white border border-gray-200 rounded-lg shadow-sm hover:bg-gray-50 transition-colors">
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
            <Card class="p-6 bg-white border border-gray-200 rounded-2xl shadow-sm">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                    <div>
                        <label class="block mb-1.5 text-sm font-semibold text-gray-600">Tanggal Mulai</label>
                        <input v-model="filterForm.tanggal_mulai" type="date" class="bg-white border border-gray-300 text-gray-900 text-sm rounded-xl focus:ring-blue-500 focus:border-blue-500 block w-full p-3 shadow-sm transition-all">
                    </div>
                    <div>
                        <label class="block mb-1.5 text-sm font-semibold text-gray-600">Tanggal Selesai</label>
                        <input v-model="filterForm.tanggal_selesai" type="date" class="bg-white border border-gray-300 text-gray-900 text-sm rounded-xl focus:ring-blue-500 focus:border-blue-500 block w-full p-3 shadow-sm transition-all">
                    </div>
                    <div>
                        <label class="block mb-1.5 text-sm font-semibold text-gray-600">Produk</label>
                        <select v-model="filterForm.produk" class="bg-white border border-gray-300 text-gray-900 text-sm rounded-xl focus:ring-blue-500 focus:border-blue-500 block w-full p-3 shadow-sm transition-all">
                            <option value="">Semua</option>
                            <option v-for="p in produks" :key="p.id" :value="p.id">{{ p.nama_produk }}</option>
                        </select>
                    </div>
                </div>
                
                <div class="flex flex-col sm:flex-row gap-3">
                    <Button @click="applyFilter" class="bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-xl flex-1 py-6">
                        <Filter class="w-4 h-4 mr-2" />
                        Filter Data
                    </Button>
                    <Button as="a" :href="route(exportRoute, filterForm)" class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl flex-1 py-6">
                        <FileDown class="w-4 h-4 mr-2" />
                        Export CSV
                    </Button>
                    <Button @click="resetFilter" variant="outline" class="border-gray-300 text-gray-600 font-bold rounded-xl px-8 py-6">
                        <RotateCcw class="w-4 h-4 mr-2" />
                        Reset
                    </Button>
                </div>
            </Card>

            <!-- Table -->
            <div class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden">
                <Table>
                    <TableHeader class="bg-gray-50">
                        <TableRow>
                            <TableHead class="px-4 py-3">No.</TableHead>
                            <TableHead class="px-4 py-3">Tanggal</TableHead>
                            <TableHead class="px-4 py-3">Produk</TableHead>
                            <TableHead class="px-4 py-3">Operator</TableHead>
                            <TableHead class="px-4 py-3">Kode Produksi</TableHead>
                            <TableHead class="px-4 py-3">Expired</TableHead>
                            <TableHead class="px-4 py-3">Berat</TableHead>
                            <TableHead class="px-4 py-3">Status</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-for="(p, index) in penimbangans.data" :key="p.id" class="hover:bg-gray-50 transition-colors">
                            <TableCell class="px-4 py-3 text-gray-400 font-bold">
                                {{ (penimbangans.current_page - 1) * penimbangans.per_page + index + 1 }}
                            </TableCell>
                            <TableCell class="px-4 py-3 text-gray-800 font-medium whitespace-nowrap">{{ formatDateTime(p.created_at) }}</TableCell>
                            <TableCell class="px-4 py-3 font-medium text-gray-800">{{ p.produk.nama_produk }}</TableCell>
                            <TableCell class="px-4 py-3">{{ p.user?.name }}</TableCell>
                            <TableCell class="px-4 py-3">
                                <span class="font-mono text-xs text-blue-500 bg-blue-50 px-2 py-1 rounded">
                                    {{ p.kode_produksi_display || p.kode_produksi }}
                                </span>
                            </TableCell>
                            <TableCell class="px-4 py-3 whitespace-nowrap">{{ formatDate(p.tanggal_expired) }}</TableCell>
                            <TableCell class="px-4 py-3 font-bold text-gray-800 whitespace-nowrap">{{ formatWeight(p.berat) }}</TableCell>
                            <TableCell class="px-4 py-3">
                                <Badge v-if="p.status == 'selesai'" class="bg-green-100 text-green-700 border-green-200 hover:bg-green-100">Selesai</Badge>
                                <Badge v-else class="bg-yellow-100 text-yellow-700 border-yellow-200 hover:bg-yellow-100">Menunggu</Badge>
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
                
                <div v-if="penimbangans.data.length === 0" class="p-12 text-center text-gray-400">
                    Belum ada data.
                </div>

                <!-- Pagination -->
                <div class="px-6 py-4 border-t border-gray-100 flex items-center justify-between">
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
