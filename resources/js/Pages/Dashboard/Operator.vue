<script setup>
import { ref, computed } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, usePage, router } from '@inertiajs/vue3';
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
    User, 
    Settings2, 
    RotateCcw, 
    LogOut, 
    Play, 
    CheckCircle2, 
    AlertCircle,
    Loader2
} from 'lucide-vue-next';

const props = defineProps({
    produks: Array,
    totalShift: Number,
    totalBerat: Number,
    activePenimbangan: Object,
    lastSession: Object,
    penimbangans: Object,
    storeRoute: { type: String, default: 'penimbangan.store' },
    nextRoute: { type: String, default: 'penimbangan.next' },
    stopRoute: { type: String, default: 'penimbangan.stop' },
});

const { auth } = usePage().props;

const form = useForm({
    produk_id: props.activePenimbangan?.produk_id || props.lastSession?.produk_id || '',
    kode_produksi: props.activePenimbangan?.kode_produksi || props.lastSession?.kode_produksi || '',
    tanggal_expired: props.activePenimbangan?.tanggal_expired || props.lastSession?.tanggal_expired || '',
});

const startSession = () => {
    form.post(route(props.storeRoute));
};

const nextSession = () => {
    router.post(route(props.nextRoute));
};

const stopSession = () => {
    if (confirm('Apakah Anda yakin ingin mengakhiri shift? Akun akan dikunci sampai besok.')) {
        router.post(route(props.stopRoute));
    }
};

const formatWeight = (weight) => {
    if (!weight || weight <= 0) return 'Belum ditimbang';
    return new Intl.NumberFormat('id-ID', { minimumFractionDigits: 3, maximumFractionDigits: 3 }).format(weight) + ' kg';
};

const formatDate = (date) => {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' });
};

