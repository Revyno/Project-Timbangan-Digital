<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { ref, computed } from 'vue';
import { Head, usePage } from '@inertiajs/vue3';
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
    Package,
    Scale,
    TrendingUp,
    AlertCircle,
    Activity,
    Wifi,
    CheckCircle2,
    Clock,
    User,
} from 'lucide-vue-next';
import Swal from 'sweetalert2';
import { Link } from '@inertiajs/vue3';
import DashboardChart from '@/Components/ui/chart/DashboardChart.vue';
import { formatWeight } from '@/utils/format.js';
import { useRealtimeReload } from '@/composables/useRealtimeReload.js';

const props = defineProps({
    stats: Object,
    moduleStats: Object,
    moduleNames: Object,
    recentPenimbangans: Array,
    chartData: Array,
    chartFilter: String,
});

const { auth } = usePage().props;

const liveIndicator = ref(false);
const lastReceived = ref(null);

// ── Real-time listener (WebSocket + fallback polling REST) ──
const { connected } = useRealtimeReload({
    channel: 'iot-weights',
    only: ['stats', 'moduleStats', 'recentPenimbangans', 'chartData'],
    onEvent: (e) => {
        // Hanya proses jika event ini milik operator yang sedang login
        if (e.operator !== auth.user.name) return false;

        liveIndicator.value = true;
        lastReceived.value = {
            weight: e.weight || e.berat,
            ip: e.ip_address,
            time: new Date().toLocaleTimeString('id-ID'),
        };

        Swal.fire({
            toast: true,
            position: 'top-end',
            icon: 'success',
            title: `Berat ${formatWeight(e.weight || e.berat)} kg diterima`,
            text: `IP: ${e.ip_address}`,
            showConfirmButton: false,
            timer: 3000,
        });

        setTimeout(() => { liveIndicator.value = false; }, 3000);
    },
});

const successRate = computed(() => props.stats.total > 0 ? (props.stats.selesai / props.stats.total) * 100 : 0);

const formatNumber = (num) => new Intl.NumberFormat('id-ID').format(num);
</script>

