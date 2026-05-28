<script setup>
import { ref, onUnmounted, watch, nextTick } from 'vue';
import { Button } from '@/Components/ui/button';
import { Label } from '@/Components/ui/label';
import { X, Camera } from 'lucide-vue-next';
import Swal from 'sweetalert2';

const props = defineProps({
    isOpen: Boolean,
});

const emit = defineEmits(['scan', 'close']);

const scannerRef = ref(null);
const cameras = ref([]);
const selectedCameraId = ref(null);
let html5QrCode = null;

const startScanner = async () => {
    try {
        // Load library dynamically from CDN if not already loaded
        if (!window.Html5Qrcode) {
            const script = document.createElement('script');
            script.src = "https://unpkg.com/html5-qrcode";
            script.async = true;
            script.onload = () => {
                setTimeout(() => initScanner(), 500); // Give it a bit more time
            };
            document.head.appendChild(script);
        } else {
            initScanner();
        }
    } catch (err) {
        console.error("Failed to load scanner script:", err);
        Swal.fire('Error', 'Gagal memuat sistem scanner. Cek koneksi internet.', 'error');
    }
};

const initScanner = async () => {
    try {
        const qrReaderElem = document.getElementById("qr-reader");
        if (!qrReaderElem) return;

        html5QrCode = new window.Html5Qrcode("qr-reader");
        
        // Try to get available cameras
        const devices = await window.Html5Qrcode.getCameras();
        cameras.value = devices;
        
        if (devices && devices.length > 0) {
            // Default to back camera or first camera
            const backCamera = devices.find(d => d.label.toLowerCase().includes('back') || d.label.toLowerCase().includes('rear'));
            selectedCameraId.value = backCamera ? backCamera.id : devices[0].id;
            
            await startWithCamera(selectedCameraId.value);
        } else {
            throw new Error("No cameras found or permission denied");
        }
    } catch (err) {
        console.error("Unable to start scanning:", err);
        // ... (rest of error handling)
        let msg = "Pastikan Anda memberikan izin kamera dan menggunakan HTTPS.";
        if (window.location.hostname === '127.0.0.1' || window.location.hostname === 'localhost') {
            msg = "Kamera memerlukan akses HTTPS atau localhost/127.0.0.1. Pastikan izin kamera aktif.";
        }
        
        Swal.fire({
            icon: 'warning',
            title: 'Kamera Tidak Terdeteksi',
            text: msg,
            confirmButtonText: 'Tutup',
            confirmButtonColor: '#3b82f6'
        });
        emit('close');
    }
};

const startWithCamera = async (cameraId) => {
    try {
        if (html5QrCode && html5QrCode.isScanning) {
            await html5QrCode.stop();
        }

        const config = { 
            fps: 10, 
            qrbox: { width: 250, height: 250 },
            aspectRatio: 1.0
        };

        await html5QrCode.start(
            cameraId,
            config,
            (decodedText) => {
                stopScanner();
                emit('scan', decodedText);
            },
            (errorMessage) => {
                // ignore
            }
        );
    } catch (err) {
        console.error("Failed to switch camera:", err);
    }
};

const switchCamera = async (event) => {
    selectedCameraId.value = event.target.value;
    await startWithCamera(selectedCameraId.value);
};

const stopScanner = async () => {
    if (html5QrCode && html5QrCode.isScanning) {
        await html5QrCode.stop();
        await html5QrCode.clear();
    }
};

watch(() => props.isOpen, async (newVal) => {
    if (newVal) {
        // Wait for next tick to ensure the DOM element with id="qr-reader" is rendered
        await nextTick();
        // Add a small extra delay for safety in some browsers
        setTimeout(() => {
            startScanner();
        }, 100);
    } else {
        stopScanner();
    }
});

onUnmounted(() => {
    stopScanner();
});

const handleClose = async () => {
    await stopScanner();
    emit('close');
};
</script>

<template>
    <div v-if="isOpen" class="fixed inset-0 z-[100] flex items-center justify-center bg-black/80 backdrop-blur-sm p-4">
        <div class="bg-white w-full max-w-md rounded-3xl overflow-hidden shadow-2xl">
            <div class="p-6 border-b flex justify-between items-center">
                <div class="flex items-center gap-2">
                    <Camera class="w-5 h-5 text-blue-600" />
                    <h3 class="font-bold text-gray-900">Scan QR Code Driver</h3>
                </div>
                <button @click="handleClose" class="p-2 hover:bg-gray-100 rounded-full transition-colors">
                    <X class="w-5 h-5 text-gray-500" />
                </button>
            </div>
            
            <div class="p-4">
                <div id="qr-reader" class="rounded-2xl overflow-hidden border-4 border-gray-100 bg-gray-50 aspect-square"></div>
                
                <!-- Camera Selection -->
                <div v-if="cameras.length > 1" class="mt-4">
                    <Label class="text-xs font-bold text-gray-500 mb-2 block uppercase">Pilih Kamera</Label>
                    <select 
                        @change="switchCamera" 
                        v-model="selectedCameraId"
                        class="w-full rounded-xl border-gray-200 text-sm font-medium focus:ring-blue-500 focus:border-blue-500"
                    >
                        <option v-for="cam in cameras" :key="cam.id" :value="cam.id">
                            {{ cam.label || `Kamera ${cameras.indexOf(cam) + 1}` }}
                        </option>
                    </select>
                </div>

                <p class="text-center text-sm text-gray-500 mt-4">
                    Arahkan kamera ke QR Code driver untuk identifikasi otomatis.
                </p>
            </div>

            <div class="p-6  border-t">
                <Button @click="handleClose" variant="outline" class="w-full py-6 rounded-xl bg-red-500 text-white font-bold">
                    Batalkan
                </Button>
            </div>
        </div>
    </div>
</template>
