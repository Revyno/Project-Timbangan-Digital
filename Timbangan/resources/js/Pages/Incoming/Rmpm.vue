<script setup>
import { ref, watch, onMounted, onUnmounted } from 'vue';
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
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { 
    User, 
    Settings2, 
    RotateCcw, 
    LogOut, 
    Play, 
    CheckCircle2, 
    Clock, 
    Package,
    QrCode,
    Loader2
} from 'lucide-vue-next';
import axios from 'axios';
import Swal from 'sweetalert2';
import QrScanner from '@/Components/QrScanner.vue';
import { formatWeight } from '@/utils/format';

const props = defineProps({
    activeSession: Object,
    totalShift: Number,
    totalBerat: Number,
    history: Object,
    namaBarangOptions: { type: Array, default: () => [] },
    asalOptions: { type: Array, default: () => [] },
});

const { auth } = usePage().props;

// Determine initial dropdown vs custom state
const initNamaBarang = props.activeSession?.nama_barang || '';
const initAsal = props.activeSession?.asal || '';

const selectedNamaBarang = ref(
    props.namaBarangOptions.includes(initNamaBarang) ? initNamaBarang : (initNamaBarang ? '__lainnya__' : '')
);
const customNamaBarang = ref(
    props.namaBarangOptions.includes(initNamaBarang) ? '' : initNamaBarang
);

const selectedAsal = ref(
    props.asalOptions.filter(a => a !== 'Lainnya').includes(initAsal) ? initAsal : (initAsal ? '__lainnya__' : '')
);
const customAsal = ref(
    props.asalOptions.filter(a => a !== 'Lainnya').includes(initAsal) ? '' : initAsal
);

const selectedJenisBarang = ref(props.activeSession?.jenis_barang || 'raw_material');
const customJenisBarang = ref('');

const form = useForm({
    tanggal_kedatangan: props.activeSession?.tanggal_kedatangan || new Date().toISOString().split('T')[0],
    nama_barang: initNamaBarang,
    jenis_barang: props.activeSession?.jenis_barang || 'raw_material',
    asal: initAsal,
    nama_supplier: props.activeSession?.nama_supplier || '',
    no_surat: props.activeSession?.no_surat || '',
    nama_sopir: props.activeSession?.nama_sopir || '',
    nomor_plat: props.activeSession?.nomor_plat || '',
    total_qty: props.activeSession?.total_qty || 1,
    kode_batch: props.activeSession?.kode_batch || '',
    expired_date: props.activeSession?.expired_date || '',
});

// Watchers to sync dropdown + custom input to form fields
watch(selectedNamaBarang, (val) => {
    if (val === '__lainnya__') {
        form.nama_barang = customNamaBarang.value;
    } else {
        form.nama_barang = val;
        customNamaBarang.value = '';
    }
});
watch(customNamaBarang, (val) => {
    if (selectedNamaBarang.value === '__lainnya__') form.nama_barang = val;
});

watch(selectedAsal, (val) => {
    if (val === '__lainnya__') {
        form.asal = customAsal.value;
    } else {
        form.asal = val;
        customAsal.value = '';
    }
});
watch(customAsal, (val) => {
    if (selectedAsal.value === '__lainnya__') form.asal = val;
});

watch(selectedJenisBarang, (val) => {
    if (val === '__lainnya__') {
        form.jenis_barang = customJenisBarang.value;
    } else {
        form.jenis_barang = val;
        customJenisBarang.value = '';
    }
});
watch(customJenisBarang, (val) => {
    if (selectedJenisBarang.value === '__lainnya__') form.jenis_barang = val;
});

const scanning = ref(false);
const showScanner = ref(false);

const handleScanResult = async (qrCode) => {
    showScanner.value = false;
    try {
        scanning.value = true;
        const response = await axios.post('/api/driver/identify', { qr_code: qrCode });
        
        if (response.data.success) {
            const driver = response.data.driver;
            form.nama_sopir = driver.name;
            form.nama_supplier = driver.supplier;
            form.nomor_plat = driver.nomor_plat;
            
            Swal.fire({
                icon: 'success',
                title: 'Driver Teridentifikasi',
                text: `${driver.name} (Supplier: ${driver.supplier})`,
                timer: 2000,
                showConfirmButton: false,
            });
        }
    } catch (error) {
        Swal.fire({
            icon: 'error',
            title: 'Gagal',
            text: error.response?.data?.message || 'Kode QR tidak valid atau driver tidak ditemukan.',
        });
    } finally {
        scanning.value = false;
    }
};

