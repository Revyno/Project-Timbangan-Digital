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
import { Building2, Plus, Search } from 'lucide-vue-next';

const props = defineProps({
    suppliers: Array,
});

const form = useForm({
    name: '',
});

const submit = () => {
    form.post(route('admin.master.suppliers.store'), {
        onSuccess: () => form.reset(),
    });
};
</script>

<template>
    <Head title="Master Data Suppliers" />

    <AuthenticatedLayout>
        <div class="space-y-6">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-2xl font-black text-gray-800">Master Data Suppliers</h2>
                    <p class="text-sm text-gray-500 mt-1">Kelola data supplier untuk identifikasi penimbangan.</p>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Form Add -->
                <Card class="h-fit">
                    <CardHeader>
                        <CardTitle class="text-lg flex items-center gap-2">
                            <Plus class="w-5 h-5 text-blue-600" />
                            Tambah Supplier
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <form @submit.prevent="submit" class="space-y-4">
                            <div class="space-y-2">
                                <Label for="name">Nama Supplier</Label>
                                <Input id="name" v-model="form.name" required placeholder="Contoh: PT. Sumber Alam" />
                            </div>
                            <Button :disabled="form.processing" class="w-full bg-blue-600 hover:bg-blue-700">
                                Simpan Supplier
                            </Button>
                        </form>
                    </CardContent>
                </Card>

                <!-- List Table -->
                <Card class="lg:col-span-2">
                    <CardHeader class="flex flex-row items-center justify-between">
                        <CardTitle class="text-lg">Daftar Supplier</CardTitle>
                        <div class="relative w-64">
                            <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" />
                            <Input placeholder="Cari supplier..." class="pl-10" />
                        </div>
                    </CardHeader>
                    <CardContent class="p-0">
                        <Table>
                            <TableHeader class="bg-gray-50">
                                <TableRow>
                                    <TableHead class="w-16">No.</TableHead>
                                    <TableHead>Nama Supplier</TableHead>
                                    <TableHead>Terdaftar Pada</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow v-for="(s, index) in suppliers" :key="s.id">
                                    <TableCell class="font-bold text-gray-400">{{ index + 1 }}</TableCell>
                                    <TableCell class="font-bold text-gray-800">
                                        <div class="flex items-center gap-2">
                                            <Building2 class="w-4 h-4 text-gray-400" />
                                            {{ s.name }}
                                        </div>
                                    </TableCell>
                                    <TableCell class="text-sm text-gray-500">
                                        {{ new Date(s.created_at).toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' }) }}
                                    </TableCell>
                                </TableRow>
                                <TableRow v-if="suppliers.length === 0">
                                    <TableCell colspan="3" class="text-center py-12 text-gray-400 italic">
                                        Belum ada data supplier.
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
