<script setup>
import { Link } from '@inertiajs/vue3';
import { Package } from 'lucide-vue-next';
</script>

<template>
    <div class="min-h-screen w-full flex flex-col lg:flex-row bg-background">
        <!-- Left Side - Branding (Hidden on Mobile) -->
        <div class="relative hidden flex-col items-center justify-center bg-zinc-900 p-10 text-white lg:flex lg:w-1/2 dark:border-r overflow-hidden text-center">
            <div class="absolute inset-0 bg-primary" />
            <!-- Animated background elements -->
            <div class="absolute inset-0 overflow-hidden opacity-30">
                <div class="absolute -top-24 -left-24 w-96 h-96 bg-white/40 rounded-full blur-3xl"></div>
                <div class="absolute bottom-0 right-0 w-full h-1/2 bg-gradient-to-t from-black/60 to-transparent"></div>
            </div>

            <div class="relative z-20 flex flex-col items-center gap-6">
                <!-- Logo Centered -->
                <div class="mb-4">
                    <img src="/images/logo.webp" alt="Ladang Lima Logo" class="h-16 w-auto object-contain" />
                </div>
                
                <!-- Content Centered -->
                <div class="max-w-md mt-4">
                    <h1 class="text-4xl font-bold tracking-tight text-white mb-4">Timbangan Digital</h1>
                    <p class="text-lg text-white/90 leading-relaxed font-medium">
                        Sistem penimbangan terintegrasi cerdas. Mengelola data secara real-time dengan presisi tinggi melalui teknologi IoT terkini.
                    </p>
                    <p class="text-sm font-semibold text-white/60 uppercase tracking-widest mt-8">Platform Manajemen Operasional</p>
                </div>
            </div>
        </div>

        <!-- Right Side - Login Form -->
        <div class="w-full lg:w-1/2 flex flex-col justify-center items-center p-6 sm:p-8 lg:p-12">
            <div class="w-full max-w-sm">
                <!-- Form Header & Logo -->
                <div class="flex flex-col items-center mb-10">
                    <div class="mb-4 inline-flex">
                        <img src="/images/logo.webp" alt="Ladang Lima Logo" class="h-16 w-auto object-contain" />
                    </div>
                    <!-- Judul hanya muncul di mobile karena desktop sudah ada di sebelah kiri -->
                    <h1 class="text-2xl font-bold tracking-tight text-foreground lg:hidden">Timbangan Digital</h1>
                </div>

                <slot />

                <!-- Footer -->
                <div class="flex flex-col items-center pt-8 mt-12 border-t border-border">
                    <p class="text-xs font-bold text-muted-foreground uppercase tracking-widest">© {{ new Date().getFullYear() }} Ladang Lima</p>
                </div>
            </div>
        </div>
    </div>
</template>
