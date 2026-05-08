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
import { Button } from '@/components/ui/button';
import { Alert, AlertTitle, AlertDescription } from '@/components/ui/alert';
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

const masterDataLinks = [
    { name: 'Suppliers', href: route('admin.master.suppliers'), icon: Building2, active: route().current('admin.master.suppliers') },
    { name: 'Drivers', href: route('admin.master.drivers'), icon: UserCheck, active: route().current('admin.master.drivers') },
];

onMounted(() => {
    if (window.Echo) {
        const userRole = auth.user.role;
        const userType = auth.user.tipe;
        
        const channels = userRole === 'admin' 
            ? ['fg_psn', 'incoming_singkong', 'incoming_rmpm', 'fg_surabaya', 'cs_noodle_sby', 'cs_fg_sby']
            : [userType];

        channels.forEach(channel => {
            console.log('Listening to IoT channel: iot-weights.' + channel);
            window.Echo.channel('iot-weights.' + channel)
                .listen('.WeightReceived', (e) => {
                    console.log('Weight received via WebSocket on ' + channel + ':', e);
                    
                    notificationDot.value = true;

                    notifications.value.unshift({
                        id: Date.now(),
                        title: 'Data Masuk!',
                        message: `Berat: ${e.weight} kg (${e.product})`,
                    });

                    // In Inertia, we can reload the page or just data
                    router.reload({ only: ['penimbangans', 'stats', 'totalShift', 'totalBerat', 'activePenimbangan'] });
                });
        });
    }
});

const logout = () => {
    router.post(route('logout'));
};
</script>

