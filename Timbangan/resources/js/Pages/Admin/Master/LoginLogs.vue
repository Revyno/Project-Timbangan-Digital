<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { Card, CardHeader, CardTitle } from '@/Components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/Components/ui/table';
import { Badge } from '@/Components/ui/badge';
import { Users, ClipboardList } from 'lucide-vue-next';
import { Button } from '@/Components/ui/button';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    loginLogs: {
        type: Object,
        required: true,
    }
});

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
</script>

<template>
    <Head title="Logs Login" />

    <AuthenticatedLayout>
        <div class="space-y-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="font-display text-4xl tracking-wider text-primary drop-shadow-sm">Logs Login</h1>
                    <p class="font-mono text-sm font-bold text-muted-foreground mt-2 uppercase tracking-widest">
                        Riwayat Autentikasi Pengguna
                    </p>
                </div>
            </div>

            <!-- Login Logs Table -->
            <Card class="overflow-hidden border-2 border-black rounded-xl shadow-[4px_4px_0_0_#000000] bg-surface dark:bg-slate-900 mt-6">
                <CardHeader class="flex flex-row items-center justify-between pb-4 border-b">
                    <div>
                        <CardTitle class="font-display text-xl tracking-wider">Data Login</CardTitle>
                        <p class="font-mono text-xs font-bold text-muted-foreground mt-1">Daftar pengguna yang login ke sistem</p>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <ClipboardList class="w-5 h-5 text-primary" />
                    </div>
                </CardHeader>
                <div class="overflow-x-auto">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead class="w-12 text-center">No.</TableHead>
                                <TableHead>Pengguna</TableHead>
                                <TableHead>Tipe/Role</TableHead>
                                <TableHead>IP Address</TableHead>
                                <TableHead>Perangkat / Browser</TableHead>
                                <TableHead>Waktu Login</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="(log, index) in loginLogs.data" :key="log.id">
                                <TableCell class="text-center text-muted-foreground text-xs">{{ (loginLogs.current_page - 1) * loginLogs.per_page + index + 1 }}</TableCell>
                                <TableCell class="font-medium">{{ log.user?.name || 'Unknown' }}</TableCell>
                                <TableCell>
                                    <Badge variant="outline" class="text-[10px] uppercase">
                                        {{ log.user?.tipe || '-' }}
                                    </Badge>
                                </TableCell>
                                <TableCell>
                                    <code class="text-xs bg-muted px-1.5 py-0.5 rounded border">{{ log.ip_address || '-' }}</code>
                                </TableCell>
                                <TableCell class="max-w-[300px] truncate" :title="log.user_agent">
                                    <span class="text-xs text-muted-foreground">{{ log.user_agent || '-' }}</span>
                                </TableCell>
                                <TableCell class="whitespace-nowrap">
                                    <div class="font-medium">
                                        {{ new Date(log.login_at).toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' }) }}
                                    </div>
                                    <div class="text-[10px] text-muted-foreground">
                                        {{ new Date(log.login_at).toLocaleDateString('id-ID') }}
                                    </div>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </div>
                <div v-if="loginLogs.data.length === 0" class="p-16 text-center border-t">
                    <div class="w-12 h-12 bg-muted rounded-full flex items-center justify-center mx-auto mb-4">
                        <Users class="w-6 h-6 text-muted-foreground" />
                    </div>
                    <p class="text-foreground font-medium">Belum ada riwayat login</p>
                    <p class="text-muted-foreground text-sm mt-1">Data log login akan muncul di sini.</p>
                </div>

                <!-- Pagination -->
                <div v-if="loginLogs.last_page > 1" class="border-t p-4 flex items-center justify-center sm:justify-between bg-muted/20">
                    <div class="hidden sm:block text-xs font-mono font-bold text-muted-foreground">
                        Menampilkan {{ loginLogs.from }}–{{ loginLogs.to }} dari {{ loginLogs.total }} data
                    </div>
                    <div class="flex gap-1">
                        <Link
                            v-if="loginLogs.prev_page_url"
                            :href="loginLogs.prev_page_url"
                            class="px-3 py-1.5 text-xs font-bold border-2 border-black rounded-md shadow-[2px_2px_0_0_#000000] hover:translate-y-[1px] hover:translate-x-[1px] hover:shadow-[1px_1px_0_0_#000000] transition-all bg-white"
                        >
                            Prev
                        </Link>
                        
                        <template v-for="page in getPageArray(loginLogs.current_page, loginLogs.last_page)" :key="page">
                            <span v-if="page === '...'" class="px-2 py-1.5 text-xs font-bold">...</span>
                            <Link
                                v-else
                                :href="loginLogs.path + '?page=' + page"
                                :class="[ 
                                    'px-3 py-1.5 text-xs font-bold border-2 border-black rounded-md transition-all',
                                    page === loginLogs.current_page 
                                        ? 'bg-primary text-primary-foreground shadow-[2px_2px_0_0_#000000]' 
                                        : 'bg-white hover:translate-y-[1px] hover:translate-x-[1px] shadow-[2px_2px_0_0_#000000] hover:shadow-[1px_1px_0_0_#000000]'
                                ]"
                            >
                                {{ page }}
                            </Link>
                        </template>

                        <Link
                            v-if="loginLogs.next_page_url"
                            :href="loginLogs.next_page_url"
                            class="px-3 py-1.5 text-xs font-bold border-2 border-black rounded-md shadow-[2px_2px_0_0_#000000] hover:translate-y-[1px] hover:translate-x-[1px] hover:shadow-[1px_1px_0_0_#000000] transition-all bg-white"
                        >
                            Next
                        </Link>
                    </div>
                </div>
            </Card>
        </div>
    </AuthenticatedLayout>
</template>
