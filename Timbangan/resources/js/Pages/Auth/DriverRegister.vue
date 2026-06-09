<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/Components/ui/card';
import { UserPlus, Building2, QrCode, CheckCircle2, Truck } from 'lucide-vue-next';

const props = defineProps({
    suppliers: Array,
    asalOptions: Array,
    success: Boolean,
    driver: Object,
});

const form = useForm({
    name: '',
    supplier_id: '',
    nomor_plat: '',
    asal: '',
});

const submit = () => {
    form.post(route('driver.register.store'));
};

const getQrUrl = (code) => {
    return `https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=${code}`;
};
</script>

<template>
    <GuestLayout>
        <Head title="Registrasi Driver" />

        <div v-if="success" class="animate-in fade-in zoom-in duration-500 w-full">
            <Card class="border-emerald-200 shadow-md bg-emerald-50/30 dark:bg-emerald-950/20 dark:border-emerald-900">
                <CardHeader class="text-center pb-2">
                    <div class="flex justify-center mb-4">
                        <div class="bg-emerald-500/10 p-4 rounded-full border border-emerald-500/20">
                            <CheckCircle2 class="w-12 h-12 text-emerald-600" />
                        </div>
                    </div>
                    <CardTitle class="text-2xl font-bold text-foreground">Registrasi Berhasil!</CardTitle>
                    <CardDescription class="text-sm mt-2">
                        Halo <strong>{{ driver.name }}</strong>, simpan QR Code Anda di bawah ini untuk digunakan saat penimbangan.
                    </CardDescription>
                </CardHeader>
                <CardContent class="text-center space-y-6 pt-4">
                    <div class="bg-white dark:bg-slate-900 p-6 rounded-xl shadow-sm border border-border inline-block mx-auto">
                        <img :src="getQrUrl(driver.qr_code)" class="w-48 h-48 mx-auto border border-border rounded-lg p-2 bg-white mb-4 shadow-sm" alt="QR Code" />
                        <div class="pt-4 border-t border-border/50">
                            <p class="text-sm font-mono font-bold text-primary uppercase tracking-widest">{{ driver.qr_code }}</p>
                            <p class="text-base font-bold text-foreground mt-1">{{ driver.supplier.name }}</p>
                            <p class="text-xs text-muted-foreground mt-1">{{ driver.nomor_plat }}</p>
                        </div>
                    </div>

                    <div class="pt-2">
                        <Link :href="route('driver.register')" class="text-sm font-semibold text-primary hover:underline inline-flex items-center">
                            Daftar driver lain
                        </Link>
                    </div>
                </CardContent>
            </Card>
        </div>

        <Card v-else class="border-0 shadow-none sm:border sm:shadow-sm bg-transparent sm:bg-card">
            <CardHeader class="text-center space-y-1">
                <CardTitle class="text-2xl font-bold tracking-tight text-foreground">Registrasi Driver</CardTitle>
                <CardDescription>Lengkapi data untuk mendapatkan QR Code identitas.</CardDescription>
            </CardHeader>
            <CardContent>
                <form @submit.prevent="submit" class="space-y-4">

            <div class="space-y-2">
                <Label for="name">Nama Lengkap Sopir</Label>
                <div class="relative group">
                    <div class="absolute inset-y-0 left-0 flex items-center pl-3 text-muted-foreground pointer-events-none group-focus-within:text-primary">
                        <UserPlus class="w-4 h-4" />
                    </div>
                    <Input
                        id="name"
                        type="text"
                        class="pl-10"
                        v-model="form.name"
                        required
                        autofocus
                        placeholder="Contoh: Budi Santoso"
                    />
                </div>
                <InputError class="mt-1" :message="form.errors.name" />
            </div>

            <div class="space-y-2">
                <Label for="nomor_plat">Nomor Plat Kendaraan</Label>
                <div class="relative group">
                    <div class="absolute inset-y-0 left-0 flex items-center pl-3 text-muted-foreground pointer-events-none group-focus-within:text-primary">
                        <Truck class="w-4 h-4" />
                    </div>
                    <Input
                        id="nomor_plat"
                        type="text"
                        class="pl-10"
                        v-model="form.nomor_plat"
                        required
                        placeholder="Contoh: L 1234 ABC"
                    />
                </div>
                <InputError class="mt-1" :message="form.errors.nomor_plat" />
            </div>

            <div class="space-y-2">
                <Label for="supplier">Pilih Supplier</Label>
                <div class="relative group">
                    <div class="absolute inset-y-0 left-0 flex items-center pl-3 text-muted-foreground pointer-events-none group-focus-within:text-primary">
                        <Building2 class="w-4 h-4" />
                    </div>
                    <select
                        id="supplier"
                        v-model="form.supplier_id"
                        required
                        class="flex h-10 w-full rounded-md border border-input bg-background pl-10 pr-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        <option value="" disabled>-- Pilih Supplier --</option>
                        <option v-for="s in suppliers" :key="s.id" :value="s.id">{{ s.name }}</option>
                    </select>
                </div>
                <InputError class="mt-1" :message="form.errors.supplier_id" />
            </div>

            <div class="space-y-2">
                <Label for="asal">Pilih Asal</Label>
                <div class="relative group">
                    <div class="absolute inset-y-0 left-0 flex items-center pl-3 text-muted-foreground pointer-events-none group-focus-within:text-primary">
                        <Building2 class="w-4 h-4" />
                    </div>
                    <select
                        id="asal"
                        v-model="form.asal"
                        required
                        class="flex h-10 w-full rounded-md border border-input bg-background pl-10 pr-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        <option value="" disabled>-- Pilih Asal --</option>
                        <option v-for="a in asalOptions" :key="a" :value="a">{{ a }}</option>
                    </select>
                </div>
                <InputError class="mt-1" :message="form.errors.asal" />
            </div>

            <!-- Submit -->
            <Button
                type="submit"
                class="w-full flex items-center justify-center pt-2"
                :disabled="form.processing"
            >
                Dapatkan QR Code
            </Button>

                    <div class="text-center pt-4 border-t border-border">
                        <Link :href="route('login')" class="text-sm font-semibold text-muted-foreground hover:text-foreground transition-colors">
                            Kembali ke <span class="font-bold text-primary hover:underline">Login</span>
                        </Link>
                    </div>
                </form>
            </CardContent>
        </Card>
    </GuestLayout>
</template>
