<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
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

const search = ref('');

const filteredSuppliers = computed(() => {
    if (!search.value) return props.suppliers;
    const q = search.value.toLowerCase();
    return props.suppliers.filter(s => 
        s.name && s.name.toLowerCase().includes(q)
    );
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
            <div>
                <h2 class="font-display text-2xl tracking-wider text-foreground">Master Data Suppliers</h2>
                <p class="font-mono text-xs font-bold text-muted-foreground mt-1">Kelola data supplier untuk identifikasi penimbangan.</p>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Form Add -->
                <Card class="border-2 border-black rounded-xl shadow-[2px_2px_0_0_#000000] bg-violet-50/60 dark:bg-violet-950/40 hover:translate-y-[-2px] transition-transform">
                    <CardHeader>
                        <CardTitle class="font-display text-xl tracking-wider flex items-center gap-2">
                            <Plus class="w-4 h-4 text-primary" />
                            Tambah Supplier
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <form @submit.prevent="submit" class="space-y-4">
                            <div class="space-y-2">
                                <Label for="name">Nama Supplier</Label>
                                <Input id="name" v-model="form.name" required placeholder="Contoh: PT. Sumber Alam" />
                            </div>
                            <Button :disabled="form.processing" class="w-full">
                                Simpan Supplier
                            </Button>
                        </form>
                    </CardContent>
                </Card>

                <!-- List Table -->
                <Card class="lg:col-span-2 border-2 border-black rounded-xl shadow-[4px_4px_0_0_#000000] bg-violet-50/60 dark:bg-violet-950/40">
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-4">
                        <CardTitle class="font-display text-xl tracking-wider">Daftar Supplier</CardTitle>
                        <div class="relative w-64">
                            <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-muted-foreground" />
                            <Input v-model="search" placeholder="Cari supplier..." class="pl-10" />
                        </div>
                    </CardHeader>
                    <CardContent class="p-0">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead class="w-16">No.</TableHead>
                                    <TableHead>Nama Supplier</TableHead>
                                    <TableHead>Terdaftar Pada</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow v-for="(s, index) in filteredSuppliers" :key="s.id">
                                    <TableCell class="font-medium text-muted-foreground">{{ index + 1 }}</TableCell>
                                    <TableCell class="font-bold">
                                        <div class="flex items-center gap-2">
                                            <Building2 class="w-4 h-4 text-muted-foreground" />
                                            {{ s.name }}
                                        </div>
                                    </TableCell>
                                    <TableCell class="text-sm text-muted-foreground">
                                        {{ new Date(s.created_at).toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' }) }}
                                    </TableCell>
                                </TableRow>
                                <TableRow v-if="filteredSuppliers.length === 0">
                                    <TableCell colspan="3" class="text-center py-12 text-muted-foreground italic">
                                        Belum ada data atau tidak ditemukan.
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
