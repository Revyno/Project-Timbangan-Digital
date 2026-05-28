<script setup>
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-[#f8fafc] selection:bg-blue-100">
        <div class="w-full sm:max-w-md mt-6 px-10 py-12 bg-white shadow-[0_20px_50px_rgba(8,_112,_184,_0.05)] overflow-hidden sm:rounded-[2.5rem] border border-gray-100">
            <div class="flex flex-col items-center mb-10">
                <img src="https://career.ladanglima.com/images/logo.webp" alt="Logo" class="w-20 h-20 mb-4" />
                <h1 class="text-3xl font-black tracking-tight text-gray-900">Timbangan Digital</h1>
            </div>

            <slot />

            <div class="flex flex-col items-center pt-8 mt-10 border-t border-gray-50">
                <p class="text-[10px] font-bold text-gray-300 uppercase tracking-[0.2em]">Copyright @ {{ new Date().getFullYear() }} Ladang Lima</p>
            </div>
        </div>
    </div>
</template>
