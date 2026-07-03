<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { ref } from 'vue';
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
    Scale,
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
import { formatWeight } from '@/utils/format.js';
import { computed } from 'vue';
import DashboardChart from '@/Components/ui/chart/DashboardChart.vue';
import { useRealtimeReload } from '@/composables/useRealtimeReload.js';

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

// ── Real-time listener (WebSocket + fallback polling REST) ──
// Grafik Penimbangan, Breakdown Per Modul, stats & Aktivitas Terakhir
// di-reload otomatis setiap ada data masuk dari API modul manapun.
const { connected } = useRealtimeReload({
    channel: 'iot-weights',
    only: ['stats', 'moduleStats', 'recentPenimbangans', 'chartData'],
    onEvent: (e) => {
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
            title: `Data berat ${formatWeight(e.weight || e.berat)} kg diterima`,
            text: `Operator: ${e.operator} | IP: ${e.ip_address}`,
            showConfirmButton: false,
            timer: 4000,
        });

        setTimeout(() => { liveIndicator.value = false; }, 3000);
    },
});

const successRate = computed(() => props.stats.total > 0 ? (props.stats.selesai / props.stats.total) * 100 : 0);

const formatNumber = (num) => new Intl.NumberFormat('id-ID').format(num);

const routeMap = {
    'fg': 'admin.fg',
    'fg_psn': 'admin.fg-psn',
    'fg_surabaya': 'admin.fg-surabaya',
    'cs_noodle_sby': 'admin.cs-noodle-sby',
    'cs_fg_sby': 'admin.cs-fg-sby',
    'incoming_singkong': 'admin.incoming.singkong',
    'incoming_rmpm': 'admin.incoming.rmpm',
};

const hasDetailRoute = (type) => !!routeMap[type];

const getRoute = (type) => {
    return routeMap[type] || 'dashboard';
};

const getModuleColor = (type) => {
    const colors = {
        'fg': 'bg-blue-50 text-blue-600',
        'fg_psn': 'bg-indigo-50 text-indigo-600',
        'formulasi_pasuruan': 'bg-sky-50 text-sky-600',
        'fg_surabaya': 'bg-violet-50 text-violet-600',
        'cs_noodle_sby': 'bg-cyan-50 text-cyan-600',
        'cs_fg_sby': 'bg-teal-50 text-teal-600',
        'incoming_singkong': 'bg-amber-50 text-amber-600',
        'incoming_rmpm': 'bg-rose-50 text-rose-600',
    };
    return colors[type] || 'bg-gray-50 text-gray-600';
};

const getModuleIcon = (type) => {
    return type.startsWith('incoming') ? 'incoming' : 'fg';
};

