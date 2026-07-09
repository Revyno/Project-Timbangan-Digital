<script setup>
import { computed } from 'vue';

const props = defineProps({
    title: { type: String, default: '' },
    headerRight: { type: String, default: '' },
    // items: array of strings, atau objek { nama, target? }
    items: { type: Array, default: () => [] },
    modelValue: { type: [String, Number, null], default: null },
    columns: { type: Number, default: 3 },
});

const emit = defineEmits(['update:modelValue', 'select']);

const labelOf = (it) => (typeof it === 'string' ? it : (it.nama ?? it.label ?? ''));
const valueOf = (it) => (typeof it === 'string' ? it : (it.nama ?? it.value ?? ''));

const gridStyle = computed(() => ({
    gridTemplateColumns: `repeat(${props.columns}, minmax(0, 1fr))`,
}));

const choose = (it) => {
    emit('update:modelValue', valueOf(it));
    emit('select', it);
};
</script>

<template>
    <div class="flex h-full flex-col border border-gray-400 bg-white">
        <div v-if="title || headerRight" class="grid grid-cols-3 gap-2 border-b border-gray-300 p-2">
            <div class="flex items-center justify-center border border-gray-400 bg-gray-50 py-2 text-sm font-bold">
                {{ title }}
            </div>
            <div class="col-span-2 flex items-center justify-center border border-gray-400 bg-white py-2 text-sm font-bold uppercase">
                {{ headerRight }}
            </div>
        </div>

        <div class="grid flex-1 content-start gap-2 overflow-auto p-2" :style="gridStyle">
            <button
                v-for="(it, i) in items"
                :key="i"
                type="button"
                @click="choose(it)"
                :class="[
                    'rounded border px-2 py-3 text-center text-sm transition-colors',
                    valueOf(it) === modelValue
                        ? 'border-gray-600 bg-yellow-300 font-bold shadow-inner'
                        : 'border-gray-400 bg-white font-medium hover:bg-gray-100',
                ]"
            >
                {{ labelOf(it) }}
            </button>
        </div>
    </div>
</template>
