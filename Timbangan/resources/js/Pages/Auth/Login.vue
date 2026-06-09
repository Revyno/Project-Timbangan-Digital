<script setup>
import Checkbox from '@/Components/Checkbox.vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { LogIn, Mail, Lock, Loader2, Eye, EyeOff } from 'lucide-vue-next';
import { ref } from 'vue';

defineProps({
    canResetPassword: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const showPassword = ref(false);

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

        <!-- Status Alert -->
        <div v-if="status" class="mb-6 p-4 rounded-lg bg-emerald-500/10 border border-emerald-500/20 text-emerald-600 text-sm font-medium">
            {{ status }}
        </div>

        <form @submit.prevent="submit" class="space-y-5">
            <!-- Email Field -->
            <div class="space-y-2">
                <label for="email" class="text-xs font-semibold text-muted-foreground uppercase tracking-wider">Email Address</label>
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
                        autofocus
                        autocomplete="username"
                        placeholder="nama@gmail.com"
                    />
                </div>
                <InputError v-if="form.errors.email" class="text-xs text-destructive mt-1" :message="form.errors.email" />
            </div>

            <!-- Password Field -->
            <div class="space-y-2">
                <label for="password" class="text-xs font-semibold text-muted-foreground uppercase tracking-wider">Password</label>
                <div class="relative group">
                    <div class="absolute inset-y-0 left-0 flex items-center pl-3 text-muted-foreground pointer-events-none group-focus-within:text-primary">
                        <Lock class="w-4 h-4" />
                    </div>
                    <Input
                        id="password"
                        :type="showPassword ? 'text' : 'password'"
                        class="pl-10 pr-10"
                        v-model="form.password"
                        required
                        autocomplete="current-password"
                        placeholder="••••••••"
                    />
                    <button
                        type="button"
                        @click="showPassword = !showPassword"
                        class="absolute inset-y-0 right-0 flex items-center pr-3 text-muted-foreground hover:text-foreground transition-colors"
                    >
                        <Eye v-if="!showPassword" class="w-4 h-4" />
                        <EyeOff v-else class="w-4 h-4" />
                    </button>
                </div>
                <InputError v-if="form.errors.password" class="text-xs text-destructive mt-1" :message="form.errors.password" />
            </div>

            <!-- Remember Checkbox -->
            <div class="flex items-center">
                <Checkbox name="remember" v-model:checked="form.remember" id="remember" />
                <label for="remember" class="text-sm font-medium text-muted-foreground cursor-pointer select-none ms-2">Ingat perangkat ini</label>
            </div>

            <!-- Submit Button -->
            <Button
                type="submit"
                class="w-full flex items-center justify-center"
                :disabled="form.processing"
            >
                <Loader2 v-if="form.processing" class="w-4 h-4 mr-2 animate-spin" />
                <LogIn v-else class="w-4 h-4 mr-2" />
                <span>{{ form.processing ? 'Memuat...' : 'Masuk' }}</span>
            </Button>

            <!-- Divider -->
            <div class="relative py-2">
                <div class="absolute inset-0 flex items-center">
                    <div class="w-full border-t border-border"></div>
                </div>
                <div class="relative flex justify-center text-xs uppercase">
                    <span class="bg-background px-2 text-muted-foreground">atau</span>
                </div>
            </div>

            <!-- Register Link -->
            <Link :href="route('driver.register')" class="block w-full py-2.5 text-center text-sm font-semibold border border-input rounded-md hover:bg-accent hover:text-accent-foreground transition-all duration-200">
                Daftar sebagai Sopir (QR Code)
            </Link>
        </form>
    </GuestLayout>
</template>
