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

const formatWeight = (weight) => {
    if (!weight || weight <= 0) return 'Belum ditimbang';
    return new Intl.NumberFormat('id-ID', { minimumFractionDigits: 3, maximumFractionDigits: 3 }).format(weight) + ' kg';
};

const formatDateTime = (date) => {
    return new Date(date).toLocaleTimeString('id-ID', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit', second: '2-digit' });
};

const handlePageChange = (page) => {
    router.get(route(route().current()), { page }, { preserveState: true, preserveScroll: true });
};
</script>

<template>
    <Head title="Incoming RMPM" />

    <AuthenticatedLayout>
        <div class="space-y-6">
            <!-- Header -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <Card class="p-5 bg-gradient-to-br from-indigo-600 to-purple-700 rounded-2xl shadow-lg text-white border-none">
                    <div class="flex items-center gap-4 mb-4">
                        <div class="p-3 bg-white/20 rounded-xl">
                            <Package class="w-6 h-6 text-white" />
                        </div>
                        <div>
                            <h5 class="text-xs font-bold uppercase tracking-widest opacity-70">Operator RMPM</h5>
                            <p class="text-xl font-black">{{ auth.user.name }}</p>
                        </div>
                    </div>
                    <div class="text-xs bg-black/20 p-3 rounded-xl border border-white/10">
                        <p>Shift: <b>{{ auth.user.shift }}</b></p>
                        <p>Total Items: <b>{{ totalShift }}</b></p>
                        <p>Total Berat: <b>{{ new Intl.NumberFormat('id-ID').format(totalBerat) }} kg</b></p>
                    </div>
                </Card>

                <Card class="md:col-span-2 p-6 bg-white border-none rounded-3xl shadow-xl">
                    <div v-if="activeSession" class="space-y-4">
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-b border-gray-100 pb-4">
                            <div>
                                <Badge class="bg-indigo-100 text-indigo-700 mb-2">SESI AKTIF</Badge>
                                <h3 class="text-xl sm:text-2xl font-black text-gray-900">{{ activeSession.nama_barang }}</h3>
                                <p class="text-sm text-gray-500">{{ activeSession.nama_supplier }} | No Surat: {{ activeSession.no_surat }}</p>
                            </div>
                            <div class="flex flex-col sm:flex-row gap-2 w-full sm:w-auto">
                                <Button @click="nextSession" variant="outline" class="border-indigo-200 text-indigo-700 hover:bg-indigo-50 py-5 px-5 w-full sm:w-auto justify-center">
                                    <RotateCcw class="w-4 h-4 mr-2" /> Ganti Sesi
                                </Button>
                                <Button @click="stopSession" variant="destructive" class="bg-red-600 py-5 px-5 w-full sm:w-auto justify-center">
                                    <LogOut class="w-4 h-4 mr-2" /> Stop Shift
                                </Button>
                            </div>
                        </div>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                            <div>
                                <p class="text-gray-400 font-bold uppercase text-[10px]">Jenis</p>
                                <p class="font-bold">{{ activeSession.jenis_barang }}</p>
                            </div>
                            <div>
                                <p class="text-gray-400 font-bold uppercase text-[10px]">Batch</p>
                                <p class="font-bold">{{ activeSession.kode_batch || '-' }}</p>
                            </div>
                            <div>
                                <p class="text-gray-400 font-bold uppercase text-[10px]">Qty</p>
                                <p class="font-bold">{{ activeSession.total_qty }}</p>
                            </div>
                            <div>
                                <p class="text-gray-400 font-bold uppercase text-[10px]">Exp Date</p>
                                <p class="font-bold">{{ activeSession.expired_date || '-' }}</p>
                            </div>
                        </div>
                        <div class="mt-4 p-3 bg-indigo-50 rounded-xl border border-indigo-100">
                             <p class="text-sm text-indigo-700 font-medium flex items-center gap-2">
                                <Clock v-if="bannerMessage === 'Menunggu data berat dari timbangan...'" class="w-4 h-4 animate-pulse" />
                                <CheckCircle2 v-else class="w-4 h-4 text-indigo-600" />
                                {{ bannerMessage }}
                            </p>
                        </div>
                    </div>

                    <div v-else>
                        <div class="flex items-center justify-between mb-6">
                            <h3 class="text-lg font-bold text-gray-900">Mulai Sesi Baru</h3>
                            <Button @click="identifyDriver" type="button" class="bg-indigo-600 hover:bg-indigo-700 font-bold">
                                <QrCode class="w-4 h-4 mr-2" /> Scan QR Driver
                            </Button>
                        </div>

                        <form @submit.prevent="startSession" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <div class="space-y-1">
                            <Label>Tanggal Kedatangan</Label>
                            <Input v-model="form.tanggal_kedatangan" type="date" required class="text-base sm:text-sm" />
                        </div>

                        <!-- Nama Barang — DROPDOWN with Lainnya -->
                        <div class="space-y-1">
                            <Label>Nama Barang</Label>
                            <select
                                v-model="selectedNamaBarang"
                                required
                                class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-base sm:text-sm shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
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
                                class="mt-1 text-base sm:text-sm"
                            />
                        </div>

                        <!-- Jenis Barang — DROPDOWN with Lainnya -->
                        <div class="space-y-1">
                            <Label>Jenis Barang</Label>
                            <select
                                v-model="selectedJenisBarang"
                                required
                                class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-base sm:text-sm shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
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
                                class="mt-1 text-base sm:text-sm"
                            />
                        </div>

                        <div class="space-y-1">
                            <Label>Supplier</Label>
                            <Input v-model="form.nama_supplier" required placeholder="Nama Supplier" class="text-base sm:text-sm" />
                        </div>

                        <!-- Asal — DROPDOWN with Lainnya -->
                        <div class="space-y-1">
                            <Label>Asal</Label>
                            <select
                                v-model="selectedAsal"
                                required
                                class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-base sm:text-sm shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
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
                                class="mt-1 text-base sm:text-sm"
                            />
                        </div>

                        <div class="space-y-1">
                            <Label>No. Surat Jalan</Label>
                            <Input v-model="form.no_surat" required placeholder="No. Surat" class="text-base sm:text-sm" />
                        </div>
                        <div class="space-y-1">
                            <Label>Sopir</Label>
                            <Input v-model="form.nama_sopir" required placeholder="Nama Sopir" class="text-base sm:text-sm" />
                        </div>
                        <div class="space-y-1">
                            <Label>No. Plat</Label>
                            <Input v-model="form.nomor_plat" required placeholder="Nopol" class="text-base sm:text-sm" />
                        </div>
                        <div class="space-y-1">
                            <Label>Total Qty</Label>
                            <Input v-model="form.total_qty" type="number" required min="1" class="text-base sm:text-sm" />
                        </div>
                        <div class="space-y-1">
                            <Label>Kode Batch (Opt)</Label>
                            <Input v-model="form.kode_batch" placeholder="Batch Number" class="text-base sm:text-sm" />
                        </div>
                        <div class="space-y-1">
                            <Label>Exp Date (Opt)</Label>
                            <Input v-model="form.expired_date" type="date" class="text-base sm:text-sm" />
                        </div>
                        <div class="lg:col-span-3 pt-2">
                            <Button type="submit" :disabled="form.processing" class="w-full bg-indigo-600 hover:bg-indigo-700 py-6 font-bold text-lg">
                                <Loader2 v-if="form.processing" class="w-5 h-5 mr-2 animate-spin" />
                                <Play v-else class="w-5 h-5 mr-2" /> 
                                {{ form.processing ? 'Memulai Sesi ...' : 'Mulai Penimbangan' }}
                            </Button>
                        </div>
                    </form>
                    </div>
                </Card>
            </div>

            <!-- History -->
            <Card class="bg-white border-none rounded-3xl shadow-xl overflow-hidden">
                <div class="p-6 border-b border-gray-100">
                    <h5 class="text-xl font-black text-gray-900">Riwayat Penimbangan RMPM</h5>
                </div>
                <div class="p-0">
                    <Table>
                        <TableHeader class="bg-gray-50">
                            <TableRow>
                                <TableHead class="px-6 py-4">Waktu</TableHead>
                                <TableHead class="px-6 py-4">Nama Barang</TableHead>
                                <TableHead class="px-6 py-4">Supplier</TableHead>
                                <TableHead class="px-6 py-4">Berat</TableHead>
                                <TableHead class="px-6 py-4">Status</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="h in history.data" :key="h.id">
                                <TableCell class="px-6 py-4">{{ formatDateTime(h.created_at) }}</TableCell>
                                <TableCell class="px-6 py-4 font-bold">{{ h.nama_barang }}</TableCell>
                                <TableCell class="px-6 py-4">{{ h.nama_supplier }}</TableCell>
                                <TableCell class="px-6 py-4 font-black">{{ formatWeight(h.berat) }}</TableCell>
                                <TableCell class="px-6 py-4">
                                    <Badge :variant="h.status === 'selesai' ? 'default' : 'secondary'">{{ h.status }}</Badge>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>

                    <!-- Pagination -->
                    <div v-if="history.total > history.per_page" class="mt-6 pb-6 flex justify-center">
                        <Pagination 
                            :total="history.total" 
                            :sibling-count="1" 
                            show-edges 
                            :default-page="history.current_page"
                            @update:page="handlePageChange"
                        >
                            <PaginationContent>
                                <PaginationFirst />
                                <PaginationPrevious />

                                <template v-for="(item, index) in history.links.slice(1, -1)" :key="index">
                                    <PaginationItem>
                                        <Button
                                            v-if="item.url"
                                            class="w-10 h-10 p-0"
                                            :variant="item.active ? 'default' : 'outline'"
                                            @click="handlePageChange(item.label)"
                                        >
                                            {{ item.label }}
                                        </Button>
                                        <PaginationEllipsis v-else />
                                    </PaginationItem>
                                </template>

                                <PaginationNext />
                                <PaginationLast />
                            </PaginationContent>
                        </Pagination>
                    </div>
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
