<script setup>
import { ref, onMounted, onUnmounted } from 'vue';

defineProps({
    title: { type: String, default: '' },
});

// Kiosk selalu tema terang (fixed) — cocok untuk konversi ke HMI fisik.
const now = ref(new Date());
let timer = null;
onMounted(() => { timer = setInterval(() => (now.value = new Date()), 1000); });
onUnmounted(() => timer && clearInterval(timer));

const fmtTime = (d) => d.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
const fmtDate = (d) => d.toLocaleDateString('id-ID', { day: '2-digit', month: '2-digit', year: 'numeric' });
</script>

<template>
    <div class="kiosk-root flex min-h-screen w-full flex-col bg-slate-100 text-slate-900">
        <!-- Header -->
        <header class="relative flex items-center justify-between bg-gradient-to-r from-purple-200 via-pink-100 to-emerald-200 px-6 py-3">
            <img src="/images/logo.webp" alt="Ladang Lima" class="h-10 w-auto">
        </header>

        <!-- Konten HMI -->
        <main class="flex-1 overflow-hidden p-3">
            <slot />
        </main>

        <!-- Footer -->
        <footer class="flex items-center gap-3 border-t border-slate-300 bg-white px-6 py-2">
            <span class="font-display text-2xl font-extrabold text-red-600">4PRO</span>
            <span class="hidden text-[11px] font-bold tracking-[0.3em] text-slate-500 md:block">
                PROFESSIONAL&nbsp;&nbsp;PROACTIVE&nbsp;&nbsp;PRODUCTIVE&nbsp;&nbsp;PROGRESSIVE
            </span>
            <span class="ml-auto font-mono text-xs text-slate-500">{{ fmtTime(now) }} · {{ fmtDate(now) }}</span>
        </footer>
    </div>
</template>
