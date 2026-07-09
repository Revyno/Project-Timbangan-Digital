import { ref, onMounted, onUnmounted } from 'vue';

/**
 * Berat "live" untuk HMI. Mendengarkan channel LAN-only `scale.{deviceKey}`
 * (di-feed oleh POST /hmi-display/{menu}/live -> event ScaleReading). Berat live TIDAK
 * disimpan ke DB; hanya angka di layar yang bergerak.
 *
 * Menyediakan juga simulator sisi-klien (startSim/stopSim) agar halaman bisa
 * didemokan tanpa perangkat timbangan nyata / tanpa Reverb berjalan.
 */
export function useLiveWeight(deviceKey) {
    const weight = ref(0);
    const connected = ref(false);
    const simulating = ref(false);

    let channelName = null;
    let simTimer = null;

    const handler = (e) => {
        if (e && typeof e.weight !== 'undefined') weight.value = Number(e.weight);
    };

    onMounted(() => {
        if (window.Echo && deviceKey) {
            channelName = 'scale.' + deviceKey;
            try {
                window.Echo.channel(channelName).listen('.ScaleReading', handler);
                connected.value = true;
            } catch (_) {
                connected.value = false;
            }
        }
    });

    onUnmounted(() => {
        stopSim();
        if (window.Echo && channelName) {
            try { window.Echo.leave(channelName); } catch (_) { /* noop */ }
        }
    });

    function startSim(target) {
        stopSim();
        simulating.value = true;
        const base = Number(target) > 0 ? Number(target) : 50;
        simTimer = setInterval(() => {
            const noise = (Math.random() - 0.5) * base * 0.02; // ±1%
            weight.value = Math.max(0, Number((base + noise).toFixed(2)));
        }, 150);
    }

    function stopSim() {
        if (simTimer) { clearInterval(simTimer); simTimer = null; }
        simulating.value = false;
    }

    function setWeight(v) {
        weight.value = Number(v) || 0;
    }

    return { weight, connected, simulating, startSim, stopSim, setWeight };
}
