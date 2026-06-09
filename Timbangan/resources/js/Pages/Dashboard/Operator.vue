<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, usePage, router } from '@inertiajs/vue3';
import { 
    Card, 
    CardContent, 
    CardHeader, 
    CardTitle 
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
    User, 
    Settings2, 
    RotateCcw, 
    LogOut, 
    Play, 
    CheckCircle2, 
    AlertCircle,
    Loader2,
    Clock
} from 'lucide-vue-next';
import Swal from 'sweetalert2';
import { formatWeight } from '@/utils/format';

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

const bannerMessage = ref('Sistem siap menerima data berat dari timbangan IoT untuk LOT ini.');

onMounted(() => {
    if (window.Echo) {
        // We listen to the global 'iot-weights' channel since this is a shared component
        window.Echo.channel('iot-weights')
            .listen('.WeightReceived', (e) => {
                // If the event operator doesn't match the current user, or it's not relevant, we can skip it,
                // but since it's a shared screen, we reload if it's the current user's data
                if (e.operator === auth.user.name) {
                    bannerMessage.value = `Telah menerima data berat ${e.weight || e.berat} kg dari Arduino (IP: ${e.ip_address})`;
                    Swal.fire({
                        toast: true,
                        position: 'top-end',
                        icon: 'success',
                        title: `Berat ${e.weight || e.berat} kg diterima dari IP ${e.ip_address}`,
                        showConfirmButton: false,
                        timer: 3000
                    });
                    router.reload({ only: ['penimbangans', 'totalShift', 'totalBerat'] });
                }
            });
    }
});