const formatDateTime = (date) => {
    return new Date(date).toLocaleTimeString('id-ID', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit', second: '2-digit' });
};
</script>

<template>
    <Head title="Operator Dashboard" />

    <AuthenticatedLayout>
        <div class="space-y-6">
            <!-- Status Overview -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <!-- Operator Card -->
                <Card class="p-5 bg-gradient-to-br from-blue-600 to-indigo-700 rounded-2xl shadow-lg text-white border-none">
                    <div class="flex items-center gap-4 mb-4">
                        <div class="p-3 bg-white/20 rounded-xl">
                            <User class="w-6 h-6 text-white" />
                        </div>
                        <div>
                            <h5 class="text-xs font-bold uppercase tracking-widest opacity-70">Operator Aktif</h5>
                            <p class="text-xl font-black">{{ auth.user.name }}</p>
                        </div>
                    </div>
                    <div class="text-xs bg-black/20 p-3 rounded-xl border border-white/10">
                        <div class="flex justify-between mb-1">
                            <span>Shift Saat Ini:</span>
                            <span class="font-bold">{{ auth.user.shift }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span>Waktu Shift:</span>
                            <span class="font-bold">{{ auth.user.shift_start }} - {{ auth.user.shift_end }}</span>
                        </div>
                    </div>
                </Card>

                <!-- Manual Session Control -->
                <Card class="md:col-span-2 p-6 bg-white border-none rounded-3xl shadow-xl">
                    <div class="flex items-center gap-3 mb-6">
                        <div class="p-2 bg-indigo-100 rounded-lg">
                            <Settings2 class="w-5 h-5 text-indigo-600" />
                        </div>
                        <h3 class="text-lg font-bold text-gray-900">Kontrol Sesi Penimbangan</h3>
                    </div>

                    <div v-if="activePenimbangan" class="p-5 bg-emerald-50 border border-emerald-100 rounded-2xl">
                        <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                            <div>
                                <span class="text-[10px] font-black text-emerald-600 uppercase tracking-widest">Sesi Aktif</span>
                                <h4 class="text-2xl font-black text-gray-900 mt-1">{{ activePenimbangan.produk?.nama_produk }}</h4>
                                <div class="flex flex-wrap gap-4 mt-2">
                                    <div class="flex items-center gap-2">
                                        <span class="text-xs text-gray-500">KP:</span>
                                        <span class="text-xs font-bold text-gray-900">{{ activePenimbangan.kode_produksi }}</span>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <span class="text-xs text-gray-500">Expired:</span>
                                        <span class="text-xs font-bold text-gray-900">{{ formatDate(activePenimbangan.tanggal_expired) }}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="flex flex-col sm:flex-row gap-3 w-full md:w-auto">
                                <Button @click="nextSession" class="bg-green-600 hover:bg-green-700 text-white font-bold rounded-xl shadow-lg shadow-green-500/20 py-5 px-6 w-full sm:w-auto justify-center">
                                    <RotateCcw class="w-5 h-5 mr-2" />
                                    Ganti Produk
                                </Button>

                                <Button @click="stopSession" variant="destructive" class="bg-red-600 hover:bg-red-700 text-white font-bold rounded-xl shadow-lg shadow-red-200 py-5 px-6 w-full sm:w-auto justify-center">
                                    <LogOut class="w-5 h-5 mr-2" />
                                    Selesai Shift
                                </Button>
                            </div>
                        </div>
                        <div class="mt-4 p-3 bg-white/50 rounded-xl border border-emerald-100">
                            <p class="text-sm text-emerald-700 font-medium flex items-center gap-2">
                                <Clock class="w-4 h-4 animate-pulse" />
                                Sistem siap menerima data berat dari timbangan IoT untuk LOT ini.
                            </p>
                        </div>
                    </div>

                    <form v-else @submit.prevent="startSession" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-2 ml-1">Pilih Produk</label>
                            <select v-model="form.produk_id" required class="w-full bg-gray-50 border border-gray-200 text-gray-900 text-sm rounded-xl focus:ring-indigo-500 focus:border-indigo-500 block p-3">
                                <option value="">-- Pilih Produk --</option>
                                <option v-for="p in produks" :key="p.id" :value="p.id">{{ p.nama_produk }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-2 ml-1">Kode Produksi (KP)</label>
                            <input v-model="form.kode_produksi" type="text" required placeholder="Contoh: KP-20240423-001" class="w-full bg-gray-50 border border-gray-200 text-gray-900 text-sm rounded-xl focus:ring-indigo-500 focus:border-indigo-500 block p-3">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-2 ml-1">Tanggal Expired</label>
                            <input v-model="form.tanggal_expired" type="date" required class="w-full bg-gray-50 border border-gray-200 text-gray-900 text-sm rounded-xl focus:ring-indigo-500 focus:border-indigo-500 block p-3">
                        </div>
                        <div class="md:col-span-3">
                            <Button type="submit" :disabled="form.processing" class="w-full md:w-auto px-10 py-6 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-xl transition-all shadow-lg shadow-indigo-200">
                                <Loader2 v-if="form.processing" class="w-5 h-5 mr-2 animate-spin" />
                                <Play v-else class="w-5 h-5 mr-2" />
                                {{ form.processing ? 'Memulai...' : 'Mulai Menimbang' }}
                            </Button>
                        </div>
                    </form>
                </Card>

                <!-- Quick Stats -->
                <Card class="p-5 bg-white border-none rounded-2xl shadow-lg">
                    <h5 class="text-xs font-bold text-gray-500 uppercase tracking-widest mb-4">Total Produksi (Shift Ini)</h5>
                    <div class="flex items-baseline gap-2">
                        <span class="text-4xl font-black text-gray-900">{{ totalShift }}</span>
                        <span class="text-sm font-bold text-gray-400 uppercase">Items</span>
                    </div>
                    <p class="text-[10px] text-emerald-600 font-bold mt-2 flex items-center gap-1">
                        <CheckCircle2 class="w-3 h-3" />
                        Semua sistem berjalan normal
                    </p>
                </Card>
            </div>

            <!-- Live History -->
            <Card class="bg-white border-none rounded-3xl shadow-xl overflow-hidden">
                <div class="p-6 border-b border-gray-100 flex items-center justify-between">
                    <div>
                        <h5 class="text-xl font-black text-gray-900">Monitoring Penimbangan Real-Time</h5>
                        <p class="text-xs text-gray-500 mt-1">Data dari IoT akan langsung muncul di sini tanpa refresh halaman.</p>
                    </div>
                </div>
                <div class="p-6">
                    <Table>
                        <TableHeader class="bg-gray-50">
                            <TableRow>
                                <TableHead class="px-6 py-3 text-xs uppercase text-gray-700">Tanggal Penimbangan</TableHead>
                                <TableHead class="px-6 py-3 text-xs uppercase text-gray-700">Produk</TableHead>
                                <TableHead class="px-6 py-3 text-xs uppercase text-gray-700">Kode Produksi</TableHead>
                                <TableHead class="px-6 py-3 text-xs uppercase text-gray-700">Berat</TableHead>
                                <TableHead class="px-6 py-3 text-xs uppercase text-gray-700">Tanggal Expired</TableHead>
                                <TableHead class="px-6 py-3 text-xs uppercase text-gray-700">Status</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="p in penimbangans.data" :key="p.id" class="bg-white border-b hover:bg-gray-50">
                                <TableCell class="px-6 py-4 font-medium text-gray-900">{{ formatDateTime(p.created_at) }}</TableCell>
                                <TableCell class="px-6 py-4 font-medium text-gray-900">{{ p.produk.nama_produk }}</TableCell>
                                <TableCell class="px-6 py-4">
                                    <span class="font-mono text-xs">{{ p.kode_produksi }}</span>
                                </TableCell>
                                <TableCell class="px-6 py-4 font-bold text-gray-900">{{ formatWeight(p.berat) }}</TableCell>
                                <TableCell class="px-6 py-4">{{ formatDate(p.tanggal_expired) }}</TableCell>
                                <TableCell class="px-6 py-4">
                                    <Badge v-if="p.status == 'menunggu'" class="bg-yellow-100 text-yellow-800 border-none">Menunggu</Badge>
                                    <Badge v-else-if="p.status == 'selesai'" class="bg-green-100 text-green-800 border-none">Selesai</Badge>
                                    <Badge v-else class="bg-red-100 text-red-800 border-none">Invalid</Badge>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                    <div v-if="penimbangans.data.length === 0" class="p-12 text-center">
                        <p class="text-gray-400 font-medium italic">You haven't recorded any weighings yet.</p>
                    </div>
                </div>
            </Card>
        </div>
    </AuthenticatedLayout>
</template>
