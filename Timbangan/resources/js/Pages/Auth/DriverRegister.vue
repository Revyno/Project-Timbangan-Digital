<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { UserPlus, Building2, QrCode, Download, CheckCircle2, Truck } from 'lucide-vue-next';

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

        <div v-if="success" class="text-center space-y-6 animate-in fade-in zoom-in duration-500">
            <div class="flex justify-center">
                <div class="bg-green-100 p-4 rounded-full">
                    <CheckCircle2 class="w-12 h-12 text-green-600" />
                </div>
            </div>
            
            <div class="space-y-2">
                <h2 class="text-2xl font-black text-gray-900">Registrasi Berhasil!</h2>
                <p class="text-gray-500 font-medium">Halo {{ driver.name }}, simpan QR Code Anda di bawah ini untuk digunakan saat penimbangan.</p>
            </div>

            <div class="bg-white p-6 rounded-[2rem] border-4 border-blue-50 shadow-2xl inline-block mx-auto">
                <img :src="getQrUrl(driver.qr_code)" class="w-48 h-48 mx-auto" alt="QR Code" />
                <div class="mt-4 pt-4 border-t border-gray-100">
                    <p class="text-xs font-black text-blue-600 uppercase tracking-widest">{{ driver.qr_code }}</p>
                    <p class="text-sm font-bold text-gray-900 mt-1">{{ driver.supplier.name }}</p>
                </div>
            </div>

            <div class="pt-6">
                <Link :href="route('driver.register')" class="text-blue-600 font-bold hover:underline">
                    Daftar driver lain
                </Link>
            </div>
        </div>

        <form v-else @submit.prevent="submit" class="space-y-6">
            <div class="text-center mb-8">
                <h2 class="text-2xl font-black text-gray-900">Registrasi Driver</h2>
                <p class="text-sm text-gray-500 mt-1">Lengkapi data untuk mendapatkan QR Code identitas.</p>
            </div>

            <div class="space-y-2">
                <Label for="name" class="ml-1 text-xs font-bold text-gray-500 uppercase">Nama Lengkap Sopir</Label>
                <div class="relative group">
                    <div class="absolute inset-y-0 left-0 flex items-center pl-4 text-gray-400 transition-colors pointer-events-none group-focus-within:text-blue-600">
                        <UserPlus class="w-5 h-5" />
                    </div>
                    <Input
                        id="name"
                        type="text"
                        class="py-6 pl-12 transition-all border-gray-100 bg-gray-50/50 focus:bg-white focus:ring-blue-600/20 rounded-2xl"
                        v-model="form.name"
                        required
                        autofocus
                        placeholder="Contoh: Budi Santoso"
                    />
                </div>
                <InputError class="mt-2" :message="form.errors.name" />
            </div>

            <div class="space-y-2">
                <Label for="nomor_plat" class="ml-1 text-xs font-bold text-gray-500 uppercase">Nomor Plat Kendaraan</Label>
                <div class="relative group">
                    <div class="absolute inset-y-0 left-0 flex items-center pl-4 text-gray-400 transition-colors pointer-events-none group-focus-within:text-blue-600">
                        <Truck class="w-5 h-5" />
                    </div>
                    <Input
                        id="nomor_plat"
                        type="text"
                        class="py-6 pl-12 transition-all border-gray-100 bg-gray-50/50 focus:bg-white focus:ring-blue-600/20 rounded-2xl"
                        v-model="form.nomor_plat"
                        required
                        placeholder="Contoh: L 1234 ABC"
                    />
                </div>
                <InputError class="mt-2" :message="form.errors.nomor_plat" />
            </div>

            <div class="space-y-2">
                <Label for="supplier" class="ml-1 text-xs font-bold text-gray-500 uppercase">Pilih Supplier</Label>
                <div class="relative group">
                    <div class="absolute inset-y-0 left-0 flex items-center pl-4 text-gray-400 transition-colors pointer-events-none group-focus-within:text-blue-600">
                        <Building2 class="w-5 h-5" />
                    </div>
                    <select
                        id="supplier"
                        v-model="form.supplier_id"
                        required
                        class="w-full py-3 pl-12 pr-4 transition-all border-gray-100 bg-gray-50/50 focus:bg-white focus:ring-blue-600/20 rounded-2xl text-sm font-medium"
                    >
                        <option value="" disabled>-- Pilih Supplier --</option>
                        <option v-for="s in suppliers" :key="s.id" :value="s.id">{{ s.name }}</option>
                    </select>
                </div>
                <InputError class="mt-2" :message="form.errors.supplier_id" />
            </div>

            <div class="space-y-2">
                <Label for="asal" class="ml-1 text-xs font-bold text-gray-500 uppercase">Pilih Asal</Label>
                <div class="relative group">
                    <div class="absolute inset-y-0 left-0 flex items-center pl-4 text-gray-400 transition-colors pointer-events-none group-focus-within:text-blue-600">
                        <Building2 class="w-5 h-5" />
                    </div>
                    <select
                        id="asal"
                        v-model="form.asal"
                        required
                        class="w-full py-3 pl-12 pr-4 transition-all border-gray-100 bg-gray-50/50 focus:bg-white focus:ring-blue-600/20 rounded-2xl text-sm font-medium"
                    >
                        <option value="" disabled>-- Pilih Asal --</option>
                        <option v-for="a in asalOptions" :key="a" :value="a">{{ a }}</option>
                    </select>
                </div>
                <InputError class="mt-2" :message="form.errors.asal" />
            </div>

            <Button
                class="w-full text-lg font-black text-white transition-all bg-blue-600 shadow-xl hover:bg-blue-700 py-7 rounded-2xl shadow-blue-100 group"
                :class="{ 'opacity-25': form.processing }"
                :disabled="form.processing"
            >
                Dapatkan QR Code
            </Button>

            <div class="text-center pt-4 border-t border-gray-50">
                <Link :href="route('login')" class="text-sm font-medium text-gray-500 hover:text-blue-600 transition-colors">
                    Kembali ke <span class="font-bold text-blue-600">Login</span>
                </Link>
            </div>
        </form>
    </GuestLayout>
</template>
