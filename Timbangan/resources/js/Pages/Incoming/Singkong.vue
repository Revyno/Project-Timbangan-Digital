<script setup>
import { ref } from 'vue';
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
    Truck,
    QrCode,
    Loader2
} from 'lucide-vue-next';
import axios from 'axios';
import Swal from 'sweetalert2';
import { onMounted, onUnmounted } from 'vue';
import QrScanner from '@/Components/QrScanner.vue';
import { formatWeight } from '@/utils/format';

const props = defineProps({
    activeSession:   Object,
    totalShift:      Number,
    totalBerat:      Number,
    history:         Object,
    jenisOptions:    Array,
    asalOptions:     Array,
    supplierOptions: Array,
});

const { auth } = usePage().props;

const form = useForm({
    no_surat:       props.activeSession?.no_surat       || '',
    nama_supplier:  props.activeSession?.nama_supplier  || '',
    asal:           props.activeSession?.asal           || '',
    nama_sopir:     props.activeSession?.nama_sopir     || '',
    nomor_plat:     props.activeSession?.nomor_plat     || '',
    jenis_singkong: props.activeSession?.jenis_singkong || '',
    kode_produksi:  props.activeSession?.kode_produksi  || '',
});

const bannerMessage = ref('Menunggu data berat dari timbangan...');

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

onMounted(() => {
    if (window.Echo) {
        window.Echo.channel('iot-weights.incoming_singkong')
            .listen('.WeightReceived', (e) => {
                console.log('Weight received:', e);
                bannerMessage.value = `Telah menerima data berat ${e.weight || e.berat} kg dari Arduino dengan IP ${e.ip_address}`;
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'success',
                    title: `Berat ${e.weight || e.berat} kg diterima dari IP ${e.ip_address}`,
                    showConfirmButton: false,
                    timer: 3000
                });
                // Auto reload only weight related data
                router.reload({ only: ['history', 'totalShift', 'totalBerat'] });
            });
    }
});

onUnmounted(() => {
    if (window.Echo) {
        window.Echo.leave('iot-weights.incoming_singkong');
    }
});

const startSession = () => {
    form.post(route('incoming.singkong.store'));
};

const nextSession = () => {
    router.post(route('incoming.singkong.next'));
};

