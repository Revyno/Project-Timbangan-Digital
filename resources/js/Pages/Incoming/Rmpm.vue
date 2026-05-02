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
    Package 
} from 'lucide-vue-next';

const props = defineProps({
    activeSession: Object,
    totalShift: Number,
    totalBerat: Number,
    history: Object,
});

const { auth } = usePage().props;

const form = useForm({
    tanggal_kedatangan: props.activeSession?.tanggal_kedatangan || new Date().toISOString().split('T')[0],
    nama_barang: props.activeSession?.nama_barang || '',
    jenis_barang: props.activeSession?.jenis_barang || 'raw_material',
    asal: props.activeSession?.asal || '',
    nama_supplier: props.activeSession?.nama_supplier || '',
    no_surat: props.activeSession?.no_surat || '',
    nama_sopir: props.activeSession?.nama_sopir || '',
    nomor_plat: props.activeSession?.nomor_plat || '',
    total_qty: props.activeSession?.total_qty || 1,
    kode_batch: props.activeSession?.kode_batch || '',
    expired_date: props.activeSession?.expired_date || '',
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
                        <div class="flex items-center justify-between border-b border-gray-100 pb-4">
                            <div>
                                <Badge class="bg-indigo-100 text-indigo-700 mb-2">SESI AKTIF</Badge>
                                <h3 class="text-2xl font-black text-gray-900">{{ activeSession.nama_barang }}</h3>
                                <p class="text-sm text-gray-500">{{ activeSession.nama_supplier }} | No Surat: {{ activeSession.no_surat }}</p>
                            </div>
                            <div class="flex gap-2">
                                <Button @click="nextSession" variant="outline" class="border-indigo-200 text-indigo-700 hover:bg-indigo-50 py-6 px-6">
                                    <RotateCcw class="w-4 h-4 mr-2" /> Ganti Sesi
                                </Button>
                                <Button @click="stopSession" variant="destructive" class="bg-red-600 py-6 px-6">
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
                    </div>

                    <form v-else @submit.prevent="startSession" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <div class="space-y-1">
                            <Label>Tanggal Kedatangan</Label>
                            <Input v-model="form.tanggal_kedatangan" type="date" required />
                        </div>
                        <div class="space-y-1">
                            <Label>Nama Barang</Label>
                            <Input v-model="form.nama_barang" required placeholder="Nama Item RMPM" />
                        </div>
                        <div class="space-y-1">
                            <Label>Jenis Barang</Label>
                            <select v-model="form.jenis_barang" class="w-full bg-white border border-gray-200 rounded-md p-2">
                                <option value="raw_material">Raw Material</option>
                                <option value="packaging_material">Packaging Material</option>
                                <option value="lainnya">Lainnya</option>
                            </select>
                        </div>
                        <div class="space-y-1">
                            <Label>Supplier</Label>
                            <Input v-model="form.nama_supplier" required placeholder="Nama Supplier" />
                        </div>
                        <div class="space-y-1">
                            <Label>Asal</Label>
                            <Input v-model="form.asal" required placeholder="Asal Barang" />
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
                            <Button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 py-6 font-bold text-lg">
                                <Play class="w-5 h-5 mr-2" /> Mulai Penimbangan
                            </Button>
                        </div>
                    </form>
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
                </div>
            </Card>
        </div>
    </AuthenticatedLayout>
</template>
