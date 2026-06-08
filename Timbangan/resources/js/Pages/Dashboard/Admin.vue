<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { ref, onMounted, onUnmounted } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import {
    Card,
    CardContent,
    CardHeader,
    CardTitle,
} from '@/Components/ui/card';
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
    FileDown,
    TrendingUp,
    Weight,
    CheckCircle2,
    AlertCircle,
    Clock,
    ChevronRight,
    ClipboardList,
    Users,
    MapPin,
    RotateCcw,
    Activity,
    Wifi,
    Package,
    ChevronUp,
    ChevronDown,
} from 'lucide-vue-next';
import Swal from 'sweetalert2';
import { computed } from 'vue';
import DashboardChart from '@/Components/ui/chart/DashboardChart.vue';

const props = defineProps({
    stats: Object,
    moduleStats: Object,
    moduleNames: Object,
    recentPenimbangans: Array,
    produks: Array,
    chartData: Array,
    chartFilter: String,
    filters: { type: Object, default: () => ({}) },
    operators: { type: Array, default: () => [] },
    shifts: { type: Array, default: () => [] },
    shiftsPerModule: { type: Object, default: () => ({}) },
});

const filterForm = ref({
    shift: props.filters?.shift || '',
    operator: props.filters?.operator || '',
    module: props.filters?.module || '',
});

const showFilters = ref(false);
const liveIndicator = ref(false);
const lastReceived = ref(null);

const filteredOperators = computed(() => {
    if (!filterForm.value.module) return props.operators;
    return props.operators.filter(op => op.tipe === filterForm.value.module);
});

const filteredShifts = computed(() => {
    if (!filterForm.value.module) return props.shifts;
    return props.shiftsPerModule[filterForm.value.module] || [];
});

const applyGlobalFilter = () => {
    if (filterForm.value.module && filterForm.value.operator) {
        const isValid = props.operators.some(op => op.id === filterForm.value.operator && op.tipe === filterForm.value.module);
        if (!isValid) filterForm.value.operator = '';
    }
    if (filterForm.value.module && filterForm.value.shift) {
        const moduleShifts = props.shiftsPerModule[filterForm.value.module] || [];
        if (!moduleShifts.includes(filterForm.value.shift)) filterForm.value.shift = '';
    }
    router.get(window.location.pathname, filterForm.value, { preserveState: true });
};

const resetGlobalFilter = () => {
    filterForm.value = { shift: '', operator: '', module: '' };
    router.get(window.location.pathname, filterForm.value, { preserveState: true });
};

// ── Real-time listener ──
onMounted(() => {
    if (window.Echo) {
        window.Echo.channel('iot-weights')
            .listen('.WeightReceived', (e) => {
                liveIndicator.value = true;
                lastReceived.value = {
                    weight: e.weight || e.berat,
                    operator: e.operator,
                    module: e.module,
                    ip: e.ip_address,
                    time: new Date().toLocaleTimeString('id-ID'),
                };

                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'success',
                    title: `Data berat ${e.weight || e.berat} kg diterima`,
                    text: `Operator: ${e.operator} | IP: ${e.ip_address}`,
                    showConfirmButton: false,
                    timer: 4000,
                });

                // Reload stats & recent data
                router.reload({ only: ['stats', 'moduleStats', 'recentPenimbangans', 'chartData'] });

                setTimeout(() => { liveIndicator.value = false; }, 3000);
            });
    }
});

onUnmounted(() => {
    if (window.Echo) {
        window.Echo.leave('iot-weights');
    }
});

const successRate = props.stats.total > 0 ? (props.stats.selesai / props.stats.total) * 100 : 0;

const formatNumber = (num) => new Intl.NumberFormat('id-ID').format(num);
const formatWeight = (weight) => new Intl.NumberFormat('id-ID', { minimumFractionDigits: 3, maximumFractionDigits: 3 }).format(weight);

