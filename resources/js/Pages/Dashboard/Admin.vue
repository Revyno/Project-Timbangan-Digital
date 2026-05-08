<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { 
    Card, 
    CardContent, 
    CardHeader, 
    CardTitle, 
    CardDescription 
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
    FileDown, 
    TrendingUp, 
    Weight, 
    CheckCircle2, 
    AlertCircle, 
    Clock, 
    ChevronRight,
    Search,
    ClipboardList,
    Filter,
    Users,
    MapPin,
    RotateCcw
} from 'lucide-vue-next';

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

import { computed } from 'vue';

const filteredOperators = computed(() => {
    if (!filterForm.value.module) return props.operators;
    return props.operators.filter(op => op.tipe === filterForm.value.module);
});

// Shows only shifts belonging to operators in the selected module
const filteredShifts = computed(() => {
    if (!filterForm.value.module) return props.shifts;
    return props.shiftsPerModule[filterForm.value.module] || [];
});

const applyGlobalFilter = () => {
    // If operator doesn't belong to newly selected module, clear it
    if (filterForm.value.module && filterForm.value.operator) {
        const isValid = props.operators.some(op => op.id === filterForm.value.operator && op.tipe === filterForm.value.module);
        if (!isValid) filterForm.value.operator = '';
    }
    // If shift doesn't exist in new module's shifts, clear it
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

const successRate = props.stats.total > 0 ? (props.stats.selesai / props.stats.total) * 100 : 0;

const formatNumber = (num) => {
    return new Intl.NumberFormat('id-ID').format(num);
};

const formatWeight = (weight) => {
    return new Intl.NumberFormat('id-ID', { minimumFractionDigits: 3, maximumFractionDigits: 3 }).format(weight);
};

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
</script>

<template>
    <Head title="Admin Dashboard" />

    <AuthenticatedLayout>
        <div class="space-y-8">
            <!-- Header Section -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div>
                    <h1 class="text-3xl font-black text-gray-900 tracking-tight">Overview Panel</h1>
                    <p class="text-gray-500 font-medium">Real-time overall operational summary across all locations.</p>
                </div>
                <div class="flex flex-col md:flex-row items-start md:items-center gap-4">
                    <div class="flex items-center gap-3">
                        <!-- Quick Stats 1 -->
                        <div class="flex items-center p-3 bg-white border border-gray-200 rounded-lg shadow-sm hover:bg-gray-50">
                            <div class="p-2 mr-3 bg-green-100 rounded-full">
                                <Clock class="w-4 h-4 text-green-600" />
                            </div>
                            <div>
                                <p class="text-xs font-medium text-gray-500 uppercase">Total Items</p>
                                <p class="text-sm font-bold text-gray-900">{{ formatNumber(stats.total) }} items</p>
                            </div>
                        </div>

                        <!-- Quick Stats 2 -->
                        <div class="flex items-center p-3 bg-white border border-gray-200 rounded-lg shadow-sm hover:bg-gray-50">
                            <div class="p-2 mr-3 bg-blue-100 rounded-full">
                                <Weight class="w-4 h-4 text-blue-600" />
                            </div>
                            <div>
                                <p class="text-xs font-medium text-gray-500 uppercase">Total Berat</p>
                                <p class="text-sm font-bold text-gray-900">{{ formatWeight(stats.total_berat) }} kg</p>
                            </div>
                        </div>
                    </div>
                    <Button as="a" :href="route('penimbangan.export', filterForm)" class="bg-blue-700 hover:bg-blue-800 shadow-sm transition-all whitespace-nowrap">
                        <FileDown class="w-4 h-4 mr-2" />
                        Export CSV
                    </Button>
                </div>
            </div>

            <!-- Global Filters Card -->
            <Card class="bg-white border border-gray-200 shadow-sm rounded-xl">
                <CardContent class="p-4 flex flex-col md:flex-row gap-4 md:items-end">
                    <div class="flex-1 space-y-1.5">
                        <label class="text-xs font-bold text-gray-500 uppercase flex items-center gap-1.5">
                            <MapPin class="w-3.5 h-3.5" /> Modul / Lokasi
                        </label>
                        <select v-model="filterForm.module" @change="applyGlobalFilter" class="block w-full py-2 pl-3 pr-8 text-sm text-gray-900 transition-all bg-gray-50 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
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
                    <div class="flex-1 space-y-1.5">
                        <label class="text-xs font-bold text-gray-500 uppercase flex items-center gap-1.5">
                            <Clock class="w-3.5 h-3.5" /> Shift
                        </label>
                        <select v-model="filterForm.shift" @change="applyGlobalFilter" class="block w-full py-2 pl-3 pr-8 text-sm text-gray-900 transition-all bg-gray-50 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                            <option value="">Semua Shift</option>
                            <option v-for="s in filteredShifts" :key="s" :value="s">{{ s }}</option>
                        </select>
                    </div>
                    <div class="flex-1 space-y-1.5">
                        <label class="text-xs font-bold text-gray-500 uppercase flex items-center gap-1.5">
                            <Users class="w-3.5 h-3.5" /> Operator
                        </label>
                        <select v-model="filterForm.operator" @change="applyGlobalFilter" class="block w-full py-2 pl-3 pr-8 text-sm text-gray-900 transition-all bg-gray-50 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                            <option value="">Semua Operator</option>
                            <option v-for="op in filteredOperators" :key="op.id" :value="op.id">{{ op.name }} ({{ op.shift ? 'Shift '+op.shift : 'No Shift' }})</option>
                        </select>
                    </div>
                    <div>
                        <Button @click="resetGlobalFilter" variant="outline" class="w-full md:w-auto font-bold text-gray-600 border-gray-300 hover:bg-gray-100 rounded-lg py-2">
                            <RotateCcw class="w-4 h-4 mr-2" />
                            Reset
                        </Button>
                    </div>
                </CardContent>
            </Card>

            <!-- Global Summary Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                <!-- Card 1 -->
                <Card class="hover:bg-gray-50 transition-all border-none shadow-sm bg-white">
                    <CardContent class="p-6 flex items-center gap-4">
                        <div class="w-16 h-16 bg-blue-50 text-blue-600 rounded-lg flex items-center justify-center shrink-0">
                            <ClipboardList class="w-8 h-8" />
                        </div>
                        <div>
                            <h5 class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-1">Total Penimbangan</h5>
                            <div class="flex items-baseline gap-1">
                                <p class="text-3xl font-black text-gray-900 leading-none">{{ formatNumber(stats.total) }}</p>
                                <span class="text-xs font-bold text-gray-400 uppercase">Items</span>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Card 2 -->
                <Card class="hover:bg-gray-50 transition-all border-none shadow-sm bg-white">
                    <CardContent class="p-6 flex items-center gap-4">
                        <div class="w-16 h-16 bg-emerald-50 text-emerald-600 rounded-lg flex items-center justify-center shrink-0">
                            <Weight class="w-8 h-8" />
                        </div>
                        <div>
                            <h5 class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-1">Total Berat</h5>
                            <div class="flex items-baseline gap-1">
                                <p class="text-3xl font-black text-gray-900 leading-none">{{ formatWeight(stats.total_berat) }}</p>
                                <span class="text-xs font-bold text-gray-400 uppercase">KG</span>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Card 3 -->
                <Card class="hover:bg-gray-50 transition-all border-none shadow-sm bg-white">
                    <CardContent class="p-6 flex items-center gap-4">
                        <div class="w-16 h-16 bg-indigo-50 text-indigo-600 rounded-lg flex items-center justify-center shrink-0">
                            <TrendingUp class="w-8 h-8" />
                        </div>
                        <div>
                            <h5 class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-1">Tingkat Berhasil</h5>
                            <p class="text-3xl font-black text-gray-900 leading-none">{{ formatNumber(successRate) }}%</p>
                            <span class="text-[10px] font-bold text-indigo-600 mt-1 block tracking-tight">({{ stats.selesai }}/{{ stats.total }})</span>
                        </div>
                    </CardContent>
                </Card>

                <!-- Card 4 -->
                <Card class="hover:bg-gray-50 transition-all border-none shadow-sm bg-white">
                    <CardContent class="p-6 flex items-center gap-4">
                        <div class="w-16 h-16 bg-rose-50 text-rose-600 rounded-lg flex items-center justify-center shrink-0">
                            <AlertCircle class="w-8 h-8" />
                        </div>
                        <div>
                            <h5 class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-1">Status Invalid</h5>
                            <div class="flex items-baseline gap-1">
                                <p class="text-3xl font-black text-gray-900 leading-none">{{ formatNumber(stats.invalid) }}</p>
                                <span class="text-xs font-bold text-rose-500 uppercase">Records</span>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Bar Chart Overview Section -->
            <div class="space-y-4">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div class="flex items-center gap-3">
                        <div class="h-8 w-1.5 bg-indigo-600 rounded-full"></div>
                        <h2 class="text-xl font-black text-gray-900 uppercase tracking-tight">Overview Penimbangan</h2>
                    </div>
                    <div class="flex bg-gray-100 p-1 rounded-lg">
                        <Link :href="route('dashboard', { ...filterForm, chart_filter: 'week' })" class="px-4 py-1.5 text-xs font-bold rounded-md transition-all" :class="chartFilter === 'week' ? 'bg-white shadow text-indigo-700' : 'text-gray-500 hover:text-gray-900'">Mingguan</Link>
                        <Link :href="route('dashboard', { ...filterForm, chart_filter: 'month' })" class="px-4 py-1.5 text-xs font-bold rounded-md transition-all" :class="chartFilter === 'month' ? 'bg-white shadow text-indigo-700' : 'text-gray-500 hover:text-gray-900'">Bulanan</Link>
                    </div>
                </div>
                
                <Card class="p-4 sm:p-6 bg-white border-none shadow-sm rounded-3xl">
                    <div class="overflow-x-auto -mx-2 sm:mx-0">
                        <div class="h-56 sm:h-64 flex items-end gap-1.5 sm:gap-4 justify-between pt-8 relative" :style="{ minWidth: chartData.length * 44 + 'px' }">
                            <!-- Y-Axis Lines -->
                            <div class="absolute inset-0 flex flex-col justify-between pointer-events-none pb-6">
                                <div class="w-full h-px bg-gray-100 border-b border-dashed border-gray-200"></div>
                                <div class="w-full h-px bg-gray-100 border-b border-dashed border-gray-200"></div>
                                <div class="w-full h-px bg-gray-100 border-b border-dashed border-gray-200"></div>
                                <div class="w-full h-px bg-gray-100 border-b border-solid border-gray-200"></div>
                            </div>

                            <!-- Bars -->
                            <div v-for="data in chartData" :key="data.name" class="relative flex flex-col items-center flex-1 h-full justify-end group z-10" style="min-width: 36px">
                                <div class="w-full max-w-[40px] sm:max-w-[48px] bg-indigo-100 rounded-t-sm relative transition-all duration-500 group-hover:bg-indigo-200"
                                     :style="{ height: Math.max((data.total / Math.max(...chartData.map(d => d.total), 1)) * 100, 5) + '%' }">
                                    <div class="absolute inset-x-0 bottom-0 bg-indigo-600 rounded-t-sm transition-all duration-500 group-hover:bg-indigo-700"
                                         :style="{ height: Math.max((data.berat / Math.max(...chartData.map(d => d.berat), 1)) * 100, 5) + '%' }"></div>
                                    
                                    <!-- Tooltip -->
                                    <div class="absolute -top-12 left-1/2 -translate-x-1/2 bg-gray-900 text-white text-[10px] px-2 sm:px-3 py-1.5 rounded-lg opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap pointer-events-none shadow-xl z-50">
                                        <p class="font-bold mb-0.5">{{ data.total }} items</p>
                                        <p class="text-indigo-200">{{ formatWeight(data.berat) }} kg</p>
                                        <div class="absolute -bottom-1 left-1/2 -translate-x-1/2 border-[4px] border-transparent border-t-gray-900"></div>
                                    </div>
                                </div>
                                <span class="text-[9px] sm:text-[10px] font-bold text-gray-500 mt-2 truncate w-full text-center px-0.5">{{ data.name }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="mt-4 sm:mt-8 flex items-center justify-center gap-6">
                        <div class="flex items-center gap-2">
                            <div class="w-3 h-3 bg-indigo-100 rounded"></div>
                            <span class="text-xs text-gray-500 font-bold uppercase tracking-widest">Total Item</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <div class="w-3 h-3 bg-indigo-600 rounded"></div>
                            <span class="text-xs text-gray-500 font-bold uppercase tracking-widest">Total Berat (kg)</span>
                        </div>
                    </div>
                </Card>
            </div>

            <!-- Module Breakdown Section -->
            <div class="space-y-4">
                <div class="flex items-center gap-3">
                    <div class="h-8 w-1.5 bg-blue-600 rounded-full"></div>
                    <h2 class="text-xl font-black text-gray-900 uppercase tracking-tight">Breakdown Per Modul</h2>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <Card v-for="(name, type) in moduleNames" :key="type" class="hover:bg-gray-50 transition-colors border-gray-200 shadow-sm flex flex-col justify-between">
                        <CardHeader class="pb-2">
                            <div class="flex justify-between items-start">
                                <CardTitle class="text-lg font-black text-gray-800 leading-tight">{{ name }}</CardTitle>
                                <Badge variant="secondary" class="bg-blue-100 text-blue-800 text-[10px] font-black uppercase rounded-full border-none">
                                    {{ type }}
                                </Badge>
                            </div>
                        </CardHeader>
                        <CardContent class="pb-4">
                            <div class="space-y-2">
                                <div class="flex items-center p-2.5 bg-gray-50 border border-gray-100 rounded-lg">
                                    <div class="w-8 h-8 bg-green-100 text-green-600 rounded-lg flex items-center justify-center shrink-0 mr-3">
                                        <ClipboardList class="w-4 h-4" />
                                    </div>
                                    <div>
                                        <p class="text-[9px] font-bold text-gray-400 uppercase tracking-widest">Penimbangan</p>
                                        <p class="text-sm font-black text-gray-900">
                                            {{ formatNumber(moduleStats[type]?.total || 0) }} 
                                            <span class="text-[9px] text-gray-400">Items</span>
                                        </p>
                                    </div>
                                </div>

                                <div class="flex items-center p-2.5 bg-gray-50 border border-gray-100 rounded-lg">
                                    <div class="w-8 h-8 bg-blue-100 text-blue-600 rounded-lg flex items-center justify-center shrink-0 mr-3">
                                        <Weight class="w-4 h-4" />
                                    </div>
                                    <div>
                                        <p class="text-[9px] font-bold text-gray-400 uppercase tracking-widest">Total Berat</p>
                                        <p class="text-sm font-black text-gray-900">
                                            {{ formatWeight(moduleStats[type]?.total_berat || 0) }} 
                                            <span class="text-[9px] text-gray-400">KG</span>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                        <div class="px-6 py-3 border-t border-gray-100 flex items-center justify-between">
                            <div class="flex items-center">
                                <span class="flex w-2 h-2 bg-blue-600 rounded-full mr-1.5"></span>
                                <span class="text-[10px] font-bold text-blue-600 uppercase">Live Status</span>
                            </div>
                            <Link :href="route(getRoute(type))" class="text-[10px] font-bold text-gray-500 hover:text-blue-600 uppercase transition-colors flex items-center">
                                View Details <ChevronRight class="w-3 h-3 ml-1" />
                            </Link>
                        </div>
                    </Card>
                </div>
            </div>

            <!-- Recent Activity Table -->
            <div class="space-y-4">
                <div class="flex items-center gap-3">
                    <div class="h-8 w-1.5 bg-gray-800 rounded-full"></div>
                    <h2 class="text-xl font-black text-gray-900 uppercase tracking-tight">Aktivitas Terakhir (Semua Modul)</h2>
                </div>

                <div class="bg-white border border-gray-100 rounded-3xl shadow-sm overflow-hidden">
                    <Table>
                        <TableHeader class="bg-gray-50">
                            <TableRow>
                                <TableHead class="text-[10px] font-black text-gray-400 uppercase tracking-widest px-6 py-4">No.</TableHead>
                                <TableHead class="text-[10px] font-black text-gray-400 uppercase tracking-widest px-6 py-4">Waktu</TableHead>
                                <TableHead class="text-[10px] font-black text-gray-400 uppercase tracking-widest px-6 py-4">Modul</TableHead>
                                <TableHead class="text-[10px] font-black text-gray-400 uppercase tracking-widest px-6 py-4">Produk</TableHead>
                                <TableHead class="text-[10px] font-black text-gray-400 uppercase tracking-widest px-6 py-4">Operator</TableHead>
                                <TableHead class="text-[10px] font-black text-gray-400 uppercase tracking-widest px-6 py-4">Berat</TableHead>
                                <TableHead class="text-[10px] font-black text-gray-400 uppercase tracking-widest px-6 py-4 text-center">Status</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="(p, index) in recentPenimbangans" :key="p.id" class="hover:bg-gray-50 transition-colors">
                                <TableCell class="px-6 py-4 font-bold text-gray-400">{{ index + 1 }}</TableCell>
                                <TableCell class="px-6 py-4 whitespace-nowrap font-medium text-gray-500">
                                    {{ new Date(p.created_at).toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' }) }}
                                    <span class="block text-[10px] text-gray-300">
                                        {{ new Date(p.created_at).toLocaleDateString('id-ID') }}
                                    </span>
                                </TableCell>
                                <TableCell class="px-6 py-4">
                                    <Badge variant="outline" class="px-2 py-1 bg-gray-100 text-[10px] font-bold text-gray-600 rounded-md uppercase border-none">
                                        {{ moduleNames[p.user.tipe] || p.user.tipe }}
                                    </Badge>
                                </TableCell>
                                <TableCell class="px-6 py-4 font-bold text-gray-900">{{ p.produk.nama_produk }}</TableCell>
                                <TableCell class="px-6 py-4 text-gray-600">
                                    {{ p.user.name }}
                                    <span class="block text-[10px] text-gray-400 italic">Shift {{ p.user.shift ?? '-' }}</span>
                                </TableCell>
                                <TableCell class="px-6 py-4 font-black text-gray-900">
                                    {{ formatWeight(p.berat) }} <span class="text-[10px] font-normal text-gray-400 uppercase">kg</span>
                                </TableCell>
                                <TableCell class="px-6 py-4 text-center">
                                    <div v-if="p.status == 'selesai'" class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-emerald-50 text-emerald-600">
                                        <CheckCircle2 class="w-4 h-4 stroke-[3]" />
                                    </div>
                                    <div v-else-if="p.status == 'menunggu'" class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-amber-50 text-amber-600 animate-pulse">
                                        <Clock class="w-4 h-4 stroke-[3]" />
                                    </div>
                                    <div v-else class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-rose-50 text-rose-600">
                                        <X class="w-4 h-4 stroke-[3]" />
                                    </div>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                    <div v-if="recentPenimbangans.length === 0" class="p-12 text-center">
                        <p class="text-gray-400 font-medium italic">Belum ada aktivitas penimbangan hari ini.</p>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
