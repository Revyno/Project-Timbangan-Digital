<script setup>
// Pagination bergaya sama dengan halaman /admin/master/login-logs.
// Terima objek paginator Laravel dan emit 'change' berisi nomor halaman
// supaya tiap halaman bisa mempertahankan filter-nya masing-masing.
const props = defineProps({
    paginator: {
        type: Object,
        required: true,
    },
});

const emit = defineEmits(['change']);

const getPageArray = (currentPage, lastPage) => {
    let pages = [];
    for (let i = 1; i <= lastPage; i++) {
        if (i === 1 || i === lastPage || (i >= currentPage - 1 && i <= currentPage + 1)) {
            pages.push(i);
        } else if (pages[pages.length - 1] !== '...') {
            pages.push('...');
        }
    }
    return pages;
};

const goTo = (page) => {
    if (page !== props.paginator.current_page) {
        emit('change', page);
    }
};
</script>

<template>
    <div v-if="paginator.last_page > 1" class="border-t p-4 flex items-center justify-center sm:justify-between bg-muted/20">
        <div class="hidden sm:block text-xs font-mono font-bold text-muted-foreground">
            Menampilkan {{ paginator.from }}–{{ paginator.to }} dari {{ paginator.total }} data
        </div>
        <div class="flex gap-1">
            <button
                v-if="paginator.current_page > 1"
                type="button"
                @click="goTo(paginator.current_page - 1)"
                class="px-3 py-1.5 text-xs font-bold border-2 border-black rounded-md shadow-[2px_2px_0_0_#000000] hover:translate-y-[1px] hover:translate-x-[1px] hover:shadow-[1px_1px_0_0_#000000] transition-all bg-white"
            >
                Prev
            </button>

            <template v-for="page in getPageArray(paginator.current_page, paginator.last_page)" :key="page">
                <span v-if="page === '...'" class="px-2 py-1.5 text-xs font-bold">...</span>
                <button
                    v-else
                    type="button"
                    @click="goTo(page)"
                    :class="[
                        'px-3 py-1.5 text-xs font-bold border-2 border-black rounded-md transition-all',
                        page === paginator.current_page
                            ? 'bg-primary text-primary-foreground shadow-[2px_2px_0_0_#000000]'
                            : 'bg-white hover:translate-y-[1px] hover:translate-x-[1px] shadow-[2px_2px_0_0_#000000] hover:shadow-[1px_1px_0_0_#000000]'
                    ]"
                >
                    {{ page }}
                </button>
            </template>

            <button
                v-if="paginator.current_page < paginator.last_page"
                type="button"
                @click="goTo(paginator.current_page + 1)"
                class="px-3 py-1.5 text-xs font-bold border-2 border-black rounded-md shadow-[2px_2px_0_0_#000000] hover:translate-y-[1px] hover:translate-x-[1px] hover:shadow-[1px_1px_0_0_#000000] transition-all bg-white"
            >
                Next
            </button>
        </div>
    </div>
</template>
