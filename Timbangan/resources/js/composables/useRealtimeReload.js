import { ref, onMounted, onUnmounted } from 'vue';
import { router } from '@inertiajs/vue3';

/**
 * Real-time reload untuk data Inertia.
 *
 * - Subscribe ke channel WebSocket (Laravel Echo / Reverb) dan reload
 *   props tertentu setiap event `.WeightReceived` masuk.
 * - Reload di-throttle supaya burst data dari timbangan tidak
 *   menembak server berkali-kali.
 * - Fallback polling: jika WebSocket tidak tersedia / terputus,
 *   data tetap di-refresh berkala lewat request Inertia biasa
 *   sehingga data dari REST API tetap muncul real-time.
 *
 * @param {Object}   options
 * @param {string}   options.channel      Nama channel Echo (default: 'iot-weights').
 * @param {string[]} options.only         Props Inertia yang di-reload.
 * @param {Function} options.onEvent      Callback per event; return false untuk skip reload.
 * @param {number}   options.pollInterval Interval fallback polling (ms).
 * @param {number}   options.throttleMs   Jeda minimal antar reload (ms).
 */
export function useRealtimeReload({
    channel = 'iot-weights',
    only = [],
    onEvent = null,
    pollInterval = 15000,
    throttleMs = 1000,
} = {}) {
    const connected = ref(false);

    let reloadTimer = null;
    let pollTimer = null;
    let stateHandler = null;

    const queueReload = () => {
        if (reloadTimer) return;
        reloadTimer = setTimeout(() => {
            reloadTimer = null;
            router.reload({ only, preserveScroll: true });
        }, throttleMs);
    };

    onMounted(() => {
        if (window.Echo) {
            window.Echo.channel(channel)
                .listen('.WeightReceived', (e) => {
                    if (onEvent && onEvent(e) === false) return;
                    queueReload();
                });

            const conn = window.Echo.connector?.pusher?.connection;
            if (conn) {
                connected.value = conn.state === 'connected';
                stateHandler = ({ current }) => {
                    connected.value = current === 'connected';
                };
                conn.bind('state_change', stateHandler);
            }
        }

        // Fallback polling saat WebSocket mati — data REST API tetap masuk.
        pollTimer = setInterval(() => {
            if (!connected.value) queueReload();
        }, pollInterval);
    });

    onUnmounted(() => {
        if (window.Echo) {
            window.Echo.leave(channel);
            const conn = window.Echo.connector?.pusher?.connection;
            if (conn && stateHandler) conn.unbind('state_change', stateHandler);
        }
        if (reloadTimer) clearTimeout(reloadTimer);
        if (pollTimer) clearInterval(pollTimer);
    });

    return { connected, queueReload };
}
