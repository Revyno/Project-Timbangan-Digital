<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
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
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { UserCheck, Plus, Search, QrCode, Download } from 'lucide-vue-next';

const props = defineProps({
    drivers: Array,
    suppliers: Array,
    asalOptions: Array,
});

const form = useForm({
    name: '',
    supplier_id: '',
    nomor_plat: '',
    asal: '',
});

const submit = () => {
    form.post(route('admin.master.drivers.store'), {
        onSuccess: () => form.reset(),
    });
};

const getQrUrl = (code) => {
    return `https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=${code}`;
};

const printQr = (driver) => {
    const win = window.open('', '_blank');
    win.document.write(`
        <html>
            <head>
                <title>Print QR Code - ${driver.name}</title>
                <style>
                    body { font-family: Arial, sans-serif; text-align: center; padding: 50px; }
                    .card { border: 2px solid #000; padding: 20px; display: inline-block; border-radius: 10px; }
                    h1 { margin-bottom: 5px; }
                    p { color: #666; margin-bottom: 20px; }
                    .qr { width: 200px; height: 200px; }
                </style>
            </head>
            <body onload="window.print(); window.close();">
                <div class="card">
                    <h1>${driver.name}</h1>
                    <p>Supplier: ${driver.supplier.name}</p>
                    <img src="${getQrUrl(driver.qr_code)}" class="qr" />
                    <br><br>
                    <small>KODE: ${driver.qr_code}</small>
                </div>
            </body>
        </html>
    `);
    win.document.close();
};
</script>

<template>
    <Head title="Master Data Drivers" />

    <AuthenticatedLayout>
        <div class="space-y-6">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-2xl font-black text-gray-800">Master Data Drivers</h2>
                    <p class="text-sm text-gray-500 mt-1">Kelola data sopir dan generate QR Code untuk identifikasi otomatis.</p>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Form Add -->
                <Card class="h-fit">
                    <CardHeader>
                        <CardTitle class="text-lg flex items-center gap-2">
                            <Plus class="w-5 h-5 text-blue-600" />
                            Tambah Driver
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <form @submit.prevent="submit" class="space-y-4">
                            <div class="space-y-2">
                                <Label for="name">Nama Sopir</Label>
                                <Input id="name" v-model="form.name" required placeholder="Nama Lengkap Sopir" />
                            </div>
                            <div class="space-y-2">
                                <Label for="nomor_plat">Nomor Plat</Label>
                                <Input id="nomor_plat" v-model="form.nomor_plat" required placeholder="Contoh: L 1234 ABC" />
                            </div>
                            <div class="space-y-2">
                                <Label for="supplier">Supplier</Label>
                                <select 
                                    id="supplier" 
                                    v-model="form.supplier_id" 
                                    required
                                    class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                                >
                                    <option value="" disabled>-- Pilih Supplier --</option>
                                    <option v-for="s in suppliers" :key="s.id" :value="s.id">{{ s.name }}</option>
                                </select>
                            </div>
                            <div class="space-y-2">
                                <Label for="asal">Asal</Label>
                                <select 
                                    id="asal" 
                                    v-model="form.asal" 
                                    required
                                    class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                                >
                                    <option value="" disabled>-- Pilih Asal --</option>
                                    <option v-for="a in asalOptions" :key="a" :value="a">{{ a }}</option>
                                </select>
                            </div>
                            <Button :disabled="form.processing" class="w-full bg-blue-600 hover:bg-blue-700">
                                Simpan Driver & Generate QR
                            </Button>
                        </form>
                    </CardContent>
                </Card>

                <!-- List Table -->
                <Card class="lg:col-span-2">
                    <CardHeader class="flex flex-row items-center justify-between">
                        <CardTitle class="text-lg">Daftar Sopir</CardTitle>
                        <div class="relative w-64">
                            <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" />
                            <Input placeholder="Cari sopir..." class="pl-10" />
                        </div>
                    </CardHeader>
                    <CardContent class="p-0">
                        <Table>
                            <TableHeader class="bg-gray-50">
                                <TableRow>
                                    <TableHead>Nama Sopir</TableHead>
                                    <TableHead>Nomor Plat</TableHead>
                                    <TableHead>Supplier</TableHead>
                                    <TableHead>Asal</TableHead>
                                    <TableHead>Kode QR</TableHead>
                                    <TableHead class="text-right">Aksi</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow v-for="d in drivers" :key="d.id">
                                    <TableCell class="font-bold text-gray-800">
                                        <div class="flex items-center gap-2">
                                            <UserCheck class="w-4 h-4 text-gray-400" />
                                            {{ d.name }}
                                        </div>
                                    </TableCell>
                                    <TableCell>{{ d.nomor_plat || '-' }}</TableCell>
                                    <TableCell>{{ d.supplier.name }}</TableCell>
                                    <TableCell>{{ d.asal || '-' }}</TableCell>
                                    <TableCell>
                                        <div class="flex items-center gap-2">
                                            <code class="bg-gray-100 px-2 py-0.5 rounded text-xs font-bold text-blue-600">
                                                {{ d.qr_code }}
                                            </code>
                                        </div>
                                    </TableCell>
                                    <TableCell class="text-right">
                                        <Button @click="printQr(d)" variant="outline" size="sm" class="border-blue-200 text-blue-600 hover:bg-blue-50 font-bold">
                                            <QrCode class="w-4 h-4 mr-1.5" /> Print QR
                                        </Button>
                                    </TableCell>
                                </TableRow>
                                <TableRow v-if="drivers.length === 0">
                                    <TableCell colspan="4" class="text-center py-12 text-gray-400 italic">
                                        Belum ada data sopir.
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
