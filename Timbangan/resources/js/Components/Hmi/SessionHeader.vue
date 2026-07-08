<script setup>
defineProps({
    brandLabel: { type: String, default: 'LADANG SEHAT INDONESIA' },
    title: { type: String, default: '' },
    rows: { type: Array, default: () => [] }, // { label, value }
});
</script>

<template>
    <div class="flex h-full flex-col gap-2 border border-gray-400 bg-white p-2">
        <div class="grid grid-cols-3 gap-2">
            <div class="flex items-center justify-center border border-gray-400 bg-pink-100 py-2 text-center text-[11px] font-bold leading-tight">
                {{ brandLabel }}
            </div>
            <div class="col-span-2 flex items-center justify-center border border-gray-400 bg-emerald-100 py-2 text-center text-sm font-bold uppercase">
                {{ title }}
            </div>
        </div>

        <div v-for="(r, i) in rows" :key="i" class="grid grid-cols-3 gap-2">
            <div class="flex items-center justify-center border border-gray-400 py-2 text-center text-[11px] font-bold uppercase">
                {{ r.label }}
            </div>
            <div class="col-span-2 flex items-center justify-center border border-gray-400 py-2 text-center text-sm">
                {{ r.value || '—' }}
            </div>
        </div>

        <!-- Slot untuk baris input (mis. Kode Batch / Nama Karu) -->
        <slot />
    </div>
</template>
