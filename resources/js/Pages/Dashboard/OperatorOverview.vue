<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, usePage } from '@inertiajs/vue3';
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
import { 
    Weight, 
    CheckCircle2, 
    Clock, 
    TrendingUp, 
    AlertCircle 
} from 'lucide-vue-next';

const props = defineProps({
    stats: Object,
    moduleStats: Object,
    moduleNames: Object,
    recentPenimbangans: Array,
});

const { auth } = usePage().props;

const successRate = props.stats.total > 0 ? (props.stats.selesai / props.stats.total) * 100 : 0;

const formatNumber = (num) => {
    return new Intl.NumberFormat('id-ID').format(num);
};

const formatWeight = (weight) => {
    return new Intl.NumberFormat('id-ID', { minimumFractionDigits: 3, maximumFractionDigits: 3 }).format(weight);
};
</script>

<template>
    <Head title=" Dashboard Operator" />

    <AuthenticatedLayout>
        <div class="space-y-8">
            <!-- Header Section -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div>
                    <h1 class="text-3xl font-black text-gray-900 tracking-tight">Personal Dashboard</h1>
                    <p class="text-gray-500 font-medium">
                        Hello, <span class="text-blue-600">{{ auth.user.name }}</span>. Here is your overall performance summary.
                    </p>
                </div>
                <div class="flex items-center gap-3">
                     <Badge variant="outline" class="px-4 py-2 bg-white border border-gray-100 rounded-xl shadow-sm text-xs font-bold text-gray-400 uppercase tracking-widest border-none">
                        Shift {{ auth.user.shift ?? '-' }}
                     </Badge>
                </div>
            </div>

            <!-- Personal Summary Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                <Card class="p-6 bg-white border-none rounded-3xl shadow-sm">
                    <div class="w-12 h-12 bg-blue-50 text-blue-600 rounded-2xl flex items-center justify-center mb-4">
                        <Clock class="w-6 h-6" />
                    </div>
                    <h5 class="text-sm font-bold text-gray-400 uppercase tracking-widest mb-1">Total Penimbangan</h5>
                    <p class="text-3xl font-black text-gray-900">{{ formatNumber(stats.total) }}</p>
                </Card>

                <Card class="p-6 bg-white border-none rounded-3xl shadow-sm">
                    <div class="w-12 h-12 bg-emerald-50 text-emerald-600 rounded-2xl flex items-center justify-center mb-4">
                        <Weight class="w-6 h-6" />
                    </div>
                    <h5 class="text-sm font-bold text-gray-400 uppercase tracking-widest mb-1">Total Berat</h5>
                    <p class="text-3xl font-black text-gray-900">
                        {{ formatWeight(stats.total_berat) }} <span class="text-base font-medium text-gray-400">kg</span>
                    </p>
                </Card>

                <Card class="p-6 bg-white border-none rounded-3xl shadow-sm">
                    <div class="w-12 h-12 bg-indigo-50 text-indigo-600 rounded-2xl flex items-center justify-center mb-4">
                        <TrendingUp class="w-6 h-6" />
                    </div>
                    <h5 class="text-sm font-bold text-gray-400 uppercase tracking-widest mb-1">Success Rate</h5>
                    <p class="text-3xl font-black text-gray-900">{{ formatNumber(successRate) }}%</p>
                </Card>

                <Card class="p-6 bg-white border-none rounded-3xl shadow-sm">
                    <div class="w-12 h-12 bg-rose-50 text-rose-600 rounded-2xl flex items-center justify-center mb-4">
                        <AlertCircle class="w-6 h-6" />
                    </div>
                    <h5 class="text-sm font-bold text-gray-400 uppercase tracking-widest mb-1">Invalid Records</h5>
                    <p class="text-3xl font-black text-gray-900">{{ formatNumber(stats.invalid) }}</p>
                </Card>
            </div>

            <!-- Contribution per Module -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <template v-for="(name, type) in moduleNames" :key="type">
                    <Card v-if="moduleStats[type]" class="bg-white border-none rounded-3xl p-6 shadow-sm flex flex-col justify-between">
                        <div>
                            <div class="flex justify-between items-start mb-4">
                                <h3 class="text-lg font-black text-gray-800 leading-tight">{{ name }}</h3>
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Penimbangan</p>
                                    <p class="text-xl font-black text-gray-900">{{ formatNumber(moduleStats[type].total) }}</p>
                                </div>
                                <div>
                                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Berat (kg)</p>
                                    <p class="text-xl font-black text-gray-900">{{ formatWeight(moduleStats[type].total_berat) }}</p>
                                </div>
                            </div>
                        </div>
                    </Card>
                </template>
            </div>

            <!-- Recent Activity Table -->
            <div class="space-y-4">
                <div class="flex items-center gap-3">
                    <div class="h-8 w-1.5 bg-gray-800 rounded-full"></div>
                    <h2 class="text-xl font-black text-gray-900 uppercase tracking-tight">Your Recent Weighings</h2>
                </div>

                <div class="bg-white border-none rounded-3xl shadow-sm overflow-hidden">
                    <Table>
                        <TableHeader class="bg-gray-50">
                            <TableRow>
                                <TableHead class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest">Time</TableHead>
                                <TableHead class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest">Product</TableHead>
                                <TableHead class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest">Weight</TableHead>
                                <TableHead class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest text-center">Status</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="p in recentPenimbangans" :key="p.id" class="hover:bg-gray-50 transition-colors">
                                <TableCell class="px-6 py-4 whitespace-nowrap font-medium text-gray-500">
                                    {{ new Date(p.created_at).toLocaleTimeString('id-ID') }}
                                    <span class="block text-[10px] text-gray-300">{{ new Date(p.created_at).toLocaleDateString('id-ID') }}</span>
                                </TableCell>
                                <TableCell class="px-6 py-4 font-bold text-gray-900">{{ p.produk.nama_produk }}</TableCell>
                                <TableCell class="px-6 py-4 font-black text-gray-900">
                                    {{ formatWeight(p.weight) }} <span class="text-[10px] font-normal text-gray-400 uppercase">kg</span>
                                </TableCell>
                                <TableCell class="px-6 py-4 text-center">
                                    <div v-if="p.status == 'selesai'" class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-emerald-50 text-emerald-600">
                                        <CheckCircle2 class="w-4 h-4 stroke-[3]" />
                                    </div>
                                    <div v-else-if="p.status == 'menunggu'" class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-amber-50 text-amber-600 animate-pulse">
                                        <Clock class="w-4 h-4 stroke-[3]" />
                                    </div>
                                    <div v-else class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-rose-50 text-rose-600">
                                        <AlertCircle class="w-4 h-4 stroke-[3]" />
                                    </div>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                    <div v-if="recentPenimbangans.length === 0" class="p-12 text-center">
                        <p class="text-gray-400 font-medium italic">You haven't recorded any weighings yet.</p>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
