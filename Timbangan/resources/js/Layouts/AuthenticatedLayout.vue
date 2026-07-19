<script setup>
import { ref, onMounted } from 'vue';
import { Link, usePage, router } from '@inertiajs/vue3';
import { 
    LayoutDashboard, 
    ClipboardList, 
    Package, 
    Truck, 
    Box, 
    LogOut, 
    ChevronDown, 
    Bell,
    Menu,
    X,
    Building2,
    UserCheck
} from 'lucide-vue-next';
import { Button } from '@/Components/ui/button';
import { Alert, AlertTitle, AlertDescription } from '@/Components/ui/alert';
import ToastStack from '@/Components/ToastStack.vue';
import Swal from 'sweetalert2';

const { auth } = usePage().props;
const showingNavigationDropdown = ref(false);
const sidebarOpen = ref(false); // Default to closed on mobile
const desktopSidebarOpen = ref(true); // Desktop separate state
const notificationDot = ref(false);
const showNotifications = ref(false);
const notifications = ref([]);

const toggleNotifications = () => {
    showNotifications.value = !showNotifications.value;
    if (showNotifications.value) {
        notificationDot.value = false;
    }
};

const clearNotifications = () => {
    notifications.value = [];
};

const isPasuruanOpen = ref(true);
const isSurabayaOpen = ref(true);

const safeRoute = (name) => {
    try { return route(name); } catch (e) { return '/admin/master/login-logs'; }
};

const safeActive = (name) => {
    try { return route().current(name); } catch (e) { return false; }
};

const masterDataLinks = [
    { name: 'Suppliers', href: safeRoute('admin.master.suppliers'), icon: Building2, active: safeActive('admin.master.suppliers') },
    { name: 'Drivers', href: safeRoute('admin.master.drivers'), icon: UserCheck, active: safeActive('admin.master.drivers') },
    { name: 'Logs Login', href: safeRoute('admin.master.login-logs'), icon: ClipboardList, active: safeActive('admin.master.login-logs') },
];

// Throttle reload supaya burst data dari beberapa modul tidak menembak server berkali-kali
let layoutReloadTimer = null;
const queueLayoutReload = () => {
    if (layoutReloadTimer) return;
    layoutReloadTimer = setTimeout(() => {
        layoutReloadTimer = null;
        router.reload({ only: ['penimbangans', 'stats', 'totalShift', 'totalBerat', 'activePenimbangan'], preserveScroll: true });
    }, 1000);
};

onMounted(() => {
    if (window.Echo) {
        const userRole = auth.user.role;
        const userType = auth.user.tipe;

        const channels = userRole === 'admin'
            ? ['fg', 'fg_psn', 'formulasi_pasuruan', 'incoming_singkong', 'incoming_rmpm', 'fg_surabaya', 'cs_noodle_sby', 'cs_fg_sby']
            : [userType];

        channels.forEach(channel => {
            window.Echo.channel('iot-weights.' + channel)
                .listen('.WeightReceived', (e) => {
                    notificationDot.value = true;

                    notifications.value.unshift({
                        id: Date.now(),
                        title: 'Data Masuk!',
                        message: `Berat: ${e.weight} kg (${e.product})`,
                    });

                    queueLayoutReload();
                });
        });
    }
});

const logout = () => {
    router.post(route('logout'));
};
</script>

