import { reactive } from 'vue';

/**
 * Store toast global (singleton). Dipakai lintas halaman + layout.
 *
 * pushToast({ title, text, meta, variant, duration }) -> id
 *   variant: 'success' | 'info' | 'warning' | 'error'
 *   meta:    array baris detail { label, value } untuk tampilan expand
 *   duration: ms auto-dismiss (0 = tidak auto-dismiss)
 *
 * Timer & pause-on-hover ditangani di komponen ToastStack.vue.
 */
const state = reactive({
    toasts: [],
});

let seq = 0;

export function pushToast({
    title,
    text = '',
    meta = [],
    variant = 'info',
    duration = 5000,
} = {}) {
    const id = ++seq;
    state.toasts.unshift({ id, title, text, meta, variant, duration });
    // Batasi tumpukan agar tidak membanjiri layar saat burst data timbangan.
    if (state.toasts.length > 6) state.toasts.splice(6);
    return id;
}

export function dismissToast(id) {
    const i = state.toasts.findIndex((t) => t.id === id);
    if (i !== -1) state.toasts.splice(i, 1);
}

export function useToastStore() {
    return { state, pushToast, dismissToast };
}
