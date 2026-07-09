<script setup>
import { computed } from 'vue';

const props = defineProps({
    summary: { type: Array, default: () => [] }, // { label, value }
    weighings: { type: Array, default: () => [] }, // baris HmiWeighing
});

const fmt = (v) =>
    Number(v ?? 0).toLocaleString('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

const unitLabel = (u) => (u === 'gr' ? 'gr' : 'kg');

// Tampilkan menaik (Timbangan ke 1..n) seperti mockup.
const ordered = computed(() =>
    [...props.weighings].sort((a, b) => (a.timbangan_ke ?? 0) - (b.timbangan_ke ?? 0)),
);
</script>

<template>
    <div class="flex h-full flex-col border border-gray-400 bg-white p-4">
        <div class="space-y-1 border-b border-gray-200 pb-3">
            <p v-for="(s, i) in summary" :key="i" class="text-lg text-slate-800">
                <span class="font-semibold">{{ s.label }} :</span> {{ s.value || '—' }}
            </p>
        </div>

        <div class="mt-3 flex-1 space-y-1 overflow-auto">
            <p v-if="!ordered.length" class="text-sm text-slate-400">Belum ada timbangan hari ini.</p>
            <p v-for="w in ordered" :key="w.id ?? w.uuid" class="text-lg text-slate-800">
                <span class="font-semibold">Timbangan ke {{ w.timbangan_ke }} :</span>
                {{ fmt(w.berat) }} {{ unitLabel(w.unit) }}
            </p>
        </div>
    </div>
</template>