<template>
    <div class="min-h-screen bg-gray-100">
        <!-- Top Navbar -->
        <nav :class="[desktopSidebarOpen ? 'sm:ml-64 sm:w-[calc(100%-16rem)]' : 'sm:ml-0 sm:w-full']" class="fixed top-0 z-40 w-full bg-blue-700 border-b border-blue-800 shadow-sm transition-all duration-300">
            <div class="px-3 py-3 lg:px-5 lg:pl-3">
                <div class="flex items-center justify-between">
                    <div class="flex items-center justify-start">
                        <!-- Sidebar Toggle for Mobile -->
                        <button @click="sidebarOpen = true" type="button" class="inline-flex items-center p-2 text-sm text-blue-100 rounded-lg sm:hidden hover:bg-blue-600 focus:outline-none">
                            <span class="sr-only">Open sidebar</span>
                            <Menu class="w-6 h-6" />
                        </button>
                        
                        <!-- Sidebar Toggle for Desktop -->
                        <button @click="desktopSidebarOpen = !desktopSidebarOpen" type="button" class="hidden sm:inline-flex items-center p-2 text-sm text-blue-100 rounded-lg hover:bg-blue-600 focus:outline-none mr-2">
                            <Menu class="w-6 h-6" />
                        </button>

                        <Link v-if="!desktopSidebarOpen" :href="route('dashboard')" class="hidden sm:flex items-center ms-2 md:me-24 gap-2">
                            <img src="/images/logo.webp" alt="Ladang Lima" class="h-8 w-auto brightness-0 invert">
                        </Link>
                        <Link :href="route('dashboard')" class="flex items-center ms-2 md:me-24 gap-2 sm:hidden">
                            <img src="/images/logo.webp" alt="Ladang Lima" class="h-8 w-auto brightness-0 invert">
                        </Link>
                    </div>
                    <div class="flex items-center">
                        <div class="flex items-center ms-3">
                            <div class="flex items-center gap-4">
                                <!-- Notification Bell -->
                                <div class="relative">
                                    <button @click="toggleNotifications" type="button" class="p-2 text-blue-100 rounded-lg hover:bg-blue-600 transition-all">
                                        <Bell class="w-6 h-6" />
                                        <div v-if="notificationDot" class="absolute top-2 right-2 w-3 h-3 bg-red-500 border-2 border-blue-700 rounded-full"></div>
                                    </button>
                                    <!-- Notifications Dropdown - Mobile: fixed full-width, Desktop: absolute -->
                                    <!-- Mobile overlay backdrop -->
                                    <div 
                                        v-if="showNotifications" 
                                        @click="showNotifications = false" 
                                        class="fixed inset-0 z-40 sm:hidden"
                                    ></div>
                                    <div 
                                        v-if="showNotifications" 
                                        class="fixed left-2 right-2 top-16 z-50 sm:absolute sm:left-auto sm:right-0 sm:top-auto sm:mt-2 sm:w-80 bg-white rounded-xl shadow-lg border border-gray-100 overflow-hidden"
                                    >
                                        <div class="p-4 border-b border-gray-100 flex justify-between items-center">
                                            <h6 class="font-bold text-gray-900">Notifications</h6>
                                            <div class="flex items-center gap-3">
                                                <button @click="clearNotifications" class="text-xs font-bold text-blue-600 hover:text-blue-800">Clear</button>
                                                <button @click="showNotifications = false" class="p-1 text-gray-400 hover:text-gray-600 rounded-lg hover:bg-gray-100 sm:hidden">
                                                    <X class="w-4 h-4" />
                                                </button>
                                            </div>
                                        </div>
                                        <div class="max-h-[60vh] sm:max-h-96 overflow-y-auto p-2 space-y-2">
                                            <Alert v-for="notif in notifications" :key="notif.id" class="border-blue-100 bg-blue-50/50">
                                                <AlertTitle class="text-blue-800 text-sm font-bold">{{ notif.title }}</AlertTitle>
                                                <AlertDescription class="text-xs text-blue-600 mt-1">
                                                    {{ notif.message }}
                                                </AlertDescription>
                                            </Alert>
                                            <div v-if="notifications.length === 0" class="p-4 text-center text-sm text-gray-500">
                                                No new notifications
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="text-right hidden md:block">
                                    <p class="text-sm font-bold text-white">{{ auth.user.name }}</p>
                                    <p class="text-xs text-blue-200 capitalize">{{ auth.user.role.replace('_', ' ') }} - Shift {{ auth.user.shift ?? '-' }}</p>
                                </div>
                                
                                <Button @click="logout" variant="destructive" size="sm" class="bg-red-600 hover:bg-red-700">
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
            class="fixed top-0 left-0 z-50 w-64 h-screen pt-20 sm:pt-0 transition-transform duration-300 bg-white border-r border-gray-200 shadow-xl sm:shadow-none" 
            aria-label="Sidebar"
        >
            <div class="h-full px-4 pb-4 overflow-y-auto bg-white">
                <!-- Logo Sidebar -->
                <div class="flex items-center justify-center h-[65px] border-b border-gray-100 mb-6">
                    <img src="/images/logo.webp" alt="Ladang Lima" class="h-10 sm:h-8 w-auto">
                </div>

                <div class="flex items-center justify-between mb-6 sm:hidden">
                    <span class="text-xs font-black text-gray-400 uppercase tracking-widest">Menu Navigasi</span>
                    <button @click="sidebarOpen = false" class="p-2 hover:bg-gray-100 rounded-full">
                        <X class="w-5 h-5 text-gray-500" />
                    </button>
                </div>

                <ul class="space-y-1.5 font-medium">
                    <li>
                        <Link :href="route('dashboard')">
                            <Button 
                                variant="ghost" 
                                class="w-full justify-start gap-3 h-11 px-3 rounded-xl transition-all"
                                :class="route().current('dashboard') ? 'bg-blue-50 text-blue-700 font-black' : 'text-gray-600 hover:bg-gray-50'"
                            >
                                <LayoutDashboard class="w-5 h-5" :class="route().current('dashboard') ? 'text-blue-600' : 'text-gray-400'" />
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
                                class="w-full justify-start gap-3 h-11 px-3 rounded-xl transition-all"
                                :class="route().current('admin.fg') || route().current('fg.dashboard') ? 'bg-blue-50 text-blue-700 font-black' : 'text-gray-600 hover:bg-gray-50'"
                            >
                                <ClipboardList class="w-5 h-5" :class="route().current('admin.fg') || route().current('fg.dashboard') ? 'text-blue-600' : 'text-gray-400'" />
                                <span>Formulasi</span>
                            </Button>
                        </Link>
                    </li>
                    <li>
                        <Link :href="auth.user.role === 'admin' ? route('admin.fg-psn') : route('fg-psn.dashboard')">
                            <Button 
                                variant="ghost" 
                                class="w-full justify-start gap-3 h-11 px-3 rounded-xl transition-all"
                                :class="route().current('admin.fg-psn') || route().current('fg-psn.dashboard') ? 'bg-blue-50 text-blue-700 font-black' : 'text-gray-600 hover:bg-gray-50'"
                            >
                                <Box class="w-5 h-5" :class="route().current('admin.fg-psn') || route().current('fg-psn.dashboard') ? 'text-blue-600' : 'text-gray-400'" />
                                <span>Finished Goods</span>
                            </Button>
                        </Link>
                    </li>
                    <li>
                        <Link :href="auth.user.role === 'admin' ? route('admin.incoming.singkong') : route('incoming.singkong.dashboard')">
                            <Button 
                                variant="ghost" 
                                class="w-full justify-start gap-3 h-11 px-3 rounded-xl transition-all"
                                :class="route().current('admin.incoming.singkong') || route().current('incoming.singkong.dashboard') ? 'bg-blue-50 text-blue-700 font-black' : 'text-gray-600 hover:bg-gray-50'"
                            >
                                <Truck class="w-5 h-5" :class="route().current('admin.incoming.singkong') || route().current('incoming.singkong.dashboard') ? 'text-blue-600' : 'text-gray-400'" />
                                <span>Incoming Singkong</span>
                            </Button>
                        </Link>
                    </li>
                    <li>
                        <Link :href="auth.user.role === 'admin' ? route('admin.incoming.rmpm') : route('incoming.rmpm.dashboard')">
                            <Button 
                                variant="ghost" 
                                class="w-full justify-start gap-3 h-11 px-3 rounded-xl transition-all"
                                :class="route().current('admin.incoming.rmpm') || route().current('incoming.rmpm.dashboard') ? 'bg-blue-50 text-blue-700 font-black' : 'text-gray-600 hover:bg-gray-50'"
                            >
                                <Package class="w-5 h-5" :class="route().current('admin.incoming.rmpm') || route().current('incoming.rmpm.dashboard') ? 'text-blue-600' : 'text-gray-400'" />
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
                                class="w-full justify-start gap-3 h-11 px-3 rounded-xl transition-all"
                                :class="route().current('admin.fg-surabaya') || route().current('fg-surabaya.dashboard') ? 'bg-blue-50 text-blue-700 font-black' : 'text-gray-600 hover:bg-gray-50'"
                            >
                                <ClipboardList class="w-5 h-5" :class="route().current('admin.fg-surabaya') || route().current('fg-surabaya.dashboard') ? 'text-blue-600' : 'text-gray-400'" />
                                <span>Formulasi</span>
                            </Button>
                        </Link>
                    </li>
                    <li>
                        <Link :href="auth.user.role === 'admin' ? route('admin.cs-noodle-sby') : route('cs-noodle-sby.dashboard')">
                            <Button 
                                variant="ghost" 
                                class="w-full justify-start gap-3 h-11 px-3 rounded-xl transition-all"
                                :class="route().current('admin.cs-noodle-sby') || route().current('cs-noodle-sby.dashboard') ? 'bg-blue-50 text-blue-700 font-black' : 'text-gray-600 hover:bg-gray-50'"
                            >
                                <Box class="w-5 h-5" :class="route().current('admin.cs-noodle-sby') || route().current('cs-noodle-sby.dashboard') ? 'text-blue-600' : 'text-gray-400'" />
                                <span>CS Noodle</span>
                            </Button>
                        </Link>
                    </li>
                    <li>
                        <Link :href="auth.user.role === 'admin' ? route('admin.cs-fg-sby') : route('cs-fg-sby.dashboard')">
                            <Button 
                                variant="ghost" 
                                class="w-full justify-start gap-3 h-11 px-3 rounded-xl transition-all"
                                :class="route().current('admin.cs-fg-sby') || route().current('cs-fg-sby.dashboard') ? 'bg-blue-50 text-blue-700 font-black' : 'text-gray-600 hover:bg-gray-50'"
                            >
                                <Box class="w-5 h-5" :class="route().current('admin.cs-fg-sby') || route().current('cs-fg-sby.dashboard') ? 'text-blue-600' : 'text-gray-400'" />
                                <span>CS FG-Sby</span>
                            </Button>
                        </Link>
                    </li>

                    <!-- Master Data Section (Admin Only) -->
                    <template v-if="auth.user.role === 'admin'">
                        <li class="pt-6 pb-1 px-3">
                            <span class="text-[10px] font-black text-gray-400 uppercase tracking-[0.2em]">Master Data</span>
                        </li>
                        <li v-for="link in masterDataLinks" :key="link.name">
                            <Link :href="link.href">
                                <Button 
                                    variant="ghost" 
                                    class="w-full justify-start gap-3 h-11 px-3 rounded-xl transition-all"
                                    :class="link.active ? 'bg-blue-50 text-blue-700 font-black' : 'text-gray-600 hover:bg-gray-50'"
                                >
                                    <component :is="link.icon" class="w-5 h-5" :class="link.active ? 'text-blue-600' : 'text-gray-400'" />
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
                            class="w-full justify-start gap-3 h-11 px-3 rounded-xl text-red-600 hover:bg-red-50 hover:text-red-700 transition-all font-bold"
                        >
                            <LogOut class="w-5 h-5 text-red-500" />
                            <span>Logout</span>
                        </Button>
                    </li>
                </ul>
            </div>
        </aside>

        <!-- Content Area -->
        <main :class="[desktopSidebarOpen ? 'sm:ml-64' : 'sm:ml-0']" class="p-4 mt-14 transition-all">
            <slot />
        </main>
    </div>
</template>