<template>
    <Head title="Dashboard Operator" />

    <AuthenticatedLayout>
        <div class="space-y-6">

            <!-- ── Header ── -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div>
                    <h1 class="font-display text-4xl tracking-wider text-primary drop-shadow-sm">Overview Operator</h1>
                    <p class="font-mono text-sm font-bold text-muted-foreground mt-1">Ringkasan operasional penimbangan Anda hari ini.</p>
                </div>
                <div class="flex items-center gap-3">
                    <div class="flex items-center gap-2 px-3 py-1.5 bg-accent text-accent-foreground rounded-md border">
                        <span class="relative flex h-2 w-2">
                            <span :class="liveIndicator ? 'animate-ping bg-emerald-500' : (connected ? 'bg-emerald-500' : 'bg-amber-500')" class="absolute inline-flex h-full w-full rounded-full opacity-75"></span>
                            <span :class="liveIndicator ? 'bg-emerald-500' : (connected ? 'bg-emerald-500' : 'bg-amber-500')" class="relative inline-flex rounded-full h-2 w-2"></span>
                        </span>
                        <span class="text-xs font-semibold uppercase tracking-wider">{{ connected ? 'Live' : 'Polling' }}</span>
                    </div>
                    <Badge variant="secondary" class="gap-1.5">
                        <User class="w-3.5 h-3.5" /> {{ auth.user.shift ? (String(auth.user.shift).toLowerCase().includes('shift') ? auth.user.shift : 'Shift ' + auth.user.shift) : '-' }}
                    </Badge>
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
                <div v-if="lastReceived" class="bg-emerald-50/50 dark:bg-emerald-950/20 text-card-foreground border border-emerald-200 rounded-xl p-4 shadow-sm flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 relative overflow-hidden">
                    <div class="absolute inset-y-0 left-0 w-1 bg-emerald-500"></div>
                    <div class="flex items-center gap-3">
                        <div class="p-2.5 bg-emerald-100 text-emerald-600 rounded-lg">
                            <Wifi class="w-5 h-5 animate-pulse" />
                        </div>
                        <div>
                            <p class="text-[10px] font-semibold text-muted-foreground uppercase tracking-widest">Data Diterima Real-Time</p>
                            <p class="text-sm font-bold mt-0.5">
                                {{ formatWeight(lastReceived.weight) }} kg dari Arduino
                            </p>
                        </div>
                    </div>
                    <div class="flex items-center gap-3 text-xs font-medium text-muted-foreground">
                        <Badge variant="outline">IP: {{ lastReceived.ip }}</Badge>
                        <Badge variant="outline">{{ lastReceived.time }}</Badge>
                    </div>
                </div>
            </transition>

            <!-- ── Stats Cards ── -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                <Card class="bg-primary/10 border-2 border-primary rounded-2xl shadow-[4px_4px_0_0_#3B82F6] hover:translate-y-[-2px] transition-transform">
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="font-display text-xl tracking-wider text-primary">Total Penimbangan</CardTitle>
                        <Package class="h-6 w-6 text-primary" />
                    </CardHeader>
                    <CardContent>
                        <div class="font-display text-4xl text-primary mt-2">{{ formatNumber(stats.total) }}</div>
                        <p class="font-mono text-xs font-bold text-primary/80 mt-1">Semua penimbangan Anda</p>
                    </CardContent>
                </Card>

                <Card class="bg-violet-500/10 border-2 border-violet-500 rounded-2xl shadow-[4px_4px_0_0_#8B5CF6] hover:translate-y-[-2px] transition-transform">
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="font-display text-xl tracking-wider text-violet-600">Total Berat</CardTitle>
                        <Scale class="h-6 w-6 text-violet-600" />
                    </CardHeader>
                    <CardContent>
                        <div class="font-display text-4xl text-violet-600 mt-2">{{ formatWeight(stats.total_berat) }} <span class="text-xl">kg</span></div>
                        <p class="font-mono text-xs font-bold text-violet-600/80 mt-1">Berat status selesai</p>
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

            <!-- ── Chart ── -->
            <Card v-if="chartData && chartData.length > 0" class="overflow-hidden bg-gradient-to-br from-indigo-50 to-blue-50 border-indigo-200 shadow-sm dark:from-indigo-950/40 dark:to-blue-950/40 dark:border-indigo-800">
                <div class="px-6 py-4 border-b flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <h2 class="text-base font-semibold">Grafik Penimbangan</h2>
                    <div class="flex gap-1 bg-muted p-1 rounded-md">
                        <Link :href="route('dashboard', { chart_filter: 'week' })">
                            <Button variant="ghost" size="sm" class="h-7 text-xs" :class="chartFilter === 'week' ? 'bg-background shadow-sm' : ''">Mingguan</Button>
                        </Link>
                        <Link :href="route('dashboard', { chart_filter: 'month' })">
                            <Button variant="ghost" size="sm" class="h-7 text-xs" :class="chartFilter === 'month' ? 'bg-background shadow-sm' : ''">Bulanan</Button>
                        </Link>
                    </div>
                </div>
                <div class="p-6">
                    <DashboardChart :data="chartData" />
                </div>
            </Card>

            <!-- ── Module Performance ── -->
            <div v-if="Object.keys(moduleStats).length > 0">
                <h2 class="text-lg font-semibold tracking-tight mb-4">Kontribusi Per Modul</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <template v-for="(name, type) in moduleNames" :key="type">
                        <Card v-if="moduleStats[type]" class="border-2 border-black rounded-xl shadow-[2px_2px_0_0_#000000] hover:translate-y-[-2px] transition-transform">
                            <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                                <CardTitle class="font-display text-lg tracking-wider">{{ name }}</CardTitle>
                                <Package class="w-5 h-5 text-black" />
                            </CardHeader>
                            <CardContent>
                                <div class="space-y-3 mt-2">
                                    <div class="flex items-center justify-between">
                                        <span class="font-mono text-xs font-bold text-muted-foreground">Penimbangan</span>
                                        <span class="font-display text-xl text-black">{{ formatNumber(moduleStats[type].total) }}</span>
                                    </div>
                                    <div class="flex items-center justify-between">
                                        <span class="font-mono text-xs font-bold text-muted-foreground">Berat</span>
                                        <span class="font-display text-xl text-black">{{ formatWeight(moduleStats[type].total_berat) }} kg</span>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                    </template>
                </div>
            </div>

            <Card class="overflow-hidden bg-gradient-to-br from-slate-50 to-gray-50 border-slate-200 shadow-sm dark:from-slate-900 dark:to-gray-900 dark:border-slate-800">
                <CardHeader class="flex flex-row items-center justify-between border-b px-6 py-4">
                    <div>
                        <CardTitle class="font-display text-xl tracking-wider">Penimbangan Terakhir Anda</CardTitle>
                        <p class="font-mono text-xs font-bold text-muted-foreground mt-1">Data terbaru akan muncul otomatis tanpa refresh.</p>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <Activity :class="connected ? 'text-emerald-500' : 'text-amber-500'" class="w-3.5 h-3.5" />
                        <span :class="connected ? 'text-emerald-600' : 'text-amber-600'" class="text-xs font-medium">
                            {{ connected ? 'Real-Time (WebSocket)' : 'Auto-Refresh (Polling)' }}
                        </span>
                    </div>
                </CardHeader>
                <div class="overflow-x-auto">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Waktu</TableHead>
                                <TableHead>Produk</TableHead>
                                <TableHead>Berat</TableHead>
                                <TableHead class="text-center">Status</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="p in recentPenimbangans" :key="p.id">
                                <TableCell class="whitespace-nowrap">
                                    <div class="text-sm font-medium">
                                        {{ new Date(p.created_at).toLocaleTimeString('id-ID') }}
                                    </div>
                                    <div class="text-[10px] text-muted-foreground">
                                        {{ new Date(p.created_at).toLocaleDateString('id-ID') }}
                                    </div>
                                </TableCell>
                                <TableCell class="font-medium text-sm">{{ p.produk?.nama_produk || '-' }}</TableCell>
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
                    <p class="text-foreground font-medium">Belum ada penimbangan</p>
                    <p class="text-muted-foreground text-sm mt-1">Data akan muncul di sini saat Anda mulai menimbang.</p>
                </div>
            </Card>

        </div>
    </AuthenticatedLayout>
</template>
