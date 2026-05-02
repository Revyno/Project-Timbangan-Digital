<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
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
    Search
} from 'lucide-vue-next';

const props = defineProps({
    stats: Object,
    moduleStats: Object,
    moduleNames: Object,
    recentPenimbangans: Array,
    produks: Array,
});

const successRate = props.stats.total > 0 ? (props.stats.selesai / props.stats.total) * 100 : 0;

const formatNumber = (num) => {
    return new Intl.NumberFormat('id-ID').format(num);
};

const formatWeight = (weight) => {
    return new Intl.NumberFormat('id-ID', { minimumFractionDigits: 3, maximumFractionDigits: 3 }).format(weight);
};

const getRoute = (type) => {
    const routeMap = {
        'fg': 'fg.dashboard',
        'fg_psn': 'fg-psn.dashboard',
        'fg_surabaya': 'fg-surabaya.dashboard',
        'cs_noodle_sby': 'cs-noodle-sby.dashboard',
        'cs_fg_sby': 'cs-fg-sby.dashboard',
        'incoming_singkong': 'incoming.singkong.dashboard',
        'incoming_rmpm': 'incoming.rmpm.dashboard',
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
                    <Button as="a" :href="route('penimbangan.export')" class="bg-blue-700 hover:bg-blue-800 shadow-sm transition-all">
                        <FileDown class="w-4 h-4 mr-2" />
                        Export CSV
                    </Button>
                </div>
            </div>

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