const getRoute = (type) => {
    const routeMap = {
        'fg': 'admin.fg',
        'fg_psn': 'admin.fg-psn',
        'fg_surabaya': 'admin.fg-surabaya',
        'cs_noodle_sby': 'admin.cs-noodle-sby',
        'cs_fg_sby': 'admin.cs-fg-sby',
        'incoming_singkong': 'admin.incoming.singkong',
        'incoming_rmpm': 'admin.incoming.rmpm',
    };
    return routeMap[type] || 'dashboard';
};

const getModuleColor = (type) => {
    const colors = {
        'fg': 'from-blue-500 to-blue-700',
        'fg_psn': 'from-indigo-500 to-indigo-700',
        'fg_surabaya': 'from-violet-500 to-violet-700',
        'cs_noodle_sby': 'from-cyan-500 to-cyan-700',
        'cs_fg_sby': 'from-teal-500 to-teal-700',
        'incoming_singkong': 'from-amber-500 to-amber-700',
        'incoming_rmpm': 'from-rose-500 to-rose-700',
    };
    return colors[type] || 'from-gray-500 to-gray-700';
};

const getModuleIcon = (type) => {
    return type.startsWith('incoming') ? 'incoming' : 'fg';
};

const getLocationLabel = (type) => {
    if (['fg', 'fg_psn', 'incoming_singkong', 'incoming_rmpm'].includes(type)) return 'Pasuruan';
    return 'Surabaya';
};
</script>