<template>
    <div class="min-h-screen bg-background text-foreground">
        <!-- Top Navbar -->
        <nav :class="[desktopSidebarOpen ? 'sm:ml-64 sm:w-[calc(100%-16rem)]' : 'sm:ml-0 sm:w-full']" class="fixed top-0 z-40 w-full bg-background/80 backdrop-blur-md border-b border-border shadow-sm transition-all duration-300">
            <div class="px-3 py-3 lg:px-5 lg:pl-3">
                <div class="flex items-center justify-between">
                    <div class="flex items-center justify-start">
                        <!-- Sidebar Toggle for Mobile -->
                        <Button @click="sidebarOpen = true" variant="ghost" size="icon" class="sm:hidden mr-2 text-muted-foreground">
                            <span class="sr-only">Open sidebar</span>
                            <Menu class="w-5 h-5" />
                        </Button>
                        
                        <!-- Sidebar Toggle for Desktop -->
                        <Button @click="desktopSidebarOpen = !desktopSidebarOpen" variant="ghost" size="icon" class="hidden sm:flex mr-2 text-muted-foreground">
                            <Menu class="w-5 h-5" />
                        </Button>

                        <Link v-if="!desktopSidebarOpen" :href="route('dashboard')" class="hidden sm:flex items-center ms-2 md:me-24 gap-2">
                            <img src="/images/logo.webp" alt="Ladang Lima" class="h-8 w-auto">
                        </Link>
                        <Link :href="route('dashboard')" class="flex items-center ms-2 md:me-24 gap-2 sm:hidden">
                            <img src="/images/logo.webp" alt="Ladang Lima" class="h-8 w-auto">
                        </Link>
                    </div>
                    <div class="flex items-center">
                        <div class="flex items-center ms-3">
                            <div class="flex items-center gap-4">
                                <!-- Notification Bell -->
                                <div class="relative">
                                    <Button @click="toggleNotifications" variant="ghost" size="icon" class="text-muted-foreground relative">
                                        <Bell class="w-5 h-5" />
                                        <div v-if="notificationDot" class="absolute top-2 right-2 w-2.5 h-2.5 bg-red-500 border-2 border-background rounded-full"></div>
                                    </Button>
                                    <!-- Notifications Dropdown - Mobile: fixed full-width, Desktop: absolute -->
                                    <!-- Mobile overlay backdrop -->
                                    <div 
                                        v-if="showNotifications" 
                                        @click="showNotifications = false" 
                                        class="fixed inset-0 z-40 sm:hidden"
                                    ></div>
                                    <div 
                                        v-if="showNotifications" 
                                        class="fixed left-2 right-2 top-16 z-50 sm:absolute sm:left-auto sm:right-0 sm:top-auto sm:mt-2 sm:w-80 bg-popover text-popover-foreground rounded-lg shadow-lg border border-border overflow-hidden"
                                    >
                                        <div class="p-4 border-b border-border flex justify-between items-center">
                                            <h6 class="font-semibold text-foreground text-sm">Notifications</h6>
                                            <div class="flex items-center gap-3">
                                                <Button @click="clearNotifications" variant="link" size="sm" class="h-auto p-0 text-xs text-primary">Clear</Button>
                                                <Button @click="showNotifications = false" variant="ghost" size="icon" class="h-6 w-6 sm:hidden">
                                                    <X class="w-4 h-4" />
                                                </Button>
                                            </div>
                                        </div>
                                        <div class="max-h-[60vh] sm:max-h-96 overflow-y-auto p-2 space-y-2">
                                            <Alert v-for="notif in notifications" :key="notif.id">
                                                <AlertTitle class="text-sm font-semibold">{{ notif.title }}</AlertTitle>
                                                <AlertDescription class="text-xs text-muted-foreground mt-1">
                                                    {{ notif.message }}
                                                </AlertDescription>
                                            </Alert>
                                            <div v-if="notifications.length === 0" class="p-4 text-center text-sm text-muted-foreground">
                                                No new notifications
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="text-right hidden md:block">
                                    <p class="text-sm font-semibold text-foreground">{{ auth.user.name }}</p>
                                    <p class="text-xs text-muted-foreground capitalize">{{ auth.user.role.replace('_', ' ') }} - {{ auth.user.shift ? (String(auth.user.shift).toLowerCase().includes('shift') ? auth.user.shift : 'Shift ' + auth.user.shift) : '-' }}</p>
                                </div>
                                
                                <Button @click="logout" variant="outline" size="sm" class="font-medium text-red-600 hover:text-red-700 hover:bg-red-50 border-red-200">
                                    Logout
                                </Button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Mobile Sidebar Overlay -->
        <div 
            v-if="sidebarOpen" 
            @click="sidebarOpen = false" 
            class="fixed inset-0 z-40 bg-black/50 backdrop-blur-sm sm:hidden transition-opacity"
        ></div>

        <!-- Sidebar -->
        <aside 
            :class="[
                sidebarOpen ? 'translate-x-0' : '-translate-x-full',
                desktopSidebarOpen ? 'sm:translate-x-0' : 'sm:-translate-x-full'
            ]" 
            class="fixed top-0 left-0 z-50 w-64 h-screen pt-20 sm:pt-0 transition-transform duration-300 bg-card border-r border-border shadow-xl sm:shadow-none flex flex-col" 
            aria-label="Sidebar"
        >
            <!-- Fixed Logo Area -->
            <div class="flex-shrink-0 px-4">
                <div class="flex items-center justify-center h-[65px] border-b border-border mb-4 py-2">
                    <div class="flex items-center justify-center w-full max-w-[180px]">
                        <img src="/images/logo.webp" alt="Ladang Lima" class="h-10 sm:h-8 w-auto">
                    </div>
                </div>

                <div class="flex items-center justify-between mb-4 sm:hidden">
                    <span class="text-xs font-black text-muted-foreground uppercase tracking-widest">Menu Navigasi</span>
                    <button @click="sidebarOpen = false" class="p-2 hover:bg-accent rounded-full">
                        <X class="w-5 h-5 text-muted-foreground" />
                    </button>
                </div>
            </div>

            <!-- Scrollable Menu Area -->
            <div class="flex-1 px-4 pb-4 overflow-y-auto">
                <ul class="space-y-1.5 font-medium">
                    <li>
                        <Link :href="route('dashboard')">
                            <Button 
                                variant="ghost" 
                                class="w-full justify-start gap-3 h-10 px-3 transition-colors"
                                :class="route().current('dashboard') ? 'bg-primary/15 text-primary font-bold border-r-4 border-primary rounded-r-none' : 'text-slate-600 dark:text-slate-400 hover:bg-primary/10 hover:text-primary'"
                            >
                                <LayoutDashboard class="w-4 h-4" />
                                <span>Overview</span>
                            </Button>
                        </Link>
                    </li>

                    <!-- Pasuruan Section -->
                    <li class="pt-4 pb-1 px-3">
                        <span class="text-[10px] font-black text-gray-400 uppercase tracking-[0.2em]">Pasuruan</span>
                    </li>
                    <li>
                        <Link :href="auth.user.role === 'admin' ? route('admin.fg') : route('fg.dashboard')">
                            <Button 
                                variant="ghost" 
                                class="w-full justify-start gap-3 h-10 px-3 transition-colors"
                                :class="route().current('admin.fg') || route().current('fg.dashboard') ? 'bg-primary/15 text-primary font-bold border-r-4 border-primary rounded-r-none' : 'text-slate-600 dark:text-slate-400 hover:bg-primary/10 hover:text-primary'"
                            >
                                <ClipboardList class="w-4 h-4" />
                                <span>Formulasi</span>
                            </Button>
                        </Link>
                    </li>
                    <li>
                        <Link :href="auth.user.role === 'admin' ? route('admin.fg-psn') : route('fg-psn.dashboard')">
                            <Button 
                                variant="ghost" 
                                class="w-full justify-start gap-3 h-10 px-3 transition-colors"
                                :class="route().current('admin.fg-psn') || route().current('fg-psn.dashboard') ? 'bg-primary/15 text-primary font-bold border-r-4 border-primary rounded-r-none' : 'text-slate-600 dark:text-slate-400 hover:bg-primary/10 hover:text-primary'"
                            >
                                <Box class="w-4 h-4" />
                                <span>Finished Goods</span>
                            </Button>
                        </Link>
                    </li>
                    <li>
                        <Link :href="auth.user.role === 'admin' ? route('admin.incoming.singkong') : route('incoming.singkong.dashboard')">
                            <Button 
                                variant="ghost" 
                                class="w-full justify-start gap-3 h-10 px-3 transition-colors"
                                :class="route().current('admin.incoming.singkong') || route().current('incoming.singkong.dashboard') ? 'bg-primary/15 text-primary font-bold border-r-4 border-primary rounded-r-none' : 'text-slate-600 dark:text-slate-400 hover:bg-primary/10 hover:text-primary'"
                            >
                                <Truck class="w-4 h-4" />
                                <span>Incoming Singkong</span>
                            </Button>
                        </Link>
                    </li>
                    <li>
                        <Link :href="auth.user.role === 'admin' ? route('admin.incoming.rmpm') : route('incoming.rmpm.dashboard')">
                            <Button 
                                variant="ghost" 
                                class="w-full justify-start gap-3 h-10 px-3 transition-colors"
                                :class="route().current('admin.incoming.rmpm') || route().current('incoming.rmpm.dashboard') ? 'bg-primary/15 text-primary font-bold border-r-4 border-primary rounded-r-none' : 'text-slate-600 dark:text-slate-400 hover:bg-primary/10 hover:text-primary'"
                            >
                                <Package class="w-4 h-4" />
                                <span>Incoming RMPM</span>
                            </Button>
                        </Link>
                    </li>

                    <!-- Surabaya Section -->
                    <li class="pt-4 pb-1 px-3">
                        <span class="text-[10px] font-black text-gray-400 uppercase tracking-[0.2em]">Surabaya</span>
                    </li>
                    <li>
                        <Link :href="auth.user.role === 'admin' ? route('admin.fg-surabaya') : route('fg-surabaya.dashboard')">
                            <Button 
                                variant="ghost" 
                                class="w-full justify-start gap-3 h-10 px-3 transition-colors"
                                :class="route().current('admin.fg-surabaya') || route().current('fg-surabaya.dashboard') ? 'bg-primary/15 text-primary font-bold border-r-4 border-primary rounded-r-none' : 'text-slate-600 dark:text-slate-400 hover:bg-primary/10 hover:text-primary'"
                            >
                                <ClipboardList class="w-4 h-4" />
                                <span>Formulasi</span>
                            </Button>
                        </Link>
                    </li>
                    <li>
                        <Link :href="auth.user.role === 'admin' ? route('admin.cs-noodle-sby') : route('cs-noodle-sby.dashboard')">
                            <Button 
                                variant="ghost" 
                                class="w-full justify-start gap-3 h-10 px-3 transition-colors"
                                :class="route().current('admin.cs-noodle-sby') || route().current('cs-noodle-sby.dashboard') ? 'bg-primary/15 text-primary font-bold border-r-4 border-primary rounded-r-none' : 'text-slate-600 dark:text-slate-400 hover:bg-primary/10 hover:text-primary'"
                            >
                                <Box class="w-4 h-4" />
                                <span>CS Noodle</span>
                            </Button>
                        </Link>
                    </li>
                    <li>
                        <Link :href="auth.user.role === 'admin' ? route('admin.cs-fg-sby') : route('cs-fg-sby.dashboard')">
                            <Button 
                                variant="ghost" 
                                class="w-full justify-start gap-3 h-10 px-3 transition-colors"
                                :class="route().current('admin.cs-fg-sby') || route().current('cs-fg-sby.dashboard') ? 'bg-primary/15 text-primary font-bold border-r-4 border-primary rounded-r-none' : 'text-slate-600 dark:text-slate-400 hover:bg-primary/10 hover:text-primary'"
                            >
                                <Box class="w-4 h-4" />
                                <span>CS FG-Sby</span>
                            </Button>
                        </Link>
                    </li>

                    <!-- Master Data Section (Admin Only) - includes Logs Login -->
                    <template v-if="auth.user.role === 'admin'">
                        <li class="pt-6 pb-1 px-3">
                            <span class="text-[10px] font-black text-gray-400 uppercase tracking-[0.2em]">Master Data</span>
                        </li>
                        <li v-for="link in masterDataLinks" :key="link.name">
                            <Link :href="link.href">
                                <Button 
                                    variant="ghost" 
                                    class="w-full justify-start gap-3 h-10 px-3 transition-colors"
                                    :class="link.active ? 'bg-primary/15 text-primary font-bold border-r-4 border-primary rounded-r-none' : 'text-slate-600 dark:text-slate-400 hover:bg-primary/10 hover:text-primary'"
                                >
                                    <component :is="link.icon" class="w-4 h-4" />
                                    <span>{{ link.name }}</span>
                                </Button>
                            </Link>
                        </li>
                    </template>

                    <!-- Logout -->
                    <li class="pt-8">
                        <Button 
                            @click="logout" 
                            variant="ghost" 
                            class="w-full justify-start gap-3 h-10 px-3 text-red-600 hover:bg-red-50 hover:text-red-700 font-medium"
                        >
                            <LogOut class="w-4 h-4" />
                            <span>Logout</span>
                        </Button>
                    </li>
                </ul>
            </div>
        </aside>

        <!-- Content Area -->
        <main :class="[desktopSidebarOpen ? 'sm:ml-64' : 'sm:ml-0']" class="p-6 mt-14 transition-all">
            <slot />
        </main>

        <!-- Toast notifikasi global (data timbangan masuk) -->
        <ToastStack />
    </div>
</template>