onUnmounted(() => {
    if (window.Echo) {
        window.Echo.leave('iot-weights');
    }
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

const handlePageChange = (page) => {
    router.get(route(route().current()), { page }, { preserveState: true, preserveScroll: true });
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
                <Card class="bg-slate-50/50 border-slate-200/60 dark:bg-slate-900/20 dark:border-slate-800">
                    <CardHeader class="flex flex-row items-center gap-4 space-y-0 pb-3">
                        <div class="p-2 bg-primary/10 text-primary rounded-lg">
                            <User class="w-5 h-5" />
                        </div>
                        <div>
                            <p class="text-xs font-bold uppercase tracking-widest text-muted-foreground">Operator Aktif</p>
                            <CardTitle class="text-xl font-bold">{{ auth.user.name }}</CardTitle>
                        </div>
                    </CardHeader>
                    <CardContent>
                        <div class="text-sm border rounded-lg p-3 bg-background space-y-1">
                            <div class="flex justify-between">
                                <span class="text-muted-foreground">Shift Saat Ini:</span>
                                <span class="font-bold">{{ auth.user.shift }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-muted-foreground">Waktu Shift:</span>
                                <span class="font-bold">{{ auth.user.shift_start }} - {{ auth.user.shift_end }}</span>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Manual Session Control -->
                <Card class="md:col-span-2 bg-blue-50/60 border-blue-200 shadow-sm dark:bg-blue-950/40 dark:border-blue-800">
                    <CardHeader class="flex flex-row items-center gap-3 space-y-0 pb-4">
                        <div class="p-2 bg-primary/10 text-primary rounded-lg">
                            <Settings2 class="w-5 h-5" />
                        </div>
                        <CardTitle class="text-lg font-bold">Kontrol Sesi Penimbangan</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div v-if="activePenimbangan" class="p-5 border border-emerald-200 bg-emerald-50 text-emerald-950 rounded-lg">
                            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                                <div>
                                    <Badge class="bg-emerald-600 text-white hover:bg-emerald-600">Sesi Aktif</Badge>
                                    <h4 class="text-2xl font-bold mt-2">{{ activePenimbangan.produk?.nama_produk }}</h4>
                                    <div class="flex flex-wrap gap-4 mt-2">
                                        <div class="flex items-center gap-2">
                                            <span class="text-xs text-emerald-800">KP:</span>
                                            <span class="text-xs font-bold text-emerald-950">{{ activePenimbangan.kode_produksi }}</span>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <span class="text-xs text-emerald-800">Expired:</span>
                                            <span class="text-xs font-bold text-emerald-950">{{ formatDate(activePenimbangan.tanggal_expired) }}</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex flex-col sm:flex-row gap-3 w-full md:w-auto">
                                    <Button @click="nextSession" class="w-full sm:w-auto bg-emerald-600 hover:bg-emerald-700 text-white font-bold">
                                        <RotateCcw class="w-4 h-4 mr-2" />
                                        Ganti Produk
                                    </Button>

                                    <Button @click="stopSession" variant="destructive" class="w-full sm:w-auto font-bold">
                                        <LogOut class="w-4 h-4 mr-2" />
                                        Selesai Shift
                                    </Button>
                                </div>
                            </div>
                            <div class="mt-4 p-3 bg-emerald-50/50 dark:bg-emerald-950/20 border border-emerald-100 rounded-md">
                                <p class="text-xs text-emerald-700 font-medium flex items-center gap-2">
                                    <Clock v-if="bannerMessage === 'Sistem siap menerima data berat dari timbangan IoT untuk LOT ini.'" class="w-4 h-4 animate-pulse text-emerald-500" />
                                    <CheckCircle2 v-else class="w-4 h-4 text-emerald-600" />
                                    {{ bannerMessage }}
                                </p>
                            </div>
                        </div>

                        <form v-else @submit.prevent="startSession" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-muted-foreground uppercase mb-2">Pilih Produk</label>
                                <select v-model="form.produk_id" required class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm shadow-sm transition-colors focus:outline-none focus:ring-1 focus:ring-ring">
                                    <option value="">-- Pilih Produk --</option>
                                    <option v-for="p in produks" :key="p.id" :value="p.id">{{ p.nama_produk }}</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-muted-foreground uppercase mb-2">Kode Produksi (KP)</label>
                                <input v-model="form.kode_produksi" type="text" required placeholder="Contoh: KP-20240423-001" class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm shadow-sm transition-colors focus:outline-none focus:ring-1 focus:ring-ring">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-muted-foreground uppercase mb-2">Tanggal Expired</label>
                                <input v-model="form.tanggal_expired" type="date" required class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm shadow-sm transition-colors focus:outline-none focus:ring-1 focus:ring-ring">
                            </div>
                            <div class="md:col-span-3">
                                <Button type="submit" :disabled="form.processing" class="w-full md:w-auto px-6 font-bold">
                                    <Loader2 v-if="form.processing" class="w-4 h-4 mr-2 animate-spin" />
                                    <Play v-else class="w-4 h-4 mr-2" />
                                    {{ form.processing ? 'Memulai...' : 'Mulai Menimbang' }}
                                </Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>

                <!-- Quick Stats -->
                <Card class="bg-blue-50/50 border-blue-100 dark:bg-blue-950/20 dark:border-blue-900/50">
                    <CardHeader class="pb-2">
                        <CardTitle class="text-xs font-bold text-blue-600 dark:text-blue-400 uppercase tracking-widest">Total Produksi (Shift Ini)</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="flex items-baseline gap-2">
                            <span class="text-4xl font-bold text-blue-950 dark:text-blue-50">{{ totalShift }}</span>
                            <span class="text-xs font-semibold text-blue-600 dark:text-blue-400 uppercase">Items</span>
                        </div>
                        <p class="text-[10px] text-emerald-600 dark:text-emerald-400 font-bold mt-3 flex items-center gap-1">
                            <CheckCircle2 class="w-3 h-3 text-emerald-500" />
                            Semua sistem berjalan normal
                        </p>
                    </CardContent>
                </Card>
            </div>

            <!-- Live History -->
            <Card class="overflow-hidden bg-blue-50/60 border-blue-200 shadow-sm dark:bg-blue-950/40 dark:border-blue-800">
                <CardHeader class="flex flex-row items-center justify-between border-b pb-4">
                    <div>
                        <CardTitle class="text-base">Monitoring Penimbangan Real-Time</CardTitle>
                        <p class="text-xs text-muted-foreground mt-1">Data dari IoT akan langsung muncul di sini tanpa refresh halaman.</p>
                    </div>
                </CardHeader>
                <div class="overflow-x-auto">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead class="px-6 py-3 text-xs uppercase text-muted-foreground">Tanggal Penimbangan</TableHead>
                                <TableHead class="px-6 py-3 text-xs uppercase text-muted-foreground">Produk</TableHead>
                                <TableHead class="px-6 py-3 text-xs uppercase text-muted-foreground">Kode Produksi</TableHead>
                                <TableHead class="px-6 py-3 text-xs uppercase text-muted-foreground">Berat</TableHead>
                                <TableHead class="px-6 py-3 text-xs uppercase text-muted-foreground">Tanggal Expired</TableHead>
                                <TableHead class="px-6 py-3 text-xs uppercase text-muted-foreground">Status</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="p in penimbangans.data" :key="p.id">
                                <TableCell class="px-6 py-4 font-medium text-foreground">{{ formatDateTime(p.created_at) }}</TableCell>
                                <TableCell class="px-6 py-4 font-medium text-foreground">{{ p.produk.nama_produk }}</TableCell>
                                <TableCell class="px-6 py-4">
                                    <code class="bg-muted px-2 py-0.5 rounded font-mono text-xs border border-border">
                                        {{ p.kode_produksi }}
                                    </code>
                                </TableCell>
                                <TableCell class="px-6 py-4 font-bold text-foreground">{{ formatWeight(p.berat) }} <span class="text-xs font-normal text-muted-foreground">kg</span></TableCell>
                                <TableCell class="px-6 py-4 text-muted-foreground">{{ formatDate(p.tanggal_expired) }}</TableCell>
                                <TableCell class="px-6 py-4">
                                    <Badge v-if="p.status == 'menunggu'" variant="outline" class="bg-amber-100 text-amber-800 border-amber-200">Menunggu</Badge>
                                    <Badge v-else-if="p.status == 'selesai'" class="bg-emerald-100 text-emerald-800 border-emerald-200">Selesai</Badge>
                                    <Badge v-else variant="destructive">Invalid</Badge>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </div>
                <div v-if="penimbangans.data.length === 0" class="p-12 text-center text-muted-foreground">
                    Belum ada data penimbangan.
                </div>

                <!-- Pagination -->
                <div v-if="penimbangans.total > penimbangans.per_page" class="px-6 py-4 border-t border-border flex justify-center">
                    <Pagination 
                        v-slot="{ page }"
                        :total="penimbangans.total" 
                        :items-per-page="penimbangans.per_page"
                        :sibling-count="1" 
                        show-edges 
                        :page="penimbangans.current_page"
                        @update:page="handlePageChange"
                    >
                        <PaginationContent v-slot="{ items }">
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
