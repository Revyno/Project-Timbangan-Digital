<script setup>
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-[#f8fafc] selection:bg-blue-100">
        <div class="w-full sm:max-w-md mt-6 px-10 py-12 bg-white shadow-[0_20px_50px_rgba(8,_112,_184,_0.05)] overflow-hidden sm:rounded-[2.5rem] border border-gray-100">
            <div class="flex flex-col items-center mb-10">
                <div class="w-20 h-20 bg-blue-600 rounded-3xl rotate-12 flex items-center justify-center shadow-xl shadow-blue-200 mb-6 group hover:rotate-0 transition-all duration-500">
                    <span class="text-3xl font-black text-white -rotate-12 group-hover:rotate-0 transition-all duration-500">L5</span>
                </div>
                <h1 class="text-3xl font-black text-gray-900 tracking-tight">IoT Weighing</h1>
                <p class="text-gray-400 font-medium mt-1">Industrial Scale Integration</p>
            </div>
            
            <slot />

            <div class="mt-10 pt-8 border-t border-gray-50 flex flex-col items-center">
                <p class="text-[10px] font-bold text-gray-300 uppercase tracking-[0.2em]">© 2024 Ladang Lima Pasuruan</p>
            </div>
        </div>
    </div>
</template>
