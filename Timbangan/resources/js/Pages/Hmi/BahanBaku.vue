<script setup>
import { ref, computed } from 'vue';
import { Head } from '@inertiajs/vue3';
import axios from 'axios';
import Swal from 'sweetalert2';
import KioskLayout from '@/Layouts/KioskLayout.vue';
import SessionHeader from '@/Components/Hmi/SessionHeader.vue';
import SelectionGrid from '@/Components/Hmi/SelectionGrid.vue';
import WeightPanel from '@/Components/Hmi/WeightPanel.vue';
import WeighingList from '@/Components/Hmi/WeighingList.vue';
import { useLiveWeight } from '@/composables/useLiveWeight';

const props = defineProps({
    menu: { type: String, default: 'bahan-baku' },
    materials: { type: Array, default: () => [] },
    context: { type: Object, default: () => ({}) },
    history: { type: Array, default: () => [] },
});

const selectedMaterial = ref('');
const kodeBatch = ref('');
const unit = ref('kg');
const weighings = ref([...props.history]);
const printing = ref(false);

const { weight, simulating, startSim, stopSim, setWeight } = useLiveWeight(props.context.deviceKey);

const nextKe = computed(() => weighings.value.length + 1);

const tanggalLabel = computed(() => {
    const d = props.context.tanggal ? new Date(props.context.tanggal) : new Date();
    return d.toLocaleDateString('id-ID', { day: '2-digit', month: 'long', year: 'numeric' });
});

const headerRows = computed(() => [
    { label: 'Nama Operator', value: props.context.operator },
    { label: 'Tanggal', value: tanggalLabel.value },
]);

const summary = computed(() => [
    { label: 'Nama Operator', value: props.context.operator },
    { label: 'Tanggal', value: tanggalLabel.value },
    { label: 'Bahan Baku', value: selectedMaterial.value },
    { label: 'Kode Batch Number', value: kodeBatch.value },
]);

const toggleSim = () => (simulating.value ? stopSim() : startSim(0));
const toggleUnit = () => (unit.value = unit.value === 'kg' ? 'gr' : 'kg');

const toast = (icon, title) =>
    Swal.fire({ toast: true, position: 'top-end', icon, title, showConfirmButton: false, timer: 2500 });

const print = async () => {
    if (!selectedMaterial.value) {
        toast('warning', 'Pilih bahan baku dulu.');
        return;
    }
    printing.value = true;
    try {
        const { data } = await axios.post(route('kiosk.print', { menu: props.menu }), {
            nama_item: selectedMaterial.value,
            berat: Number(weight.value),
            unit: unit.value,
            kode_batch: kodeBatch.value || null,
            timbangan_ke: nextKe.value,
            device: props.context.deviceKey,
        });
        weighings.value.push(data.weighing);
        toast('success', `Tersimpan: ${selectedMaterial.value} ${Number(weight.value).toFixed(2)} ${unit.value}`);
    } catch (e) {
        toast('error', e.response?.data?.message || 'Gagal menyimpan timbangan.');
    } finally {
        printing.value = false;
    }
};
</script>

<template>
    <Head title="HMI — Bahan Baku" />

    <KioskLayout title="Incoming Bahan Baku">
        <div class="grid h-full grid-cols-1 gap-3 lg:grid-cols-2 lg:grid-rows-2">
            <!-- Kiri-atas: identitas sesi -->
            <SessionHeader title="Bahan Baku" :rows="headerRows">
                <div class="grid grid-cols-3 gap-2">
                    <div class="flex items-center justify-center border border-gray-400 py-2 text-center text-[11px] font-bold uppercase">
                        Kode Batch
                    </div>
                    <input
                        v-model="kodeBatch"
                        class="col-span-2 border border-gray-400 px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-emerald-500"
                        placeholder="mis. PUTR-120924"
                    />
                </div>

                <div class="mt-auto flex items-center justify-between gap-2 pt-2">
                    <button
                        type="button"
                        @click="toggleSim"
                        class="rounded border border-gray-400 px-3 py-1.5 text-xs font-semibold hover:bg-gray-100"
                    >
                        {{ simulating ? '■ Stop Simulasi' : '▶ Simulasi Berat' }}
                    </button>
                    <span class="text-xs text-slate-400">Berat live tidak disimpan — hanya PRINT.</span>
                </div>
            </SessionHeader>

            <!-- Kanan-atas: pilih bahan -->
            <SelectionGrid
                title="Bahan Baku"
                :header-right="context.operator"
                :items="materials"
                v-model="selectedMaterial"
                :columns="3"
            />

            <!-- Kiri-bawah: panel timbang -->
            <WeightPanel
                :item="selectedMaterial"
                :kode-batch="kodeBatch"
                :weight="Number(weight)"
                :target="null"
                :unit="unit"
                :printing="printing"
                :timbangan-ke="nextKe"
                @print="print"
                @tare="setWeight(0)"
                @zero="setWeight(0)"
                @toggle-unit="toggleUnit"
            />

            <!-- Kanan-bawah: ringkasan + daftar -->
            <WeighingList :summary="summary" :weighings="weighings" />
        </div>
    </KioskLayout>
</template>



