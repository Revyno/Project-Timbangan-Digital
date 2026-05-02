<script setup>
import Checkbox from '@/Components/Checkbox.vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { LogIn, Mail, Lock } from 'lucide-vue-next';

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

        <div v-if="status" class="mb-4 text-sm font-medium text-green-600 bg-green-50 p-4 rounded-2xl border border-green-100">
            {{ status }}
        </div>

        <form @submit.prevent="submit" class="space-y-6">
            <div class="space-y-2">
                <Label for="email" class="text-xs font-bold text-gray-500 uppercase ml-1">Email Address</Label>
                <div class="relative group">
                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-gray-400 group-focus-within:text-blue-600 transition-colors">
                        <Mail class="w-5 h-5" />
                    </div>
                    <Input
                        id="email"
                        type="email"
                        class="pl-12 bg-gray-50/50 border-gray-100 focus:bg-white focus:ring-blue-600/20 py-6 rounded-2xl transition-all"
                        v-model="form.email"
                        required
                        autofocus
                        autocomplete="username"
                        placeholder="name@company.com"
                    />
                </div>
                <InputError class="mt-2" :message="form.errors.email" />
            </div>

            <div class="space-y-2">
                <div class="flex items-center justify-between ml-1">
                    <Label for="password" class="text-xs font-bold text-gray-500 uppercase">Password</Label>
                    <Link
                        v-if="canResetPassword"
                        :href="route('password.request')"
                        class="text-xs font-bold text-blue-600 hover:text-blue-700 transition-colors"
                    >
                        Forgot?
                    </Link>
                </div>
                <div class="relative group">
                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-gray-400 group-focus-within:text-blue-600 transition-colors">
                        <Lock class="w-5 h-5" />
                    </div>
                    <Input
                        id="password"
                        type="password"
                        class="pl-12 bg-gray-50/50 border-gray-100 focus:bg-white focus:ring-blue-600/20 py-6 rounded-2xl transition-all"
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
                <label for="remember" class="ms-3 text-sm font-medium text-gray-600 cursor-pointer select-none">Remember this device</label>
            </div>

            <Button
                class="w-full bg-blue-600 hover:bg-blue-700 text-white font-black py-7 rounded-2xl shadow-xl shadow-blue-100 transition-all text-lg group"
                :class="{ 'opacity-25': form.processing }"
                :disabled="form.processing"
            >
                <LogIn class="w-5 h-5 mr-2 group-hover:translate-x-1 transition-transform" />
                Sign In
            </Button>
        </form>
    </GuestLayout>
</template>