<template>
    <Head title="Admin Dashboard" />

    <AuthenticatedLayout>
        <div class="space-y-6">

            <!-- ── Page Header ── -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-black text-gray-900 tracking-tight">Overview Panel</h1>
                    <p class="text-sm text-gray-500">Ringkasan operasional real-time semua lokasi.</p>
                </div>
                <div class="flex items-center gap-3">
                    <!-- Live Indicator -->
                    <div class="flex items-center gap-2 px-4 py-2 bg-white border border-gray-200 rounded-xl shadow-sm">
                        <span class="relative flex h-2.5 w-2.5">
                            <span :class="liveIndicator ? 'animate-ping bg-emerald-400' : 'bg-gray-300'" class="absolute inline-flex h-full w-full rounded-full opacity-75"></span>
                            <span :class="liveIndicator ? 'bg-emerald-500' : 'bg-gray-400'" class="relative inline-flex rounded-full h-2.5 w-2.5"></span>
                        </span>
                        <span class="text-xs font-bold text-gray-600 uppercase tracking-wider">Live</span>
                    </div>
                    <Button as="a" :href="route('penimbangan.export', filterForm)" class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold px-5 py-2.5 rounded-xl shadow-md shadow-emerald-200 transition-all">
                        <FileDown class="w-4 h-4 mr-2" /> Export CSV
                    </Button>
                </div>
            </div>

            <!-- ── Live Reception Banner ── -->
            <transition
                enter-active-class="transition-all duration-500 ease-out"
                enter-from-class="opacity-0 -translate-y-2"
                enter-to-class="opacity-100 translate-y-0"
                leave-active-class="transition-all duration-300 ease-in"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div v-if="lastReceived" class="bg-gradient-to-r from-emerald-500 to-teal-600 rounded-2xl p-4 shadow-lg shadow-emerald-200/50 text-white flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <div class="p-2.5 bg-white/20 rounded-xl backdrop-blur-sm">
                            <Wifi class="w-5 h-5 animate-pulse" />
                        </div>
                        <div>
                            <p class="text-xs font-bold uppercase tracking-widest opacity-80">Data Diterima Real-Time</p>
                            <p class="text-sm font-black mt-0.5">
                                {{ lastReceived.weight }} kg dari <span class="underline decoration-white/50">{{ lastReceived.operator }}</span>
                            </p>
                        </div>
                    </div>
                    <div class="flex items-center gap-4 text-xs opacity-90">
                        <span class="bg-white/20 px-3 py-1 rounded-full font-bold backdrop-blur-sm">IP: {{ lastReceived.ip }}</span>
                        <span class="bg-white/20 px-3 py-1 rounded-full font-bold backdrop-blur-sm">{{ lastReceived.time }}</span>
                    </div>
                </div>
            </transition>

            <!-- ── Stats Cards ── -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-gradient-to-br from-blue-600 to-indigo-700 rounded-2xl p-5 text-white shadow-lg shadow-blue-200">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-[10px] font-bold uppercase tracking-widest opacity-75">Total Penimbangan</span>
                        <div class="p-2 bg-white/20 rounded-lg"><ClipboardList class="w-4 h-4" /></div>
                    </div>
                    <p class="text-3xl font-black">{{ formatNumber(stats.total) }}</p>
                    <p class="text-xs opacity-60 mt-1">Items keseluruhan</p>
                </div>

                <div class="bg-gradient-to-br from-emerald-600 to-teal-700 rounded-2xl p-5 text-white shadow-lg shadow-emerald-200">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-[10px] font-bold uppercase tracking-widest opacity-75">Total Berat</span>
                        <div class="p-2 bg-white/20 rounded-lg"><Weight class="w-4 h-4" /></div>
                    </div>
                    <p class="text-2xl font-black">{{ formatWeight(stats.total_berat) }} <span class="text-sm opacity-70">kg</span></p>
                    <p class="text-xs opacity-60 mt-1">Berat selesai</p>
                </div>

                <div class="bg-gradient-to-br from-violet-600 to-purple-700 rounded-2xl p-5 text-white shadow-lg shadow-violet-200">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-[10px] font-bold uppercase tracking-widest opacity-75">Success Rate</span>
                        <div class="p-2 bg-white/20 rounded-lg"><TrendingUp class="w-4 h-4" /></div>
                    </div>
                    <p class="text-3xl font-black">{{ formatNumber(successRate) }}%</p>
                    <p class="text-xs opacity-60 mt-1">{{ stats.selesai }}/{{ stats.total }} selesai</p>
                </div>

                <div class="bg-gradient-to-br from-rose-600 to-red-700 rounded-2xl p-5 text-white shadow-lg shadow-rose-200">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-[10px] font-bold uppercase tracking-widest opacity-75">Invalid</span>
                        <div class="p-2 bg-white/20 rounded-lg"><AlertCircle class="w-4 h-4" /></div>
                    </div>
                    <p class="text-3xl font-black">{{ formatNumber(stats.invalid) }}</p>
                    <p class="text-xs opacity-60 mt-1">Records gagal</p>
                </div>
            </div>

            <!-- ── Filter Panel ── -->
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                <button @click="showFilters = !showFilters" class="w-full flex items-center justify-between px-6 py-3.5 hover:bg-gray-50 transition-colors">
                    <span class="flex items-center gap-2 font-bold text-gray-700 text-sm">
                        <MapPin class="w-4 h-4 text-indigo-500" /> Filter Modul, Shift & Operator
                    </span>
                    <ChevronUp v-if="showFilters" class="w-4 h-4 text-gray-400" />
                    <ChevronDown v-else class="w-4 h-4 text-gray-400" />
                </button>
                <div v-show="showFilters" class="px-6 pb-5 border-t border-gray-100">
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 pt-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-1.5">Modul / Lokasi</label>
                            <select v-model="filterForm.module" @change="applyGlobalFilter" class="block w-full p-2.5 text-sm text-gray-900 bg-gray-50 border border-gray-200 rounded-xl focus:ring-indigo-500 focus:border-indigo-500">
                                <option value="">Semua Modul</option>
                                <optgroup label="Pasuruan">
                                    <option value="fg">Formulasi</option>
                                    <option value="fg_psn">Finished Goods</option>
                                    <option value="incoming_singkong">Incoming Singkong</option>
                                    <option value="incoming_rmpm">Incoming RMPM</option>
                                </optgroup>
                                <optgroup label="Surabaya">
                                    <option value="fg_surabaya">Formulasi</option>
                                    <option value="cs_noodle_sby">CS Noodle</option>
                                    <option value="cs_fg_sby">CS FG-Sby</option>
                                </optgroup>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-1.5">Shift</label>
                            <select v-model="filterForm.shift" @change="applyGlobalFilter" class="block w-full p-2.5 text-sm text-gray-900 bg-gray-50 border border-gray-200 rounded-xl focus:ring-indigo-500 focus:border-indigo-500">
                                <option value="">Semua Shift</option>
                                <option v-for="s in filteredShifts" :key="s" :value="s">{{ s }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-1.5">Operator</label>
                            <select v-model="filterForm.operator" @change="applyGlobalFilter" class="block w-full p-2.5 text-sm text-gray-900 bg-gray-50 border border-gray-200 rounded-xl focus:ring-indigo-500 focus:border-indigo-500">
                                <option value="">Semua Operator</option>
                                <option v-for="op in filteredOperators" :key="op.id" :value="op.id">{{ op.name }} ({{ op.shift ? 'Shift '+op.shift : '-' }})</option>
                            </select>
                        </div>
                        <div class="flex items-end">
                            <Button @click="resetGlobalFilter" variant="outline" class="w-full font-bold text-gray-600 border-gray-200 rounded-xl py-2.5">
                                <RotateCcw class="w-4 h-4 mr-2" /> Reset
                            </Button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ── Chart ── -->
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <h2 class="text-base font-bold text-gray-900">Grafik Penimbangan</h2>
                    <div class="flex bg-gray-100 p-1 rounded-lg">
                        <Link :href="route('dashboard', { ...filterForm, chart_filter: 'week' })" class="px-4 py-1.5 text-xs font-bold rounded-md transition-all" :class="chartFilter === 'week' ? 'bg-white shadow text-indigo-700' : 'text-gray-500 hover:text-gray-900'">Mingguan</Link>
                        <Link :href="route('dashboard', { ...filterForm, chart_filter: 'month' })" class="px-4 py-1.5 text-xs font-bold rounded-md transition-all" :class="chartFilter === 'month' ? 'bg-white shadow text-indigo-700' : 'text-gray-500 hover:text-gray-900'">Bulanan</Link>
                    </div>
                </div>
                <div class="p-6">
                    <DashboardChart :data="chartData" />
                </div>
            </div>

            <!-- ── Module Breakdown ── -->
            <div>
                <h2 class="text-base font-bold text-gray-900 mb-4">Breakdown Per Modul</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                    <div v-for="(name, type) in moduleNames" :key="type" class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden hover:shadow-md transition-shadow">
                        <div :class="'bg-gradient-to-r ' + getModuleColor(type)" class="px-5 py-3.5 text-white">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-[10px] font-bold uppercase tracking-widest opacity-75">{{ getLocationLabel(type) }}</p>
                                    <h3 class="text-sm font-black mt-0.5">{{ name }}</h3>
                                </div>
                                <div class="p-2 bg-white/20 rounded-lg">
                                    <Package class="w-4 h-4" />
                                </div>
                            </div>
                        </div>
                        <div class="p-4 space-y-3">
                            <div class="flex items-center justify-between">
                                <span class="text-xs text-gray-500 font-medium">Penimbangan</span>
                                <span class="text-sm font-black text-gray-900">{{ formatNumber(moduleStats[type]?.total || 0) }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-xs text-gray-500 font-medium">Total Berat</span>
                                <span class="text-sm font-black text-gray-900">{{ formatWeight(moduleStats[type]?.total_berat || 0) }} kg</span>
                            </div>
                            <div class="pt-2 border-t border-gray-100">
                                <Link :href="route(getRoute(type))" class="flex items-center justify-center gap-1 text-xs font-bold text-indigo-600 hover:text-indigo-800 transition-colors py-1">
                                    Lihat Detail <ChevronRight class="w-3.5 h-3.5" />
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ── Recent Activity ── -->
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                    <div>
                        <h2 class="text-base font-bold text-gray-900">Aktivitas Terakhir</h2>
                        <p class="text-xs text-gray-400 mt-0.5">10 penimbangan terbaru dari semua modul</p>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <Activity class="w-3.5 h-3.5 text-emerald-500" />
                        <span class="text-xs font-bold text-emerald-600">Real-Time</span>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <Table>
                        <TableHeader class="bg-gray-50">
                            <TableRow>
                                <TableHead class="px-5 py-3 text-xs font-bold text-gray-500 uppercase whitespace-nowrap">No.</TableHead>
                                <TableHead class="px-5 py-3 text-xs font-bold text-gray-500 uppercase whitespace-nowrap">Waktu</TableHead>
                                <TableHead class="px-5 py-3 text-xs font-bold text-gray-500 uppercase whitespace-nowrap">Modul</TableHead>
                                <TableHead class="px-5 py-3 text-xs font-bold text-gray-500 uppercase whitespace-nowrap">Produk</TableHead>
                                <TableHead class="px-5 py-3 text-xs font-bold text-gray-500 uppercase whitespace-nowrap">Operator</TableHead>
                                <TableHead class="px-5 py-3 text-xs font-bold text-gray-500 uppercase whitespace-nowrap">Berat</TableHead>
                                <TableHead class="px-5 py-3 text-xs font-bold text-gray-500 uppercase whitespace-nowrap text-center">Status</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="(p, index) in recentPenimbangans" :key="p.id" class="hover:bg-indigo-50/30 transition-colors border-b border-gray-50">
                                <TableCell class="px-5 py-3.5 font-bold text-gray-400 text-xs">{{ index + 1 }}</TableCell>
                                <TableCell class="px-5 py-3.5 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-700">
                                        {{ new Date(p.created_at).toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' }) }}
                                    </div>
                                    <div class="text-[10px] text-gray-400">
                                        {{ new Date(p.created_at).toLocaleDateString('id-ID') }}
                                    </div>
                                </TableCell>
                                <TableCell class="px-5 py-3.5">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-indigo-100 text-indigo-700">
                                        {{ moduleNames[p.user?.tipe] || p.user?.tipe || '-' }}
                                    </span>
                                </TableCell>
                                <TableCell class="px-5 py-3.5 font-semibold text-gray-900 text-sm">{{ p.produk?.nama_produk || '-' }}</TableCell>
                                <TableCell class="px-5 py-3.5">
                                    <div class="text-sm font-medium text-gray-800">{{ p.user?.name || '-' }}</div>
                                    <div class="text-[10px] text-gray-400">Shift {{ p.user?.shift ?? '-' }}</div>
                                </TableCell>
                                <TableCell class="px-5 py-3.5 font-black text-gray-900 whitespace-nowrap">
                                    {{ formatWeight(p.berat) }} <span class="text-[10px] font-normal text-gray-400">kg</span>
                                </TableCell>
                                <TableCell class="px-5 py-3.5 text-center">
                                    <span v-if="p.status == 'selesai'" class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-emerald-50 text-emerald-600">
                                        <CheckCircle2 class="w-4 h-4 stroke-[3]" />
                                    </span>
                                    <span v-else-if="p.status == 'menunggu'" class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-amber-50 text-amber-600 animate-pulse">
                                        <Clock class="w-4 h-4 stroke-[3]" />
                                    </span>
                                    <span v-else class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-rose-50 text-rose-600">
                                        <AlertCircle class="w-4 h-4 stroke-[3]" />
                                    </span>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </div>
                <div v-if="recentPenimbangans.length === 0" class="p-16 text-center">
                    <div class="w-16 h-16 bg-gray-100 rounded-2xl flex items-center justify-center mx-auto mb-4">
                        <Package class="w-8 h-8 text-gray-300" />
                    </div>
                    <p class="text-gray-500 font-bold">Belum ada aktivitas</p>
                    <p class="text-gray-400 text-sm mt-1">Data penimbangan akan muncul di sini secara otomatis.</p>
                </div>
            </div>

        </div>
    </AuthenticatedLayout>
</template>
