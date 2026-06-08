<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { ref, onMounted, onUnmounted } from 'vue';
import { Head, usePage, router } from '@inertiajs/vue3';
import {
    Card,
    CardContent,
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
import {
    Weight,
    CheckCircle2,
    Clock,
    TrendingUp,
    AlertCircle,
    Wifi,
    Activity,
    Package,
    User,
} from 'lucide-vue-next';
import Swal from 'sweetalert2';
import { Link } from '@inertiajs/vue3';
import DashboardChart from '@/Components/ui/chart/DashboardChart.vue';

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

// ── Real-time listener ──
onMounted(() => {
    if (window.Echo) {
        window.Echo.channel('iot-weights')
            .listen('.WeightReceived', (e) => {
                // Only show if this event is for the current operator
                if (e.operator === auth.user.name) {
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
                        title: `Berat ${e.weight || e.berat} kg diterima`,
                        text: `IP: ${e.ip_address}`,
                        showConfirmButton: false,
                        timer: 3000,
                    });

                    router.reload({ only: ['stats', 'moduleStats', 'recentPenimbangans'] });

                    setTimeout(() => { liveIndicator.value = false; }, 3000);
                }
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
</script>

<template>
    <Head title="Dashboard Operator" />

    <AuthenticatedLayout>
        <div class="space-y-6">

            <!-- ── Header ── -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-black text-gray-900 tracking-tight">Dashboard Personal</h1>
                    <p class="text-sm text-gray-500">
                        Halo, <span class="font-bold text-indigo-600">{{ auth.user.name }}</span>. Berikut ringkasan performa Anda.
                    </p>
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
                    <Badge class="px-4 py-2 bg-indigo-50 text-indigo-700 border-none rounded-xl text-xs font-bold">
                        <User class="w-3.5 h-3.5 mr-1.5" /> Shift {{ auth.user.shift ?? '-' }}
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
                <div v-if="lastReceived" class="bg-gradient-to-r from-emerald-500 to-teal-600 rounded-2xl p-4 shadow-lg shadow-emerald-200/50 text-white flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <div class="p-2.5 bg-white/20 rounded-xl backdrop-blur-sm">
                            <Wifi class="w-5 h-5 animate-pulse" />
                        </div>
                        <div>
                            <p class="text-xs font-bold uppercase tracking-widest opacity-80">Data Diterima Real-Time</p>
                            <p class="text-sm font-black mt-0.5">
                                {{ lastReceived.weight }} kg dari Arduino
                            </p>
                        </div>
                    </div>
                    <div class="flex items-center gap-3 text-xs opacity-90">
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
                        <div class="p-2 bg-white/20 rounded-lg"><Package class="w-4 h-4" /></div>
                    </div>
                    <p class="text-3xl font-black">{{ formatNumber(stats.total) }}</p>
                    <p class="text-xs opacity-60 mt-1">Semua penimbangan Anda</p>
                </div>

                <div class="bg-gradient-to-br from-emerald-600 to-teal-700 rounded-2xl p-5 text-white shadow-lg shadow-emerald-200">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-[10px] font-bold uppercase tracking-widest opacity-75">Total Berat</span>
                        <div class="p-2 bg-white/20 rounded-lg"><Weight class="w-4 h-4" /></div>
                    </div>
                    <p class="text-2xl font-black">{{ formatWeight(stats.total_berat) }} <span class="text-sm opacity-70">kg</span></p>
                    <p class="text-xs opacity-60 mt-1">Berat status selesai</p>
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

            <!-- ── Chart ── -->
            <div v-if="chartData && chartData.length > 0" class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <h2 class="text-base font-bold text-gray-900">Grafik Penimbangan</h2>
                    <div class="flex bg-gray-100 p-1 rounded-lg">
                        <Link :href="route('dashboard', { chart_filter: 'week' })" class="px-4 py-1.5 text-xs font-bold rounded-md transition-all" :class="chartFilter === 'week' ? 'bg-white shadow text-indigo-700' : 'text-gray-500 hover:text-gray-900'">Mingguan</Link>
                        <Link :href="route('dashboard', { chart_filter: 'month' })" class="px-4 py-1.5 text-xs font-bold rounded-md transition-all" :class="chartFilter === 'month' ? 'bg-white shadow text-indigo-700' : 'text-gray-500 hover:text-gray-900'">Bulanan</Link>
                    </div>
                </div>
                <div class="p-6">
                    <DashboardChart :data="chartData" />
                </div>
            </div>

            <!-- ── Module Performance ── -->
            <div v-if="Object.keys(moduleStats).length > 0">
                <h2 class="text-base font-bold text-gray-900 mb-4">Kontribusi Per Modul</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <template v-for="(name, type) in moduleNames" :key="type">
                        <div v-if="moduleStats[type]" class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                            <div :class="'bg-gradient-to-r ' + getModuleColor(type)" class="px-5 py-3 text-white">
                                <h3 class="text-sm font-black">{{ name }}</h3>
                            </div>
                            <div class="p-4 space-y-2">
                                <div class="flex items-center justify-between">
                                    <span class="text-xs text-gray-500">Penimbangan</span>
                                    <span class="text-sm font-black text-gray-900">{{ formatNumber(moduleStats[type].total) }}</span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-xs text-gray-500">Berat</span>
                                    <span class="text-sm font-black text-gray-900">{{ formatWeight(moduleStats[type].total_berat) }} kg</span>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            <!-- ── Recent Activity ── -->
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                    <div>
                        <h2 class="text-base font-bold text-gray-900">Penimbangan Terakhir Anda</h2>
                        <p class="text-xs text-gray-400 mt-0.5">Data terbaru akan muncul otomatis tanpa refresh.</p>
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
                                <TableHead class="px-5 py-3 text-xs font-bold text-gray-500 uppercase whitespace-nowrap">Waktu</TableHead>
                                <TableHead class="px-5 py-3 text-xs font-bold text-gray-500 uppercase whitespace-nowrap">Produk</TableHead>
                                <TableHead class="px-5 py-3 text-xs font-bold text-gray-500 uppercase whitespace-nowrap">Berat</TableHead>
                                <TableHead class="px-5 py-3 text-xs font-bold text-gray-500 uppercase whitespace-nowrap text-center">Status</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="p in recentPenimbangans" :key="p.id" class="hover:bg-indigo-50/30 transition-colors border-b border-gray-50">
                                <TableCell class="px-5 py-3.5 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-700">
                                        {{ new Date(p.created_at).toLocaleTimeString('id-ID') }}
                                    </div>
                                    <div class="text-[10px] text-gray-400">
                                        {{ new Date(p.created_at).toLocaleDateString('id-ID') }}
                                    </div>
                                </TableCell>
                                <TableCell class="px-5 py-3.5 font-semibold text-gray-900 text-sm">{{ p.produk?.nama_produk || '-' }}</TableCell>
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
                    <p class="text-gray-500 font-bold">Belum ada penimbangan</p>
                    <p class="text-gray-400 text-sm mt-1">Data akan muncul di sini saat Anda mulai menimbang.</p>
                </div>
            </div>

        </div>
    </AuthenticatedLayout>
</template>