const identifyDriver = () => {
    showScanner.value = true;
};

const bannerMessage = ref('Menunggu data berat dari timbangan...');

onMounted(() => {
    if (window.Echo) {
        window.Echo.channel('iot-weights.incoming_rmpm')
            .listen('.WeightReceived', (e) => {
                console.log('Weight received:', e);
                bannerMessage.value = `Telah menerima data berat ${e.weight || e.berat} kg dari Arduino (IP: ${e.ip_address})`;
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'success',
                    title: `Berat ${e.weight || e.berat} kg diterima dari IP ${e.ip_address}`,
                    showConfirmButton: false,
                    timer: 3000
                });
                router.reload({ only: ['history', 'totalShift', 'totalBerat'] });
            });
    }
});

onUnmounted(() => {
    if (window.Echo) {
        window.Echo.leave('iot-weights.incoming_rmpm');
    }
});

const startSession = () => {
    form.post(route('incoming.rmpm.store'));
};

const nextSession = () => {
    router.post(route('incoming.rmpm.next'));
};

const stopSession = () => {
    if (confirm('Apakah Anda yakin ingin mengakhiri shift? Akun akan dikunci sampai besok.')) {
        router.post(route('incoming.rmpm.stop'));
    }
};

const handlePageChange = (page) => {
    router.get(route(route().current()), { page }, { preserveState: true, preserveScroll: true });
};

