<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { UserPlus, Mail, Lock, User, Shield } from 'lucide-vue-next';

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    role: 'operator',
    tipe: 'fg',
});

const tipeOptions = [
    { value: 'fg', label: 'Formulasi Pasuruan (FG)' },
    { value: 'fg_psn', label: 'Finished Goods Pasuruan (PSN)' },
    { value: 'incoming_singkong', label: 'Incoming Singkong' },
    { value: 'incoming_rmpm', label: 'Incoming RMPM' },
    { value: 'fg_surabaya', label: 'Formulasi Surabaya' },
    { value: 'cs_noodle_sby', label: 'CS Noodle Surabaya' },
    { value: 'cs_fg_sby', label: 'CS FG Surabaya' },
];

const submit = () => {
    form.post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Register" />

        <form @submit.prevent="submit" class="space-y-5">
            <!-- Name -->
            <div class="space-y-2">
                <Label for="name" class="ml-1 text-xs font-bold text-gray-500 uppercase">Nama Lengkap</Label>
                <div class="relative group">
                    <div class="absolute inset-y-0 left-0 flex items-center pl-4 text-gray-400 transition-colors pointer-events-none group-focus-within:text-blue-600">
                        <User class="w-5 h-5" />
                    </div>
                    <Input
                        id="name"
                        type="text"
                        class="py-6 pl-12 transition-all border-gray-100 bg-gray-50/50 focus:bg-white focus:ring-blue-600/20 rounded-2xl"
                        v-model="form.name"
                        required
                        autofocus
                        autocomplete="name"
                        placeholder="Nama lengkap"
                    />
                </div>
                <InputError class="mt-1" :message="form.errors.name" />
            </div>

            <!-- Email -->
            <div class="space-y-2">
                <Label for="email" class="ml-1 text-xs font-bold text-gray-500 uppercase">Email Address</Label>
                <div class="relative group">
                    <div class="absolute inset-y-0 left-0 flex items-center pl-4 text-gray-400 transition-colors pointer-events-none group-focus-within:text-blue-600">
                        <Mail class="w-5 h-5" />
                    </div>
                    <Input
                        id="email"
                        type="email"
                        class="py-6 pl-12 transition-all border-gray-100 bg-gray-50/50 focus:bg-white focus:ring-blue-600/20 rounded-2xl"
                        v-model="form.email"
                        required
                        autocomplete="username"
                        placeholder="name@gmail.com"
                    />
                </div>
                <InputError class="mt-1" :message="form.errors.email" />
            </div>

            <!-- Role -->
            <div class="space-y-2">
                <Label for="role" class="ml-1 text-xs font-bold text-gray-500 uppercase">Role</Label>
                <div class="relative group">
                    <div class="absolute inset-y-0 left-0 flex items-center pl-4 text-gray-400 transition-colors pointer-events-none group-focus-within:text-blue-600">
                        <Shield class="w-5 h-5" />
                    </div>
                    <select
                        id="role"
                        v-model="form.role"
                        class="w-full py-3 pl-12 pr-4 transition-all border border-gray-100 bg-gray-50/50 focus:bg-white focus:ring-blue-600/20 rounded-2xl text-sm appearance-none"
                    >
                        <option value="admin">Admin</option>
                        <option value="operator">Operator</option>
                    </select>
                </div>
                <InputError class="mt-1" :message="form.errors.role" />
            </div>

            <!-- Tipe (Only for operator) -->
            <div v-if="form.role === 'operator'" class="space-y-2">
                <Label for="tipe" class="ml-1 text-xs font-bold text-gray-500 uppercase">Tipe Operator</Label>
                <select
                    id="tipe"
                    v-model="form.tipe"
                    class="w-full py-3 px-4 transition-all border border-gray-100 bg-gray-50/50 focus:bg-white focus:ring-blue-600/20 rounded-2xl text-sm appearance-none"
                >
                    <option v-for="t in tipeOptions" :key="t.value" :value="t.value">{{ t.label }}</option>
                </select>
                <InputError class="mt-1" :message="form.errors.tipe" />
            </div>

            <!-- Password -->
            <div class="space-y-2">
                <Label for="password" class="ml-1 text-xs font-bold text-gray-500 uppercase">Password</Label>
                <div class="relative group">
                    <div class="absolute inset-y-0 left-0 flex items-center pl-4 text-gray-400 transition-colors pointer-events-none group-focus-within:text-blue-600">
                        <Lock class="w-5 h-5" />
                    </div>
                    <Input
                        id="password"
                        type="password"
                        class="py-6 pl-12 transition-all border-gray-100 bg-gray-50/50 focus:bg-white focus:ring-blue-600/20 rounded-2xl"
                        v-model="form.password"
                        required
                        autocomplete="new-password"
                        placeholder="••••••••"
                    />
                </div>
                <InputError class="mt-1" :message="form.errors.password" />
            </div>

            <!-- Confirm Password -->
            <div class="space-y-2">
                <Label for="password_confirmation" class="ml-1 text-xs font-bold text-gray-500 uppercase">Konfirmasi Password</Label>
                <div class="relative group">
                    <div class="absolute inset-y-0 left-0 flex items-center pl-4 text-gray-400 transition-colors pointer-events-none group-focus-within:text-blue-600">
                        <Lock class="w-5 h-5" />
                    </div>
                    <Input
                        id="password_confirmation"
                        type="password"
                        class="py-6 pl-12 transition-all border-gray-100 bg-gray-50/50 focus:bg-white focus:ring-blue-600/20 rounded-2xl"
                        v-model="form.password_confirmation"
                        required
                        autocomplete="new-password"
                        placeholder="••••••••"
                    />
                </div>
                <InputError class="mt-1" :message="form.errors.password_confirmation" />
            </div>

            <!-- Submit -->
            <Button
                type="submit"
                class="w-full text-lg font-black text-white transition-all bg-blue-600 shadow-xl hover:bg-blue-700 py-7 rounded-2xl shadow-blue-100 group"
                :class="{ 'opacity-25': form.processing }"
                :disabled="form.processing"
            >
                <UserPlus class="w-5 h-5 mr-2 transition-transform group-hover:translate-x-1" />
                Register
            </Button>

            <div class="text-center">
                <Link :href="route('login')" class="text-sm font-medium text-gray-500 hover:text-blue-600 transition-colors">
                    Sudah punya akun? <span class="font-bold text-blue-600">Sign In</span>
                </Link>
            </div>
        </form>
    </GuestLayout>
</template>