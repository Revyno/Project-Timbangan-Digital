<script setup>
import { onUnmounted, reactive } from 'vue';
import { CheckCircle2, Info, AlertTriangle, XCircle, X, ChevronDown } from 'lucide-vue-next';
import { useToastStore } from '@/composables/useToast.js';

// Expandable notification toast dengan timer bar + stack.
// UX ref: Dribbble "Expandable Notification Toast with timer for SaaS Dashboard".

const { state, dismissToast } = useToastStore();

const variantMap = {
    success: { icon: CheckCircle2, ring: 'text-emerald-500', bar: 'bg-emerald-500' },
    info:    { icon: Info,         ring: 'text-blue-500',    bar: 'bg-blue-500' },
    warning: { icon: AlertTriangle,ring: 'text-amber-500',   bar: 'bg-amber-500' },
    error:   { icon: XCircle,      ring: 'text-red-500',     bar: 'bg-red-500' },
};
const meta = (v) => variantMap[v] || variantMap.info;

// Per-toast timer state. reactive -> timer bar & expand ikut re-render.
// Key by id -> { remaining, total, raf, expanded, paused, last }.
const timers = reactive(new Map());

function ensureTimer(t) {
    if (timers.has(t.id) || !t.duration) return timers.get(t.id);
    timers.set(t.id, { total: t.duration, remaining: t.duration, expanded: false, paused: false, last: null, raf: null });
    tick(t.id);
    return timers.get(t.id);
}

function tick(id) {
    const rec = timers.get(id);
    if (!rec) return;
    const step = (now) => {
        const cur = timers.get(id);
        if (!cur) return;
        if (cur.last == null) cur.last = now;
        const dt = now - cur.last;
        cur.last = now;
        if (!cur.paused && !cur.expanded) {
            cur.remaining -= dt;
            if (cur.remaining <= 0) { close(id); return; }
        }
        cur.raf = requestAnimationFrame(step);
    };
    rec.raf = requestAnimationFrame(step);
}

// pct dibaca via reactive re-render — pakai getter yang dipanggil dalam template.
function progress(id, duration) {
    const rec = timers.get(id);
    if (!rec || !duration) return 0;
    return Math.max(0, Math.min(100, (rec.remaining / rec.total) * 100));
}

function isExpanded(id) { return !!timers.get(id)?.expanded; }
function toggleExpand(id) { const r = timers.get(id); if (r) r.expanded = !r.expanded; }
function pause(id, v) { const r = timers.get(id); if (r) { r.paused = v; r.last = null; } }

function close(id) {
    const r = timers.get(id);
    if (r?.raf) cancelAnimationFrame(r.raf);
    timers.delete(id);
    dismissToast(id);
}

onUnmounted(() => { timers.forEach((r) => r.raf && cancelAnimationFrame(r.raf)); timers.clear(); });
</script>

<template>
    <div class="fixed top-4 right-4 z-[100] flex flex-col gap-2 w-[min(92vw,22rem)] pointer-events-none">
        <TransitionGroup name="toast">
            <div
                v-for="t in state.toasts"
                :key="t.id"
                :ref="() => ensureTimer(t)"
                class="pointer-events-auto overflow-hidden rounded-xl border border-slate-200/80 dark:border-slate-700 bg-white/95 dark:bg-slate-800/95 backdrop-blur shadow-lg shadow-slate-900/10"
                @mouseenter="pause(t.id, true)"
                @mouseleave="pause(t.id, false)"
            >
                <div class="flex items-start gap-3 p-3">
                    <component :is="meta(t.variant).icon" class="w-5 h-5 mt-0.5 shrink-0" :class="meta(t.variant).ring" />

                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-semibold text-slate-800 dark:text-slate-100 leading-snug">{{ t.title }}</p>
                        <p v-if="t.text" class="text-xs text-slate-500 dark:text-slate-400 mt-0.5 truncate">{{ t.text }}</p>

                        <!-- Detail expandable -->
                        <div
                            v-if="t.meta && t.meta.length"
                            class="grid transition-all duration-300"
                            :style="{ gridTemplateRows: isExpanded(t.id) ? '1fr' : '0fr' }"
                        >
                            <div class="overflow-hidden">
                                <dl class="mt-2 pt-2 border-t border-slate-100 dark:border-slate-700 space-y-1">
                                    <div v-for="(m, i) in t.meta" :key="i" class="flex justify-between gap-3 text-xs">
                                        <dt class="text-slate-400">{{ m.label }}</dt>
                                        <dd class="font-medium text-slate-700 dark:text-slate-200 truncate">{{ m.value }}</dd>
                                    </div>
                                </dl>
                            </div>
                        </div>

                        <button
                            v-if="t.meta && t.meta.length"
                            @click="toggleExpand(t.id)"
                            class="mt-1.5 inline-flex items-center gap-1 text-[11px] font-medium text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition-colors"
                        >
                            {{ isExpanded(t.id) ? 'Sembunyikan' : 'Detail' }}
                            <ChevronDown class="w-3 h-3 transition-transform" :class="isExpanded(t.id) && 'rotate-180'" />
                        </button>
                    </div>

                    <button @click="close(t.id)" class="shrink-0 text-slate-300 hover:text-slate-500 dark:hover:text-slate-200 transition-colors">
                        <X class="w-4 h-4" />
                    </button>
                </div>

                <!-- Timer bar -->
                <div v-if="t.duration" class="h-1 bg-slate-100 dark:bg-slate-700">
                    <div class="h-full transition-none" :class="meta(t.variant).bar" :style="{ width: progress(t.id, t.duration) + '%' }" />
                </div>
            </div>
        </TransitionGroup>
    </div>
</template>

<style scoped>
.toast-enter-active { transition: all .3s cubic-bezier(.22,1,.36,1); }
.toast-leave-active { transition: all .25s ease; position: absolute; right: 0; width: 100%; }
.toast-enter-from { opacity: 0; transform: translateX(110%); }
.toast-leave-to { opacity: 0; transform: translateX(110%); }
.toast-move { transition: transform .3s cubic-bezier(.22,1,.36,1); }
</style>