const formatDateTime = (date) => {
    return new Date(date).toLocaleTimeString('id-ID', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit', second: '2-digit' });
};
</script>

<template>
    <Head title="Incoming RMPM" />

    <AuthenticatedLayout>
        <div class="space-y-6">
            <!-- Header -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <Card class="bg-rose-50/60 border-rose-200 shadow-sm dark:bg-rose-950/40 dark:border-rose-800">
                    <CardHeader class="flex flex-row items-center gap-4 space-y-0 pb-3">
                        <div class="p-2 bg-primary/10 text-primary rounded-lg">
                            <Package class="w-5 h-5" />
                        </div>
                        <div>
                            <p class="text-xs font-bold uppercase tracking-widest text-muted-foreground">Operator RMPM</p>
                            <CardTitle class="text-xl font-bold">{{ auth.user.name }}</CardTitle>
                        </div>
                    </CardHeader>
                    <CardContent>
                        <div class="text-sm border rounded-lg p-3 bg-background space-y-1">
                            <div class="flex justify-between">
                                <span class="text-muted-foreground">Shift:</span>
                                <span class="font-bold">{{ auth.user.shift }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-muted-foreground">Total Sesi:</span>
                                <span class="font-bold">{{ totalShift }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-muted-foreground">Total Berat:</span>
                                <span class="font-bold text-primary">{{ new Intl.NumberFormat('id-ID').format(totalBerat) }} kg</span>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card class="md:col-span-2 bg-rose-50/60 border-rose-200 shadow-sm dark:bg-rose-950/40 dark:border-rose-800">
                    <div v-if="activeSession" class="p-6 space-y-4">
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-b pb-4">
                            <div>
                                <Badge class="bg-primary text-primary-foreground hover:bg-primary/95 mb-2">SESI AKTIF</Badge>
                                <h3 class="text-xl sm:text-2xl font-bold text-foreground">{{ activeSession.nama_barang }}</h3>
                                <p class="text-sm text-muted-foreground">{{ activeSession.nama_supplier }} | No Surat: {{ activeSession.no_surat }}</p>
                            </div>
                            <div class="flex flex-col sm:flex-row gap-2 w-full sm:w-auto">
                                <Button @click="nextSession" variant="outline" class="w-full sm:w-auto justify-center">
                                    <RotateCcw class="w-4 h-4 mr-2" /> Ganti Sesi
                                </Button>
                                <Button @click="stopSession" variant="destructive" class="w-full sm:w-auto justify-center">
                                    <LogOut class="w-4 h-4 mr-2" /> Stop Shift
                                </Button>
                            </div>
                        </div>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                            <div>
                                <p class="text-muted-foreground font-semibold uppercase text-[10px]">Jenis</p>
                                <p class="font-bold text-foreground">{{ activeSession.jenis_barang }}</p>
                            </div>
                            <div>
                                <p class="text-muted-foreground font-semibold uppercase text-[10px]">Batch</p>
                                <p class="font-bold text-foreground">{{ activeSession.kode_batch || '-' }}</p>
                            </div>
                            <div>
                                <p class="text-muted-foreground font-semibold uppercase text-[10px]">Qty</p>
                                <p class="font-bold text-foreground">{{ activeSession.total_qty }}</p>
                            </div>
                            <div>
                                <p class="text-muted-foreground font-semibold uppercase text-[10px]">Exp Date</p>
                                <p class="font-bold text-foreground">{{ activeSession.expired_date || '-' }}</p>
                            </div>
                        </div>
                        <div class="mt-4 p-3 bg-primary/10 border border-primary/20 rounded-md">
                             <p class="text-sm text-primary font-medium flex items-center gap-2">
                                <Clock v-if="bannerMessage === 'Menunggu data berat dari timbangan...'" class="w-4 h-4 animate-pulse" />
                                <CheckCircle2 v-else class="w-4 h-4 text-primary" />
                                {{ bannerMessage }}
                            </p>
                        </div>
                    </div>

                    <div v-else class="p-6">
                        <div class="flex items-center justify-between mb-6">
                            <CardTitle class="text-lg font-bold text-foreground">Mulai Sesi Baru</CardTitle>
                            <Button @click="identifyDriver" type="button">
                                <QrCode class="w-4 h-4 mr-2" /> Scan QR Driver
                            </Button>
                        </div>

                        <form @submit.prevent="startSession" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            <div class="space-y-1">
                                <Label>Tanggal Kedatangan</Label>
                                <Input v-model="form.tanggal_kedatangan" type="date" required />
                            </div>

                            <!-- Nama Barang — DROPDOWN with Lainnya -->
                            <div class="space-y-1">
                                <Label>Nama Barang</Label>
                                <select
                                    v-model="selectedNamaBarang"
                                    required
                                    class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm shadow-sm transition-colors focus:outline-none focus:ring-1 focus:ring-ring"
                                >
                                    <option value="" disabled>-- Pilih Nama Barang --</option>
                                    <option v-for="item in namaBarangOptions" :key="item" :value="item">{{ item }}</option>
                                    <option value="__lainnya__">Lainnya</option>
                                </select>
                                <Input
                                    v-if="selectedNamaBarang === '__lainnya__'"
                                    v-model="customNamaBarang"
                                    required
                                    placeholder="Ketik nama barang..."
                                    class="mt-1"
                                />
                            </div>

                            <!-- Jenis Barang — DROPDOWN with Lainnya -->
                            <div class="space-y-1">
                                <Label>Jenis Barang</Label>
                                <select
                                    v-model="selectedJenisBarang"
                                    required
                                    class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm shadow-sm transition-colors focus:outline-none focus:ring-1 focus:ring-ring"
                                >
                                    <option value="" disabled>-- Pilih Jenis --</option>
                                    <option value="raw_material">Raw Material</option>
                                    <option value="packaging_material">Packaging Material</option>
                                    <option value="__lainnya__">Lainnya</option>
                                </select>
                                <Input
                                    v-if="selectedJenisBarang === '__lainnya__'"
                                    v-model="customJenisBarang"
                                    required
                                    placeholder="Ketik jenis barang..."
                                    class="mt-1"
                                />
                            </div>

                            <div class="space-y-1">
                                <Label>Supplier</Label>
                                <Input v-model="form.nama_supplier" required placeholder="Nama Supplier" />
                            </div>

                            <!-- Asal — DROPDOWN with Lainnya -->
                            <div class="space-y-1">
                                <Label>Asal</Label>
                                <select
                                    v-model="selectedAsal"
                                    required
                                    class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm shadow-sm transition-colors focus:outline-none focus:ring-1 focus:ring-ring"
                                >
                                    <option value="" disabled>-- Pilih Asal --</option>
                                    <option v-for="a in asalOptions.filter(o => o !== 'Lainnya')" :key="a" :value="a">{{ a }}</option>
                                    <option value="__lainnya__">Lainnya</option>
                                </select>
                                <Input
                                    v-if="selectedAsal === '__lainnya__'"
                                    v-model="customAsal"
                                    required
                                    placeholder="Ketik asal barang..."
                                    class="mt-1"
                                />
                            </div>

                            <div class="space-y-1">
                                <Label>No. Surat Jalan</Label>
                                <Input v-model="form.no_surat" required placeholder="No. Surat" />
                            </div>
                            <div class="space-y-1">
                                <Label>Sopir</Label>
                                <Input v-model="form.nama_sopir" required placeholder="Nama Sopir" />
                            </div>
                            <div class="space-y-1">
                                <Label>No. Plat</Label>
                                <Input v-model="form.nomor_plat" required placeholder="Nopol" />
                            </div>
                            <div class="space-y-1">
                                <Label>Total Qty</Label>
                                <Input v-model="form.total_qty" type="number" required min="1" />
                            </div>
                            <div class="space-y-1">
                                <Label>Kode Batch (Opt)</Label>
                                <Input v-model="form.kode_batch" placeholder="Batch Number" />
                            </div>
                            <div class="space-y-1">
                                <Label>Exp Date (Opt)</Label>
                                <Input v-model="form.expired_date" type="date" />
                            </div>
                            <div class="lg:col-span-3 pt-2">
                                <Button type="submit" :disabled="form.processing" class="w-full font-bold">
                                    <Loader2 v-if="form.processing" class="w-4 h-4 mr-2 animate-spin" />
                                    <Play v-else class="w-4 h-4 mr-2" /> 
                                    {{ form.processing ? 'Memulai Sesi ...' : 'Mulai Penimbangan' }}
                                </Button>
                            </div>
                        </form>
                    </div>
                </Card>
            </div>

            <!-- History -->
            <Card class="overflow-hidden bg-rose-50/60 border-rose-200 shadow-sm dark:bg-rose-950/40 dark:border-rose-800">
                <div class="p-6 border-b border-border">
                    <CardTitle class="text-xl font-bold text-foreground">Riwayat Penimbangan RMPM</CardTitle>
                </div>
                <div class="overflow-x-auto">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead class="px-6 py-4 text-xs font-bold text-muted-foreground uppercase whitespace-nowrap">Waktu</TableHead>
                                <TableHead class="px-6 py-4 text-xs font-bold text-muted-foreground uppercase whitespace-nowrap">Nama Barang</TableHead>
                                <TableHead class="px-6 py-4 text-xs font-bold text-muted-foreground uppercase whitespace-nowrap">Supplier</TableHead>
                                <TableHead class="px-6 py-4 text-xs font-bold text-muted-foreground uppercase whitespace-nowrap">Berat</TableHead>
                                <TableHead class="px-6 py-4 text-xs font-bold text-muted-foreground uppercase whitespace-nowrap">Status</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="h in history.data" :key="h.id">
                                <TableCell class="px-6 py-4 text-muted-foreground whitespace-nowrap">{{ formatDateTime(h.created_at) }}</TableCell>
                                <TableCell class="px-6 py-4 font-bold text-sm text-foreground">{{ h.nama_barang }}</TableCell>
                                <TableCell class="px-6 py-4 text-sm text-muted-foreground">{{ h.nama_supplier }}</TableCell>
                                <TableCell class="px-6 py-4 font-bold whitespace-nowrap">{{ formatWeight(h.berat) }} <span class="text-xs font-normal text-muted-foreground">kg</span></TableCell>
                                <TableCell class="px-6 py-4">
                                    <Badge v-if="h.status === 'selesai'" class="bg-emerald-100 text-emerald-800 border-emerald-200">Selesai</Badge>
                                    <Badge v-else variant="outline" class="bg-amber-100 text-amber-800 border-amber-200">{{ h.status }}</Badge>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </div>

                <!-- Pagination -->
                <div v-if="history.total > history.per_page" class="px-6 py-4 border-t border-border flex justify-center">
                    <Pagination 
                        v-slot="{ page }"
                        :total="history.total" 
                        :items-per-page="history.per_page"
                        :sibling-count="1" 
                        show-edges 
                        :page="history.current_page"
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

        <!-- Scanner Modal -->
        <QrScanner 
            :isOpen="showScanner" 
            @scan="handleScanResult" 
            @close="showScanner = false" 
        />
    </AuthenticatedLayout>
</template>