const stopSession = () => {
    if (confirm('Apakah Anda yakin ingin mengakhiri shift? Akun akan dikunci sampai besok.')) {
        router.post(route('incoming.singkong.stop'));
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
    <Head title="Incoming Singkong" />

    <AuthenticatedLayout>
        <div class="space-y-6">
            <!-- Header -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <Card class="bg-amber-50/60 border-amber-200 shadow-sm dark:bg-amber-950/40 dark:border-amber-800">
                    <CardHeader class="flex flex-row items-center gap-4 space-y-0 pb-3">
                        <div class="p-2 bg-primary/10 text-primary rounded-lg">
                            <Truck class="w-5 h-5" />
                        </div>
                        <div>
                            <p class="text-xs font-bold uppercase tracking-widest text-muted-foreground">Operator Incoming</p>
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

                <Card class="md:col-span-2 bg-amber-50/60 border-amber-200 shadow-sm dark:bg-amber-950/40 dark:border-amber-800">
                    <!-- Active Session Info -->
                    <div v-if="activeSession" class="p-6 space-y-4">
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-b pb-4">
                            <div>
                                <Badge class="bg-emerald-600 hover:bg-emerald-600 text-white mb-2">SESI AKTIF</Badge>
                                <h3 class="text-xl sm:text-2xl font-bold text-foreground">{{ activeSession.nama_supplier }}</h3>
                                <p class="text-sm text-muted-foreground">KP: {{ activeSession.kode_produksi }} | No Surat: {{ activeSession.no_surat }}</p>
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
                                <p class="text-muted-foreground font-semibold uppercase text-[10px]">Sopir</p>
                                <p class="font-bold text-foreground">{{ activeSession.nama_sopir }}</p>
                            </div>
                            <div>
                                <p class="text-muted-foreground font-semibold uppercase text-[10px]">Plat Nomor</p>
                                <p class="font-bold text-foreground">{{ activeSession.nomor_plat }}</p>
                            </div>
                            <div>
                                <p class="text-muted-foreground font-semibold uppercase text-[10px]">Asal</p>
                                <p class="font-bold text-foreground">{{ activeSession.asal }}</p>
                            </div>
                            <div>
                                <p class="text-muted-foreground font-semibold uppercase text-[10px]">Jenis</p>
                                <p class="font-bold text-foreground">{{ activeSession.jenis_singkong }}</p>
                            </div>
                        </div>
                        <div class="mt-4 p-3 bg-emerald-50 border border-emerald-100 rounded-md">
                             <p class="text-sm text-emerald-700 font-medium flex items-center gap-2">
                                <Clock v-if="bannerMessage === 'Menunggu data berat dari timbangan...'" class="w-4 h-4 animate-pulse text-emerald-500" />
                                <CheckCircle2 v-else class="w-4 h-4 text-emerald-600" />
                                {{ bannerMessage }}
                            </p>
                        </div>
                    </div>

                    <!-- Start Session Form -->
                    <div v-else class="p-6">
                        <div class="flex items-center justify-between mb-6">
                            <CardTitle class="text-lg font-bold text-foreground">Mulai Sesi Baru</CardTitle>
                            <Button @click="identifyDriver" type="button">
                                <QrCode class="w-4 h-4 mr-2" /> Scan QR Driver
                            </Button>
                        </div>

                        <form @submit.prevent="startSession" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            <!-- No. Surat — plain text -->
                            <div class="space-y-1">
                                <Label for="no_surat">No. Surat</Label>
                                <Input
                                    id="no_surat"
                                    v-model="form.no_surat"
                                    required
                                    placeholder="No. Surat Jalan"
                                />
                                <p v-if="form.errors.no_surat" class="text-destructive text-xs">{{ form.errors.no_surat }}</p>
                            </div>

                            <!-- Supplier — DROPDOWN -->
                            <div class="space-y-1">
                                <Label for="nama_supplier">Supplier</Label>
                                <select
                                    id="nama_supplier"
                                    v-model="form.nama_supplier"
                                    required
                                    class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm shadow-sm transition-colors focus:outline-none focus:ring-1 focus:ring-ring"
                                >
                                    <option value="" disabled>-- Pilih Supplier --</option>
                                    <option v-for="s in supplierOptions" :key="s" :value="s">{{ s }}</option>
                                </select>
                                <p v-if="form.errors.nama_supplier" class="text-destructive text-xs">{{ form.errors.nama_supplier }}</p>
                            </div>

                            <!-- Asal — DROPDOWN -->
                            <div class="space-y-1">
                                <Label for="asal">Asal</Label>
                                <select
                                    id="asal"
                                    v-model="form.asal"
                                    required
                                    class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm shadow-sm transition-colors focus:outline-none focus:ring-1 focus:ring-ring"
                                >
                                    <option value="" disabled>-- Pilih Asal --</option>
                                    <option v-for="a in asalOptions" :key="a" :value="a">{{ a }}</option>
                                </select>
                                <p v-if="form.errors.asal" class="text-destructive text-xs">{{ form.errors.asal }}</p>
                            </div>

                            <!-- Nama Sopir — plain text -->
                            <div class="space-y-1">
                                <Label for="nama_sopir">Nama Sopir</Label>
                                <Input
                                    id="nama_sopir"
                                    v-model="form.nama_sopir"
                                    required
                                    placeholder="Nama Sopir"
                                />
                                <p v-if="form.errors.nama_sopir" class="text-destructive text-xs">{{ form.errors.nama_sopir }}</p>
                            </div>

                            <!-- No. Plat — plain text -->
                            <div class="space-y-1">
                                <Label for="nomor_plat">No. Plat</Label>
                                <Input
                                    id="nomor_plat"
                                    v-model="form.nomor_plat"
                                    required
                                    placeholder="Nopol Kendaraan"
                                />
                                <p v-if="form.errors.nomor_plat" class="text-destructive text-xs">{{ form.errors.nomor_plat }}</p>
                            </div>

                            <!-- Jenis Singkong — DROPDOWN -->
                            <div class="space-y-1">
                                <Label for="jenis_singkong">Jenis Singkong</Label>
                                <select
                                    id="jenis_singkong"
                                    v-model="form.jenis_singkong"
                                    required
                                    class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm shadow-sm transition-colors focus:outline-none focus:ring-1 focus:ring-ring"
                                >
                                    <option value="" disabled>-- Pilih Jenis --</option>
                                    <option v-for="j in jenisOptions" :key="j" :value="j">{{ j }}</option>
                                </select>
                                <p v-if="form.errors.jenis_singkong" class="text-destructive text-xs">{{ form.errors.jenis_singkong }}</p>
                            </div>

                            <!-- Kode Produksi — plain text -->
                            <div class="space-y-1">
                                <Label for="kode_produksi">Kode Produksi</Label>
                                <Input
                                    id="kode_produksi"
                                    v-model="form.kode_produksi"
                                    required
                                    placeholder="LOT / KP"
                                />
                                <p v-if="form.errors.kode_produksi" class="text-destructive text-xs">{{ form.errors.kode_produksi }}</p>
                            </div>

                            <div class="lg:col-span-3 pt-2">
                                <Button
                                    type="submit"
                                    :disabled="form.processing"
                                    class="w-full font-bold text-base"
                                >
                                    <Loader2 v-if="form.processing" class="w-4 h-4 mr-2 animate-spin" />
                                    <Play v-else class="w-4 h-4 mr-2" /> 
                                    {{ form.processing ? 'Memulai Sesi...' : 'Mulai Penimbangan' }}
                                </Button>
                            </div>
                        </form>
                    </div>
                </Card>
            </div>
                  <!-- History -->
            <Card class="overflow-hidden bg-amber-50/60 border-amber-200 shadow-sm dark:bg-amber-950/40 dark:border-amber-800">
                <div class="p-6 border-b border-border">
                    <CardTitle class="text-xl font-bold text-foreground">Riwayat Penimbangan Singkong</CardTitle>
                </div>
                <div class="overflow-x-auto">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead class="px-6 py-4 text-xs font-bold text-muted-foreground uppercase whitespace-nowrap">Waktu</TableHead>
                                <TableHead class="px-6 py-4 text-xs font-bold text-muted-foreground uppercase whitespace-nowrap">No Surat</TableHead>
                                <TableHead class="px-6 py-4 text-xs font-bold text-muted-foreground uppercase whitespace-nowrap">Supplier</TableHead>
                                <TableHead class="px-6 py-4 text-xs font-bold text-muted-foreground uppercase whitespace-nowrap">Asal</TableHead>
                                <TableHead class="px-6 py-4 text-xs font-bold text-muted-foreground uppercase whitespace-nowrap">Jenis</TableHead>
                                <TableHead class="px-6 py-4 text-xs font-bold text-muted-foreground uppercase whitespace-nowrap">Berat</TableHead>
                                <TableHead class="px-6 py-4 text-xs font-bold text-muted-foreground uppercase whitespace-nowrap">Status</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="h in history.data" :key="h.id">
                                <TableCell class="px-6 py-4 text-muted-foreground whitespace-nowrap">{{ formatDateTime(h.created_at) }}</TableCell>
                                <TableCell class="px-6 py-4 font-mono text-xs">{{ h.no_surat }}</TableCell>
                                <TableCell class="px-6 py-4 font-bold text-sm">{{ h.nama_supplier }}</TableCell>
                                <TableCell class="px-6 py-4 text-sm text-muted-foreground">{{ h.asal }}</TableCell>
                                <TableCell class="px-6 py-4">
                                    <Badge variant="secondary">{{ h.jenis_singkong }}</Badge>
                                </TableCell>
                                <TableCell class="px-6 py-4 font-bold whitespace-nowrap">{{ formatWeight(h.berat) }} <span class="text-xs font-normal text-muted-foreground">kg</span></TableCell>
                                <TableCell class="px-6 py-4">
                                    <Badge v-if="h.status === 'selesai'" class="bg-emerald-100 text-emerald-800 border-emerald-200">Selesai</Badge>
                                    <Badge v-else variant="outline" class="bg-amber-100 text-amber-800 border-amber-200">{{ h.status }}</Badge>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </div>
                <div v-if="history.data.length === 0" class="p-12 text-center text-muted-foreground">
                    Belum ada riwayat untuk shift ini.
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