const getLocationLabel = (type) => {
    if (['fg', 'fg_psn', 'formulasi_pasuruan', 'incoming_singkong', 'incoming_rmpm'].includes(type)) return 'Pasuruan';
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
                    <h1 class="font-display text-4xl tracking-wider text-primary drop-shadow-sm">Overview Panel</h1>
                    <p class="text-sm text-muted-foreground">Ringkasan operasional real-time semua lokasi.</p>
                </div>
                <div class="flex items-center gap-3">
                    <!-- Live Indicator -->
                    <div class="flex items-center gap-2 px-3 py-1.5 bg-accent text-accent-foreground rounded-md border">
                        <span class="relative flex h-2 w-2">
                            <span :class="liveIndicator ? 'animate-ping bg-emerald-500' : (connected ? 'bg-emerald-500' : 'bg-amber-500')" class="absolute inline-flex h-full w-full rounded-full opacity-75"></span>
                            <span :class="liveIndicator ? 'bg-emerald-500' : (connected ? 'bg-emerald-500' : 'bg-amber-500')" class="relative inline-flex rounded-full h-2 w-2"></span>
                        </span>
                        <span class="text-xs font-semibold uppercase tracking-wider">{{ connected ? 'Live' : 'Polling' }}</span>
                    </div>
                    <Button as="a" :href="route('penimbangan.export', filterForm)">
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
                <div v-if="lastReceived" class="bg-emerald-50/50 dark:bg-emerald-950/20 border border-emerald-200 rounded-2xl p-4 shadow-sm flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 relative overflow-hidden">
                    <div class="absolute inset-y-0 left-0 w-1 bg-emerald-500"></div>
                    <div class="flex items-center gap-3">
                        <div class="p-2.5 bg-emerald-50 text-emerald-600 rounded-xl">
                            <Wifi class="w-5 h-5 animate-pulse" />
                        </div>
                        <div>
                            <p class="text-[10px] font-bold text-gray-500 uppercase tracking-widest">Data Diterima Real-Time</p>
                            <p class="text-sm font-black text-gray-900 mt-0.5">
                                {{ formatWeight(lastReceived.weight) }} kg dari <span class="text-emerald-600">{{ lastReceived.operator }}</span>
                            </p>
                        </div>
                    </div>
                    <div class="flex items-center gap-4 text-xs font-bold text-gray-500">
                        <span class="bg-gray-50 px-3 py-1 rounded-full border border-gray-100">IP: {{ lastReceived.ip }}</span>
                        <span class="bg-gray-50 px-3 py-1 rounded-full border border-gray-100">{{ lastReceived.time }}</span>
                    </div>
                </div>
            </transition>

            <!-- ── Stats Cards ── -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                <Card class="bg-primary/10 border-2 border-primary rounded-2xl shadow-[4px_4px_0_0_#3B82F6] hover:translate-y-[-2px] transition-transform">
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="font-display text-xl tracking-wider text-primary">Total Penimbangan</CardTitle>
                        <ClipboardList class="h-6 w-6 text-primary" />
                    </CardHeader>
                    <CardContent>
                        <div class="font-display text-4xl text-primary mt-2">{{ formatNumber(stats.total) }}</div>
                        <p class="font-mono text-xs font-bold text-primary/80 mt-1">Items keseluruhan</p>
                    </CardContent>
                </Card>

                <Card class="bg-violet-500/10 border-2 border-violet-500 rounded-2xl shadow-[4px_4px_0_0_#8B5CF6] hover:translate-y-[-2px] transition-transform">
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="font-display text-xl tracking-wider text-violet-600">Total Berat</CardTitle>
                        <Scale class="h-6 w-6 text-violet-600" />
                    </CardHeader>
                    <CardContent>
                        <div class="font-display text-4xl text-violet-600 mt-2">{{ formatWeight(stats.total_berat) }} <span class="text-xl">kg</span></div>
                        <p class="font-mono text-xs font-bold text-violet-600/80 mt-1">Berat selesai</p>
                    </CardContent>
                </Card>

                <Card class="bg-emerald-500/10 border-2 border-emerald-500 rounded-2xl shadow-[4px_4px_0_0_#16A34A] hover:translate-y-[-2px] transition-transform">
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="font-display text-xl tracking-wider text-emerald-600">Success Rate</CardTitle>
                        <TrendingUp class="h-6 w-6 text-emerald-600" />
                    </CardHeader>
                    <CardContent>
                        <div class="font-display text-4xl text-emerald-600 mt-2">{{ formatNumber(successRate) }}%</div>
                        <p class="font-mono text-xs font-bold text-emerald-600/80 mt-1">{{ stats.selesai }}/{{ stats.total }} selesai</p>
                    </CardContent>
                </Card>

                <Card class="bg-red-500/10 border-2 border-red-500 rounded-2xl shadow-[4px_4px_0_0_#DC2626] hover:translate-y-[-2px] transition-transform">
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="font-display text-xl tracking-wider text-red-600">Invalid</CardTitle>
                        <AlertCircle class="h-6 w-6 text-red-600" />
                    </CardHeader>
                    <CardContent>
                        <div class="font-display text-4xl text-red-600 mt-2">{{ formatNumber(stats.invalid) }}</div>
                        <p class="font-mono text-xs font-bold text-red-600/80 mt-1">Records gagal</p>
                    </CardContent>
                </Card>
            </div>

            <!-- ── Filter Panel ── -->
            <Card class="overflow-hidden bg-slate-50/80 border-slate-200 shadow-md dark:bg-slate-900/80 dark:border-slate-800">
                <Button variant="ghost" @click="showFilters = !showFilters" class="w-full flex items-center justify-between px-6 py-4 rounded-none h-auto">
                    <span class="flex items-center gap-2 font-semibold text-sm">
                        <MapPin class="w-4 h-4 text-primary" /> Filter Modul, Shift & Operator
                    </span>
                    <ChevronUp v-if="showFilters" class="w-4 h-4 text-muted-foreground" />
                    <ChevronDown v-else class="w-4 h-4 text-muted-foreground" />
                </Button>
                <div v-show="showFilters" class="px-6 pb-5 border-t">
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 pt-4">
                        <div>
                            <label class="block text-xs font-semibold text-muted-foreground uppercase mb-1.5">Modul / Lokasi</label>
                            <select v-model="filterForm.module" @change="applyGlobalFilter" class="flex h-9 w-full items-center justify-between rounded-md border border-input bg-background px-3 py-2 text-sm shadow-sm ring-offset-background placeholder:text-muted-foreground focus:outline-none focus:ring-1 focus:ring-ring disabled:cursor-not-allowed disabled:opacity-50">
                                <option value="">Semua Modul</option>
                                <optgroup label="Pasuruan">
                                    <option value="fg">Formulasi</option>
                                    <option value="fg_psn">Finished Goods</option>
                                    <option value="formulasi_pasuruan">Formulasi Pasuruan</option>
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
                            <label class="block text-xs font-semibold text-muted-foreground uppercase mb-1.5">Shift</label>
                            <select v-model="filterForm.shift" @change="applyGlobalFilter" class="flex h-9 w-full items-center justify-between rounded-md border border-input bg-background px-3 py-2 text-sm shadow-sm ring-offset-background placeholder:text-muted-foreground focus:outline-none focus:ring-1 focus:ring-ring disabled:cursor-not-allowed disabled:opacity-50">
                                <option value="">Semua Shift</option>
                                <option v-for="s in filteredShifts" :key="s" :value="s">{{ s }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-muted-foreground uppercase mb-1.5">Operator</label>
                            <select v-model="filterForm.operator" @change="applyGlobalFilter" class="flex h-9 w-full items-center justify-between rounded-md border border-input bg-background px-3 py-2 text-sm shadow-sm ring-offset-background placeholder:text-muted-foreground focus:outline-none focus:ring-1 focus:ring-ring disabled:cursor-not-allowed disabled:opacity-50">
                                <option value="">Semua Operator</option>
                                <option v-for="op in filteredOperators" :key="op.id" :value="op.id">{{ op.name }} ({{ op.shift ? (String(op.shift).toLowerCase().includes('shift') ? op.shift : 'Shift ' + op.shift) : '-' }})</option>
                            </select>
                        </div>
                        <div class="flex items-end">
                            <Button @click="resetGlobalFilter" variant="outline" class="w-full">
                                <RotateCcw class="w-4 h-4 mr-2" /> Reset
                            </Button>
                        </div>
                    </div>
                </div>
            </Card>

            <!-- ── Chart ── -->
            <Card class="overflow-hidden bg-slate-50/80 border-slate-200 shadow-md dark:bg-slate-900/80 dark:border-slate-800">
                <div class="px-6 py-4 border-b flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <h2 class="text-base font-semibold">Grafik Penimbangan</h2>
                    <div class="flex gap-1 bg-muted p-1 rounded-md">
                        <Link :href="route('dashboard', { ...filterForm, chart_filter: 'week' })">
                            <Button variant="ghost" size="sm" class="h-7 text-xs" :class="chartFilter === 'week' ? 'bg-background shadow-sm' : ''">Mingguan</Button>
                        </Link>
                        <Link :href="route('dashboard', { ...filterForm, chart_filter: 'month' })">
                            <Button variant="ghost" size="sm" class="h-7 text-xs" :class="chartFilter === 'month' ? 'bg-background shadow-sm' : ''">Bulanan</Button>
                        </Link>
                    </div>
                </div>
                <div class="p-6">
                    <DashboardChart :data="chartData" />
                </div>
            </Card>

            <!-- ── Module Breakdown ── -->
            <div>
                <h2 class="text-lg font-semibold tracking-tight mb-4">Breakdown Per Modul</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                    <Card v-for="(name, type) in moduleNames" :key="type" class="border-2 border-black rounded-xl shadow-[2px_2px_0_0_#000000] hover:translate-y-[-2px] transition-transform">
                        <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                            <div>
                                <p class="font-mono text-[10px] font-bold uppercase tracking-widest text-muted-foreground">{{ getLocationLabel(type) }}</p>
                                <CardTitle class="font-display text-lg tracking-wider mt-1">{{ name }}</CardTitle>
                            </div>
                            <Package class="w-5 h-5 text-black" />
                        </CardHeader>
                        <CardContent>
                            <div class="space-y-3">
                                <div class="flex items-center justify-between">
                                    <span class="font-mono text-xs font-bold text-muted-foreground">Penimbangan</span>
                                    <span class="font-display text-xl text-black">{{ formatNumber(moduleStats[type]?.total || 0) }}</span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="font-mono text-xs font-bold text-muted-foreground">Total Berat</span>
                                    <span class="font-display text-xl text-black">{{ formatWeight(moduleStats[type]?.total_berat || 0) }} kg</span>
                                </div>
                                <div v-if="hasDetailRoute(type)" class="pt-2 border-t border-black/10">
                                    <Link :href="route(getRoute(type))" class="flex items-center justify-center gap-1 font-display text-sm tracking-wider text-primary hover:underline py-1">
                                        Lihat Detail <ChevronRight class="w-4 h-4" />
                                    </Link>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>

            <!-- ── Recent Activity ── -->
            <Card class="overflow-hidden bg-slate-50/80 border-slate-200 shadow-md dark:bg-slate-900/80 dark:border-slate-800">
                <CardHeader class="flex flex-row items-center justify-between">
                    <div>
                        <CardTitle class="font-display text-xl tracking-wider">Aktivitas Terakhir</CardTitle>
                        <p class="font-mono text-xs font-bold text-muted-foreground mt-1">10 penimbangan terbaru dari semua modul</p>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <Activity :class="connected ? 'text-emerald-500' : 'text-amber-500'" class="w-3.5 h-3.5" />
                        <span :class="connected ? 'text-emerald-600' : 'text-amber-600'" class="text-xs font-medium">
                            {{ connected ? 'Real-Time (WebSocket)' : 'Auto-Refresh (Polling)' }}
                        </span>
                    </div>
                </CardHeader>
                <div class="overflow-x-auto border-t">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead class="w-12 text-center">No.</TableHead>
                                <TableHead>Waktu</TableHead>
                                <TableHead>Modul</TableHead>
                                <TableHead>Produk</TableHead>
                                <TableHead>Operator</TableHead>
                                <TableHead>Berat</TableHead>
                                <TableHead class="text-center">Status</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="(p, index) in recentPenimbangans" :key="p.id">
                                <TableCell class="text-center text-muted-foreground text-xs">{{ index + 1 }}</TableCell>
                                <TableCell class="whitespace-nowrap">
                                    <div class="font-medium">
                                        {{ new Date(p.created_at).toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' }) }}
                                    </div>
                                    <div class="text-[10px] text-muted-foreground">
                                        {{ new Date(p.created_at).toLocaleDateString('id-ID') }}
                                    </div>
                                </TableCell>
                                <TableCell>
                                    <Badge variant="secondary" class="text-[10px]">
                                        {{ moduleNames[p.user?.tipe] || p.user?.tipe || '-' }}
                                    </Badge>
                                </TableCell>
                                <TableCell class="font-medium">{{ p.produk?.nama_produk || '-' }}</TableCell>
                                <TableCell>
                                    <div class="text-sm">{{ p.user?.name || '-' }}</div>
                                    <div class="text-[10px] text-muted-foreground">{{ p.user?.shift ? (String(p.user.shift).toLowerCase().includes('shift') ? p.user.shift : 'Shift ' + p.user.shift) : '-' }}</div>
                                </TableCell>
                                <TableCell class="font-semibold whitespace-nowrap">
                                    {{ formatWeight(p.berat) }} <span class="text-[10px] font-normal text-muted-foreground">kg</span>
                                </TableCell>
                                <TableCell class="text-center">
                                    <span v-if="p.status == 'selesai'" class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-emerald-100 text-emerald-600">
                                        <CheckCircle2 class="w-3.5 h-3.5" />
                                    </span>
                                    <span v-else-if="p.status == 'menunggu'" class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-amber-100 text-amber-600 animate-pulse">
                                        <Clock class="w-3.5 h-3.5" />
                                    </span>
                                    <span v-else class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-rose-100 text-rose-600">
                                        <AlertCircle class="w-3.5 h-3.5" />
                                    </span>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </div>
                <div v-if="recentPenimbangans.length === 0" class="p-16 text-center">
                    <div class="w-12 h-12 bg-muted rounded-full flex items-center justify-center mx-auto mb-4">
                        <Package class="w-6 h-6 text-muted-foreground" />
                    </div>
                    <p class="text-foreground font-medium">Belum ada aktivitas</p>
                    <p class="text-muted-foreground text-sm mt-1">Data penimbangan akan muncul di sini secara otomatis.</p>
                </div>
            </Card>
        </div>
    </AuthenticatedLayout>
</template>
