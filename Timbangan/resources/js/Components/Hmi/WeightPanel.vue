<script setup>
import { computed } from 'vue';

const props = defineProps({
    item: { type: String, default: '' },
    kodeBatch: { type: String, default: '' },
    weight: { type: Number, default: 0 },
    target: { type: [Number, null], default: null },
    unit: { type: String, default: 'kg' },
    printing: { type: Boolean, default: false },
    canPrint: { type: Boolean, default: true },
    timbanganKe: { type: [Number, null], default: null },
});

const emit = defineEmits(['print', 'tare', 'zero', 'toggle-unit']);

const unitLabel = computed(() => (props.unit === 'gr' ? 'Gr' : 'Kg'));

const fmt = (v) =>
    Number(v ?? 0).toLocaleString('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

// Warna kotak: hijau bila dalam toleransi ±2% target (atau tak ada target),
// amber bila di luar. Memberi feedback cepat ke operator.
const boxColor = computed(() => {
    if (props.target === null || props.target <= 0) return 'bg-[#79b348]';
    const diff = Math.abs(props.weight - props.target);
    return diff <= props.target * 0.02 ? 'bg-[#79b348]' : 'bg-amber-500';
});
</script>

<template>
    <div class="flex h-full flex-col border border-gray-400 bg-white">
        <div class="border-b border-gray-300 py-2 text-center">
            <div class="text-lg font-bold uppercase leading-tight">{{ item || '—' }}</div>
            <div class="text-sm text-gray-600">Kode Batch Number : {{ kodeBatch || '—' }}</div>
        </div>

        <div class="flex flex-1 items-center gap-4 p-4">
            <div :class="['flex min-h-[150px] flex-1 items-center justify-center rounded text-white transition-colors', boxColor]">
                <span class="font-display text-6xl font-semibold tabular-nums md:text-7xl">{{ fmt(weight) }}</span>
            </div>

            <div class="w-14 text-center font-display text-4xl font-medium">{{ unitLabel }}</div>

            <div class="flex w-44 flex-col gap-3">
                <div class="flex items-stretch gap-1">
                    <span class="flex-1 border border-gray-400 px-2 py-1 text-center text-sm">
                        {{ target !== null ? fmt(target) + ' ' + unitLabel : '—' }}
                    </span>
                    <span class="border border-gray-400 bg-gray-50 px-2 py-1 text-xs font-semibold">TARGET</span>
                </div>

                <button
                    type="button"
                    :disabled="!canPrint || printing"
                    @click="emit('print')"
                    class="rounded border-2 border-gray-500 py-5 text-lg font-bold tracking-wide transition-colors hover:bg-emerald-50 disabled:cursor-not-allowed disabled:opacity-40"
                >
                    {{ printing ? 'MENYIMPAN…' : 'SAVE' }}
                </button>

                <p v-if="timbanganKe" class="text-center text-xs font-semibold text-slate-500">
                    Timbangan ke {{ timbanganKe }}
                </p>
            </div>
        </div>

        <div class="grid grid-cols-3 gap-3 border-t border-gray-300 p-4">
            <button type="button" @click="emit('tare')" class="rounded border border-gray-400 py-3 font-semibold hover:bg-gray-100">Tare</button>
            <button type="button" @click="emit('zero')" class="rounded border border-gray-400 py-3 font-semibold hover:bg-gray-100">Zero</button>
            <button type="button" @click="emit('toggle-unit')" class="rounded border border-gray-400 py-3 font-semibold hover:bg-gray-100">Unit</button>
        </div>
    </div>
</template>
