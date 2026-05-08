<script setup>
import Checkbox from '@/Components/Checkbox.vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { LogIn, Mail, Lock, Loader2 } from 'lucide-vue-next';

defineProps({
    canResetPassword: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Log in" />

        <div v-if="status" class="p-4 mb-4 text-sm font-medium text-green-600 border border-green-100 bg-green-50 rounded-2xl">
            {{ status }}
        </div>

        <form @submit.prevent="submit" class="space-y-6">
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
                        autofocus
                        autocomplete="username"
                        placeholder="name@gmail.com"
                    />
                </div>
                <InputError class="mt-2" :message="form.errors.email" />
            </div>

            <div class="space-y-2">
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
                        autocomplete="current-password"
                        placeholder="••••••••"
                    />
                </div>
                <InputError class="mt-2" :message="form.errors.password" />
            </div>

            <div class="flex items-center ml-1">
                <Checkbox name="remember" v-model:checked="form.remember" id="remember" />
                <label for="remember" class="text-sm font-medium text-gray-600 cursor-pointer select-none ms-3">Remember this device</label>
            </div>

            <Button
                class="w-full text-lg font-black text-white transition-all bg-blue-600 shadow-xl hover:bg-blue-700 py-7 rounded-2xl shadow-blue-100 group"
                :class="{ 'opacity-70 cursor-not-allowed': form.processing }"
                :disabled="form.processing"
            >
                <Loader2 v-if="form.processing" class="w-5 h-5 mr-2 animate-spin" />
                <LogIn v-else class="w-5 h-5 mr-2 transition-transform group-hover:translate-x-1" />
                {{ form.processing ? 'Memuat...' : 'Sign In' }}
            </Button>

            <div class="text-center">
                <Link :href="route('driver.register')" class="block text-sm font-medium text-gray-500 hover:text-blue-600 transition-colors">
                    Daftar sebagai <span class="font-bold text-blue-600">Sopir (QR Code)</span>
                </Link>
            </div>
        </form>
    </GuestLayout>
</template>
