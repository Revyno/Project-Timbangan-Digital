<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
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

        <form @submit.prevent="submit" class="space-y-4">
            <!-- Name -->
            <div class="space-y-2">
                <Label for="name">Nama Lengkap</Label>
                <div class="relative group">
                    <div class="absolute inset-y-0 left-0 flex items-center pl-3 text-muted-foreground pointer-events-none group-focus-within:text-primary">
                        <User class="w-4 h-4" />
                    </div>
                    <Input
                        id="name"
                        type="text"
                        class="pl-10"
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
                <Label for="email">Email Address</Label>
                <div class="relative group">
                    <div class="absolute inset-y-0 left-0 flex items-center pl-3 text-muted-foreground pointer-events-none group-focus-within:text-primary">
                        <Mail class="w-4 h-4" />
                    </div>
                    <Input
                        id="email"
                        type="email"
                        class="pl-10"
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
                <Label for="role">Role</Label>
                <div class="relative group">
                    <div class="absolute inset-y-0 left-0 flex items-center pl-3 text-muted-foreground pointer-events-none group-focus-within:text-primary">
                        <Shield class="w-4 h-4" />
                    </div>
                    <select
                        id="role"
                        v-model="form.role"
                        class="flex h-10 w-full rounded-md border border-input bg-background pl-10 pr-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        <option value="admin">Admin</option>
                        <option value="operator">Operator</option>
                    </select>
                </div>
                <InputError class="mt-1" :message="form.errors.role" />
            </div>

            <!-- Tipe (Only for operator) -->
            <div v-if="form.role === 'operator'" class="space-y-2">
                <Label for="tipe">Tipe Operator</Label>
                <select
                    id="tipe"
                    v-model="form.tipe"
                    class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                >
                    <option v-for="t in tipeOptions" :key="t.value" :value="t.value">{{ t.label }}</option>
                </select>
                <InputError class="mt-1" :message="form.errors.tipe" />
            </div>

            <!-- Password -->
            <div class="space-y-2">
                <Label for="password">Password</Label>
                <div class="relative group">
                    <div class="absolute inset-y-0 left-0 flex items-center pl-3 text-muted-foreground pointer-events-none group-focus-within:text-primary">
                        <Lock class="w-4 h-4" />
                    </div>
                    <Input
                        id="password"
                        type="password"
                        class="pl-10"
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
                <Label for="password_confirmation">Konfirmasi Password</Label>
                <div class="relative group">
                    <div class="absolute inset-y-0 left-0 flex items-center pl-3 text-muted-foreground pointer-events-none group-focus-within:text-primary">
                        <Lock class="w-4 h-4" />
                    </div>
                    <Input
                        id="password_confirmation"
                        type="password"
                        class="pl-10"
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
                class="w-full flex items-center justify-center pt-2"
                :disabled="form.processing"
            >
                <UserPlus class="w-4 h-4 mr-2" />
                Register
            </Button>

            <div class="text-center text-sm text-muted-foreground pt-2">
                Sudah punya akun? 
                <Link :href="route('login')" class="font-semibold text-primary hover:underline">
                    Sign In
                </Link>
            </div>
        </form>
    </GuestLayout>
</template>
