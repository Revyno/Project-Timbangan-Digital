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
    Truck 
} from 'lucide-vue-next';

const props = defineProps({
    activeSession: Object,
    totalShift: Number,
    totalBerat: Number,
    history: Object,
});

const { auth } = usePage().props;

const form = useForm({
    no_surat: props.activeSession?.no_surat || '',
    nama_supplier: props.activeSession?.nama_supplier || '',
    asal: props.activeSession?.asal || '',
    nama_sopir: props.activeSession?.nama_sopir || '',
    nomor_plat: props.activeSession?.nomor_plat || '',
    jenis_singkong: props.activeSession?.jenis_singkong || '',
    kode_produksi: props.activeSession?.kode_produksi || '',
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

                    <form v-else @submit.prevent="startSession" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <div class="space-y-1">
                            <Label>No. Surat</Label>
                            <Input v-model="form.no_surat" required placeholder="No. Surat Jalan" />
                        </div>
                        <div class="space-y-1">
                            <Label>Supplier</Label>
                            <Input v-model="form.nama_supplier" required placeholder="Nama Supplier" />
                        </div>
                        <div class="space-y-1">
                            <Label>Asal</Label>
                            <Input v-model="form.asal" required placeholder="Asal Singkong" />
                        </div>
                        <div class="space-y-1">
                            <Label>Nama Sopir</Label>
                            <Input v-model="form.nama_sopir" required placeholder="Nama Sopir" />
                        </div>
                        <div class="space-y-1">
                            <Label>No. Plat</Label>
                            <Input v-model="form.nomor_plat" required placeholder="Nopol Kendaraan" />
                        </div>
                        <div class="space-y-1">
                            <Label>Jenis Singkong</Label>
                            <Input v-model="form.jenis_singkong" required placeholder="Contoh: Singkong Putih" />
                        </div>
                        <div class="space-y-1">
                            <Label>Kode Produksi</Label>
                            <Input v-model="form.kode_produksi" required placeholder="LOT / KP" />
                        </div>
                        <div class="lg:col-span-3 pt-2">
                            <Button type="submit" class="w-full bg-emerald-600 hover:bg-emerald-700 py-6 font-bold text-lg">
                                <Play class="w-5 h-5 mr-2" /> Mulai Penimbangan
                            </Button>
                        </div>
                    </form>
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
                                <TableHead class="px-6 py-4">Berat</TableHead>
                                <TableHead class="px-6 py-4">Status</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="h in history.data" :key="h.id">
                                <TableCell class="px-6 py-4">{{ formatDateTime(h.created_at) }}</TableCell>
                                <TableCell class="px-6 py-4 font-mono">{{ h.no_surat }}</TableCell>
                                <TableCell class="px-6 py-4 font-bold">{{ h.nama_supplier }}</TableCell>
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
    </AuthenticatedLayout>
</template>
