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
    Scale,
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
import { formatWeight } from '@/utils/format';

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
    tanggal_mulai:   props.filters?.tanggal_mulai   || '',
    tanggal_selesai: props.filters?.tanggal_selesai || '',
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
        tanggal_mulai:   '',
        tanggal_selesai: '',
        produk:          '',
        shift:           '',
        operator:        '',
        supplier:        '',
        jenis:           '',
    };
    applyFilter();
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
                    <h1 class="text-2xl font-bold tracking-tight text-foreground">{{ title }}</h1>
                    <p class="mt-1 text-sm text-muted-foreground">{{ subtitle }}</p>
                </div>
                <Button
                    as="a"
                    :href="route(exportRoute, filterForm)"
                >
                    <FileDown class="w-4 h-4 mr-2" />
                    Export CSV
                </Button>
            </div>

            <!-- ── Stats Cards ── -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <Card class="bg-blue-50/50 border-blue-100 dark:bg-blue-950/20 dark:border-blue-900/50">
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-xs font-bold uppercase tracking-widest text-blue-800 dark:text-blue-300">Total Penimbangan</CardTitle>
                        <Package class="w-4 h-4 text-blue-500" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-3xl font-bold text-blue-950 dark:text-blue-50">{{ stats.total }}</div>
                        <p class="text-xs text-blue-600/80 dark:text-blue-300/80 mt-1">Records dalam periode ini</p>
                    </CardContent>
                </Card>

                <Card class="bg-indigo-50/50 border-indigo-100 dark:bg-indigo-950/20 dark:border-indigo-900/50">
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-xs font-bold uppercase tracking-widest text-indigo-800 dark:text-indigo-300">Total Berat</CardTitle>
                        <Scale class="w-4 h-4 text-indigo-500" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-3xl font-bold text-indigo-950 dark:text-indigo-50">{{ formatWeight(stats.total_berat) }} <span class="text-sm font-normal text-indigo-600/80 dark:text-indigo-300/80">kg</span></div>
                        <p class="text-xs text-indigo-600/80 dark:text-indigo-300/80 mt-1">Akumulasi berat selesai</p>
                    </CardContent>
                </Card>

                <Card class="bg-slate-50/80 border-slate-200 shadow-md dark:bg-slate-900/80 dark:border-slate-800">
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-xs font-bold uppercase tracking-widest text-slate-800 dark:text-slate-300">Periode Filter</CardTitle>
                        <Calendar class="w-4 h-4 text-slate-500" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-sm font-bold text-slate-900 dark:text-slate-50">
                            <template v-if="filterForm.tanggal_mulai && filterForm.tanggal_selesai">
                                {{ filterForm.tanggal_mulai }} s/d {{ filterForm.tanggal_selesai }}
                            </template>
                            <template v-else-if="filterForm.tanggal_mulai">
                                Sejak {{ filterForm.tanggal_mulai }}
                            </template>
                            <template v-else-if="filterForm.tanggal_selesai">
                                Hingga {{ filterForm.tanggal_selesai }}
                            </template>
                            <template v-else>
                                Semua Waktu
                            </template>
                        </div>
                        <p class="text-xs text-slate-500 mt-1">Rentang waktu terpilih</p>
                    </CardContent>
                </Card>
            </div>

            <!-- ── Filter Panel ── -->
            <Card class="bg-slate-50/80 border-slate-200 shadow-md dark:bg-slate-900/80 dark:border-slate-800">
                <button
                    @click="showFilters = !showFilters"
                    class="w-full flex items-center justify-between px-6 py-4 hover:bg-accent hover:text-accent-foreground transition-colors"
                >
                    <span class="flex items-center gap-2 font-semibold text-sm text-foreground">
                        <Filter class="w-4 h-4 text-primary" />
                        Filter & Pencarian
                    </span>
                    <ChevronUp v-if="showFilters" class="w-4 h-4 text-muted-foreground" />
                    <ChevronDown v-else class="w-4 h-4 text-muted-foreground" />
                </button>

                <div v-show="showFilters" class="px-6 pb-6 border-t border-border">
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 pt-4">
                        <!-- Tanggal Mulai -->
                        <div>
                            <label class="block text-xs font-semibold text-muted-foreground uppercase mb-1.5">Tanggal Mulai</label>
                            <input
                                v-model="filterForm.tanggal_mulai"
                                type="date"
                                class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm shadow-sm transition-colors focus:outline-none focus:ring-1 focus:ring-ring"
                            >
                        </div>
                        <!-- Tanggal Selesai -->
                        <div>
                            <label class="block text-xs font-semibold text-muted-foreground uppercase mb-1.5">Tanggal Selesai</label>
                            <input
                                v-model="filterForm.tanggal_selesai"
                                type="date"
                                class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm shadow-sm transition-colors focus:outline-none focus:ring-1 focus:ring-ring"
                            >
                        </div>
                        <!-- Produk (FG) -->
                        <div v-if="produks && produks.length > 0">
                            <label class="block text-xs font-semibold text-muted-foreground uppercase mb-1.5">Produk</label>
                            <select v-model="filterForm.produk" class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm shadow-sm transition-colors focus:outline-none focus:ring-1 focus:ring-ring">
                                <option value="">Semua Produk</option>
                                <option v-for="p in produks" :key="p.id" :value="p.id">{{ p.nama_produk }}</option>
                            </select>
                        </div>
                        <!-- Shift -->
                        <div v-if="shifts && shifts.length > 0">
                            <label class="block text-xs font-semibold text-muted-foreground uppercase mb-1.5">Shift</label>
                            <select v-model="filterForm.shift" class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm shadow-sm transition-colors focus:outline-none focus:ring-1 focus:ring-ring">
                                <option value="">Semua Shift</option>
                                <option v-for="s in shifts" :key="s" :value="s">{{ s }}</option>
                            </select>
                        </div>
                        <!-- Operator -->
                        <div v-if="operators && operators.length > 0">
                            <label class="block text-xs font-semibold text-muted-foreground uppercase mb-1.5">Operator</label>
                            <select v-model="filterForm.operator" class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm shadow-sm transition-colors focus:outline-none focus:ring-1 focus:ring-ring">
                                <option value="">Semua Operator</option>
                                <option v-for="op in operators" :key="op.id" :value="op.id">{{ op.name }}</option>
                            </select>
                        </div>
                        <!-- Supplier (Singkong) -->
                        <div v-if="supplierOptions && supplierOptions.length > 0">
                            <label class="block text-xs font-semibold text-muted-foreground uppercase mb-1.5">Supplier</label>
                            <select v-model="filterForm.supplier" class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm shadow-sm transition-colors focus:outline-none focus:ring-1 focus:ring-ring">
                                <option value="">Semua Supplier</option>
                                <option v-for="s in supplierOptions" :key="s" :value="s">{{ s }}</option>
                            </select>
                        </div>
                        <!-- Jenis Singkong -->
                        <div v-if="jenisOptions && jenisOptions.length > 0">
                            <label class="block text-xs font-semibold text-muted-foreground uppercase mb-1.5">Jenis Singkong</label>
                            <select v-model="filterForm.jenis" class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm shadow-sm transition-colors focus:outline-none focus:ring-1 focus:ring-ring">
                                <option value="">Semua Jenis</option>
                                <option v-for="j in jenisOptions" :key="j" :value="j">{{ j }}</option>
                            </select>
                        </div>
                    </div>

                    <div class="flex flex-col sm:flex-row gap-3 mt-5">
                        <Button @click="applyFilter" class="flex-1 sm:flex-none px-8 py-2.5">
                            <Search class="w-4 h-4 mr-2" /> Terapkan Filter
                        </Button>
                        <Button @click="resetFilter" variant="outline" class="flex-1 sm:flex-none px-6 py-2.5">
                            <RotateCcw class="w-4 h-4 mr-2" /> Reset
                        </Button>
                    </div>
                </div>
            </Card>

            <!-- ── Data Table ── -->
            <Card class="overflow-hidden bg-slate-50/80 border-slate-200 shadow-md dark:bg-slate-900/80 dark:border-slate-800">
                <div class="px-6 py-4 border-b flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div>
                        <h2 class="text-base font-bold text-foreground">Tabel Data</h2>
                        <p class="text-xs text-muted-foreground mt-0.5">
                            Menampilkan {{ penimbangans.from || 0 }}–{{ penimbangans.to || 0 }} dari {{ penimbangans.total }} data
                        </p>
                    </div>
                    <Badge variant="secondary" class="font-bold text-xs px-3 py-1">
                        Halaman {{ penimbangans.current_page }} / {{ penimbangans.last_page }}
                    </Badge>
                </div>

                <div class="overflow-x-auto">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead class="px-4 py-3 text-xs font-bold text-muted-foreground uppercase whitespace-nowrap">No.</TableHead>
                                <TableHead class="px-4 py-3 text-xs font-bold text-muted-foreground uppercase whitespace-nowrap">Tanggal</TableHead>
                                <!-- Singkong columns -->
                                <template v-if="isSingkong">
                                    <TableHead class="px-4 py-3 text-xs font-bold text-muted-foreground uppercase whitespace-nowrap">No Surat</TableHead>
                                    <TableHead class="px-4 py-3 text-xs font-bold text-muted-foreground uppercase whitespace-nowrap">Supplier</TableHead>
                                    <TableHead class="px-4 py-3 text-xs font-bold text-muted-foreground uppercase whitespace-nowrap">Asal</TableHead>
                                    <TableHead class="px-4 py-3 text-xs font-bold text-muted-foreground uppercase whitespace-nowrap">Jenis</TableHead>
                                    <TableHead class="px-4 py-3 text-xs font-bold text-muted-foreground uppercase whitespace-nowrap">Sopir</TableHead>
                                    <TableHead class="px-4 py-3 text-xs font-bold text-muted-foreground uppercase whitespace-nowrap">No. Plat</TableHead>
                                </template>
                                <!-- FG columns -->
                                <template v-else>
                                    <TableHead class="px-4 py-3 text-xs font-bold text-muted-foreground uppercase whitespace-nowrap">Produk</TableHead>
                                    <TableHead class="px-4 py-3 text-xs font-bold text-muted-foreground uppercase whitespace-nowrap">Kode Produksi</TableHead>
                                    <TableHead class="px-4 py-3 text-xs font-bold text-muted-foreground uppercase whitespace-nowrap">Expired</TableHead>
                                </template>
                                <TableHead class="px-4 py-3 text-xs font-bold text-muted-foreground uppercase whitespace-nowrap">Operator</TableHead>
                                <TableHead class="px-4 py-3 text-xs font-bold text-muted-foreground uppercase whitespace-nowrap">Berat</TableHead>
                                <TableHead class="px-4 py-3 text-xs font-bold text-muted-foreground uppercase whitespace-nowrap">Status</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow
                                v-for="(p, index) in penimbangans.data"
                                :key="p.id"
                            >
                                <TableCell class="px-4 py-3.5 text-xs font-bold text-muted-foreground whitespace-nowrap">
                                    {{ (penimbangans.current_page - 1) * penimbangans.per_page + index + 1 }}
                                </TableCell>
                                <TableCell class="px-4 py-3.5 text-xs text-muted-foreground whitespace-nowrap">
                                    {{ formatDateTime(p.created_at) }}
                                </TableCell>

                                <!-- Singkong cells -->
                                <template v-if="isSingkong">
                                    <TableCell class="px-4 py-3.5 font-mono text-xs">{{ p.no_surat }}</TableCell>
                                    <TableCell class="px-4 py-3.5 font-bold text-sm">{{ p.nama_supplier }}</TableCell>
                                    <TableCell class="px-4 py-3.5 text-sm text-muted-foreground">{{ p.asal }}</TableCell>
                                    <TableCell class="px-4 py-3.5">
                                        <Badge variant="secondary">
                                            {{ p.jenis_singkong }}
                                        </Badge>
                                    </TableCell>
                                    <TableCell class="px-4 py-3.5 text-sm text-muted-foreground">{{ p.nama_sopir }}</TableCell>
                                    <TableCell class="px-4 py-3.5 font-mono text-xs text-muted-foreground">{{ p.nomor_plat }}</TableCell>
                                </template>
                                <!-- FG cells -->
                                <template v-else>
                                    <TableCell class="px-4 py-3.5 font-semibold text-sm whitespace-nowrap">{{ p.produk?.nama_produk || '-' }}</TableCell>
                                    <TableCell class="px-4 py-3.5">
                                        <code class="bg-muted px-2 py-0.5 rounded font-mono text-xs border border-border">
                                            {{ p.kode_produksi_display || p.kode_produksi || '-' }}
                                        </code>
                                    </TableCell>
                                    <TableCell class="px-4 py-3.5 text-sm text-muted-foreground whitespace-nowrap">{{ formatDate(p.tanggal_expired) }}</TableCell>
                                </template>

                                <TableCell class="px-4 py-3.5">
                                    <div class="text-sm font-semibold">{{ p.user?.name || '-' }}</div>
                                    <div v-if="p.user?.shift" class="text-xs text-muted-foreground mt-0.5">Shift {{ p.user.shift }}</div>
                                </TableCell>
                                <TableCell class="px-4 py-3.5 font-bold whitespace-nowrap">
                                    {{ formatWeight(p.berat) }} <span class="text-xs font-normal text-muted-foreground">kg</span>
                                </TableCell>
                                <TableCell class="px-4 py-3.5">
                                    <Badge v-slot:default v-if="p.status === 'selesai'" class="bg-emerald-100 text-emerald-800 border-emerald-200">
                                        Selesai
                                    </Badge>
                                    <Badge v-else variant="outline" class="bg-amber-100 text-amber-800 border-amber-200">
                                        Menunggu
                                    </Badge>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </div>

                <!-- Empty State -->
                <div v-if="penimbangans.data.length === 0" class="p-16 text-center">
                    <div class="w-16 h-16 bg-muted rounded-full flex items-center justify-center mx-auto mb-4">
                        <Package class="w-8 h-8 text-muted-foreground" />
                    </div>
                    <p class="font-bold">Tidak ada data</p>
                    <p class="text-muted-foreground text-sm mt-1">Coba ubah filter tanggal atau reset filter.</p>
                </div>

                <!-- Pagination -->
                <div v-if="penimbangans.last_page > 1" class="flex flex-col sm:flex-row items-center justify-between px-6 py-4 border-t border-border gap-4">
                    <p class="text-sm text-muted-foreground">
                        Menampilkan <span class="font-bold">{{ penimbangans.from }}</span>
                        – <span class="font-bold">{{ penimbangans.to }}</span>
                        dari <span class="font-bold">{{ penimbangans.total }}</span> data
                    </p>
                    <Pagination
                        v-slot="{ page }"
                        :total="penimbangans.total"
                        :items-per-page="penimbangans.per_page"
                        :sibling-count="1"
                        show-edges
                        :page="penimbangans.current_page"
                        @update:page="handlePageChange"
                    >
                        <PaginationContent v-slot="{ items }" class="flex items-center gap-1">
                            <PaginationFirst />
                            <PaginationPrevious />

                            <template v-for="(item, index) in items" :key="index">
                                <PaginationItem
                                    v-if="item.type === 'page'"
                                    :value="item.value"
                                    :is-active="item.value === page"
                                >
                                    {{ item.value }}
                                </PaginationItem>
                                <PaginationEllipsis v-else />
                            </template>

                            <PaginationNext />
                            <PaginationLast />
                        </PaginationContent>
                    </Pagination>
                </div>
            </Card>

        </div>
    </AuthenticatedLayout>
</template>
