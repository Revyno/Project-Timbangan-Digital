<script setup>
import { ref } from 'vue';
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
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
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

const formatWeight = (weight) => {
    if (!weight || weight <= 0) return 'Belum ditimbang';
    return new Intl.NumberFormat('id-ID', { minimumFractionDigits: 3, maximumFractionDigits: 3 }).format(weight) + ' kg';
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
                <Card class="p-5 bg-gradient-to-br from-emerald-600 to-teal-700 rounded-2xl shadow-lg text-white border-none">
                    <div class="flex items-center gap-4 mb-4">
                        <div class="p-3 bg-white/20 rounded-xl">
                            <Truck class="w-6 h-6 text-white" />
                        </div>
                        <div>
                            <h5 class="text-xs font-bold uppercase tracking-widest opacity-70">Operator Incoming</h5>
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
                    <!-- Active Session Info -->
                    <div v-if="activeSession" class="space-y-4">
                        <div class="flex items-center justify-between border-b border-gray-100 pb-4">
                            <div>
                                <Badge class="bg-emerald-100 text-emerald-700 mb-2">SESI AKTIF</Badge>
                                <h3 class="text-2xl font-black text-gray-900">{{ activeSession.nama_supplier }}</h3>
                                <p class="text-sm text-gray-500">KP: {{ activeSession.kode_produksi }} | No Surat: {{ activeSession.no_surat }}</p>
                            </div>
                            <div class="flex gap-2">
                                <Button @click="nextSession" variant="outline" class="border-emerald-200 text-emerald-700 hover:bg-emerald-50 py-6 px-6">
                                    <RotateCcw class="w-4 h-4 mr-2" /> Ganti Sesi
                                </Button>
                                <Button @click="stopSession" variant="destructive" class="bg-red-600 py-6 px-6">
                                    <LogOut class="w-4 h-4 mr-2" /> Stop Shift
                                </Button>
                            </div>
                        </div>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                            <div>
                                <p class="text-gray-400 font-bold uppercase text-[10px]">Sopir</p>
                                <p class="font-bold">{{ activeSession.nama_sopir }}</p>
                            </div>
                            <div>
                                <p class="text-gray-400 font-bold uppercase text-[10px]">Plat Nomor</p>
                                <p class="font-bold">{{ activeSession.nomor_plat }}</p>
                            </div>
                            <div>
                                <p class="text-gray-400 font-bold uppercase text-[10px]">Asal</p>
                                <p class="font-bold">{{ activeSession.asal }}</p>
                            </div>
                            <div>
                                <p class="text-gray-400 font-bold uppercase text-[10px]">Jenis</p>
                                <p class="font-bold">{{ activeSession.jenis_singkong }}</p>
                            </div>
                        </div>
                        <div class="mt-4 p-3 bg-emerald-50 rounded-xl border border-emerald-100">
                             <p class="text-sm text-emerald-700 font-medium flex items-center gap-2">
                                <Clock class="w-4 h-4 animate-pulse" />
                                Menunggu data berat dari timbangan...
                            </p>
                        </div>
                    </div>

                    <!-- Start Session Form -->
                    <div v-else>
                        <div class="flex items-center justify-between mb-6">
                            <h3 class="text-lg font-bold text-gray-900">Mulai Sesi Baru</h3>
                            <Button @click="identifyDriver" type="button" class="bg-blue-600 hover:bg-blue-700 font-bold">
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
                            <p v-if="form.errors.no_surat" class="text-red-500 text-xs">{{ form.errors.no_surat }}</p>
                        </div>

                        <!-- Supplier — DROPDOWN -->
                        <div class="space-y-1">
                            <Label for="nama_supplier">Supplier</Label>
                            <select
                                id="nama_supplier"
                                v-model="form.nama_supplier"
                                required
                                class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
                            >
                                <option value="" disabled>-- Pilih Supplier --</option>
                                <option v-for="s in supplierOptions" :key="s" :value="s">{{ s }}</option>
                            </select>
                            <p v-if="form.errors.nama_supplier" class="text-red-500 text-xs">{{ form.errors.nama_supplier }}</p>
                        </div>

                        <!-- Asal — DROPDOWN -->
                        <div class="space-y-1">
                            <Label for="asal">Asal</Label>
                            <select
                                id="asal"
                                v-model="form.asal"
                                required
                                class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
                            >
                                <option value="" disabled>-- Pilih Asal --</option>
                                <option v-for="a in asalOptions" :key="a" :value="a">{{ a }}</option>
                            </select>
                            <p v-if="form.errors.asal" class="text-red-500 text-xs">{{ form.errors.asal }}</p>
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
                            <p v-if="form.errors.nama_sopir" class="text-red-500 text-xs">{{ form.errors.nama_sopir }}</p>
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
                            <p v-if="form.errors.nomor_plat" class="text-red-500 text-xs">{{ form.errors.nomor_plat }}</p>
                        </div>

                        <!-- Jenis Singkong — DROPDOWN -->
                        <div class="space-y-1">
                            <Label for="jenis_singkong">Jenis Singkong</Label>
                            <select
                                id="jenis_singkong"
                                v-model="form.jenis_singkong"
                                required
                                class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
                            >
                                <option value="" disabled>-- Pilih Jenis --</option>
                                <option v-for="j in jenisOptions" :key="j" :value="j">{{ j }}</option>
                            </select>
                            <p v-if="form.errors.jenis_singkong" class="text-red-500 text-xs">{{ form.errors.jenis_singkong }}</p>
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
                            <p v-if="form.errors.kode_produksi" class="text-red-500 text-xs">{{ form.errors.kode_produksi }}</p>
                        </div>

                        <div class="lg:col-span-3 pt-2">
                            <Button
                                type="submit"
                                :disabled="form.processing"
                                class="w-full bg-emerald-600 hover:bg-emerald-700 py-6 font-bold text-lg"
                            >
                                <Loader2 v-if="form.processing" class="w-5 h-5 mr-2 animate-spin" />
                                <Play v-else class="w-5 h-5 mr-2" /> 
                                {{ form.processing ? 'Memulai Sesi...' : 'Mulai Penimbangan' }}
                            </Button>
                        </div>
                    </form>
                </div>
                </Card>
            </div>

            <!-- History -->
            <Card class="bg-white border-none rounded-3xl shadow-xl overflow-hidden">
                <div class="p-6 border-b border-gray-100">
                    <h5 class="text-xl font-black text-gray-900">Riwayat Penimbangan Singkong</h5>
                </div>
                <div class="p-0">
                    <Table>
                        <TableHeader class="bg-gray-50">
                            <TableRow>
                                <TableHead class="px-6 py-4">Waktu</TableHead>
                                <TableHead class="px-6 py-4">No Surat</TableHead>
                                <TableHead class="px-6 py-4">Supplier</TableHead>
                                <TableHead class="px-6 py-4">Asal</TableHead>
                                <TableHead class="px-6 py-4">Jenis</TableHead>
                                <TableHead class="px-6 py-4">Berat</TableHead>
                                <TableHead class="px-6 py-4">Status</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="h in history.data" :key="h.id">
                                <TableCell class="px-6 py-4">{{ formatDateTime(h.created_at) }}</TableCell>
                                <TableCell class="px-6 py-4 font-mono">{{ h.no_surat }}</TableCell>
                                <TableCell class="px-6 py-4 font-bold">{{ h.nama_supplier }}</TableCell>
                                <TableCell class="px-6 py-4">{{ h.asal }}</TableCell>
                                <TableCell class="px-6 py-4">{{ h.jenis_singkong }}</TableCell>
                                <TableCell class="px-6 py-4 font-black">{{ formatWeight(h.berat) }}</TableCell>
                                <TableCell class="px-6 py-4">
                                    <Badge :variant="h.status === 'selesai' ? 'default' : 'secondary'">{{ h.status }}</Badge>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                    <div v-if="history.data.length === 0" class="p-12 text-center text-gray-400">
                        Belum ada riwayat untuk shift ini.
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
