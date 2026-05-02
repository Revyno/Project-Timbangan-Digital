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
    X
} from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import Swal from 'sweetalert2';

const { auth } = usePage().props;
const showingNavigationDropdown = ref(false);
const sidebarOpen = ref(true);
const notificationDot = ref(false);

const isPasuruanOpen = ref(true);
const isSurabayaOpen = ref(true);

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

                    Swal.fire({
                        icon: 'success',
                        title: 'Data Masuk!',
                        text: `Berat: ${e.weight} kg (${e.product})`,
                        timer: 3000,
                        showConfirmButton: false,
                        toast: true,
                        position: 'top-end'
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
        <nav class="fixed top-0 z-50 w-full bg-blue-700 border-b border-blue-800 shadow-sm">
            <div class="px-3 py-3 lg:px-5 lg:pl-3">
                <div class="flex items-center justify-between">
                    <div class="flex items-center justify-start">
                        <button @click="sidebarOpen = !sidebarOpen" type="button" class="inline-flex items-center p-2 text-sm text-blue-100 rounded-lg sm:hidden hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-300">
                            <span class="sr-only">Open sidebar</span>
                            <Menu v-if="!sidebarOpen" class="w-6 h-6" />
                            <X v-else class="w-6 h-6" />
                        </button>
                        <Link :href="route('dashboard')" class="flex items-center ms-2 md:me-24 gap-2">
                            <img src="/images/logo.webp" alt="Ladang Lima" class="h-8 w-auto brightness-0 invert">
                        </Link>
                    </div>
                    <div class="flex items-center">
                        <div class="flex items-center ms-3">
                            <div class="flex items-center gap-4">
                                <!-- Notification Bell -->
                                <div class="relative">
                                    <button @click="notificationDot = false" type="button" class="p-2 text-blue-100 rounded-lg hover:bg-blue-600 transition-all">
                                        <Bell class="w-6 h-6" />
                                        <div v-if="notificationDot" class="absolute top-2 right-2 w-3 h-3 bg-red-500 border-2 border-blue-700 rounded-full"></div>
                                    </button>
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

        <!-- Sidebar -->
        <aside :class="{'translate-x-0': sidebarOpen, '-translate-x-full': !sidebarOpen}" class="fixed top-0 left-0 z-40 w-64 h-screen pt-20 transition-transform bg-white border-r border-gray-200 sm:translate-x-0 shadow-sm" aria-label="Sidebar">
            <div class="h-full px-3 pb-4 overflow-y-auto bg-white">
                <ul class="space-y-2 font-medium">
                    <li>
                        <Link :href="route('dashboard')" :class="route().current('dashboard') ? 'bg-blue-600 text-white shadow-md shadow-blue-500/20' : 'text-gray-600 hover:bg-gray-50'" class="flex items-center p-2 rounded-lg group transition-colors">
                            <LayoutDashboard class="w-5 h-5" :class="route().current('dashboard') ? 'text-white' : 'text-gray-400 group-hover:text-blue-600'" />
                            <span class="ms-3">Overview</span>
                        </Link>
                    </li>

                    <!-- Pasuruan Section -->
                    <li>
                        <button @click="isPasuruanOpen = !isPasuruanOpen" type="button" class="flex items-center w-full p-2 text-base text-gray-600 transition duration-75 rounded-lg group hover:bg-gray-50">
                            <Package class="flex-shrink-0 w-5 h-5 text-gray-400 transition duration-75 group-hover:text-blue-600" />
                            <span class="flex-1 ms-3 text-left rtl:text-right whitespace-nowrap font-bold">Pasuruan</span>
                            <ChevronDown class="w-4 h-4 transition-transform" :class="{'rotate-180': isPasuruanOpen}" />
                        </button>
                        <ul v-show="isPasuruanOpen" class="py-2 space-y-2">
                            <li>
                                <Link :href="route('fg.dashboard')" :class="route().current('fg.dashboard') ? 'bg-blue-600 text-white shadow-md shadow-blue-500/20' : 'text-gray-600 hover:bg-gray-50'" class="flex items-center w-full p-2 text-sm transition-colors duration-75 rounded-lg pl-11 group">
                                    <ClipboardList class="w-4 h-4 mr-2" />
                                    <span>Formulasi</span>
                                </Link>
                            </li>
                            <li>
                                <Link :href="route('fg-psn.dashboard')" :class="route().current('fg-psn.dashboard') ? 'bg-blue-600 text-white shadow-md shadow-blue-500/20' : 'text-gray-600 hover:bg-gray-50'" class="flex items-center w-full p-2 text-sm transition-colors duration-75 rounded-lg pl-11 group">
                                    <Box class="w-4 h-4 mr-2" />
                                    <span>Finished Goods</span>
                                </Link>
                            </li>
                            <li>
                                <Link :href="route('incoming.singkong.dashboard')" :class="route().current('incoming.singkong.dashboard') ? 'bg-blue-600 text-white shadow-md shadow-blue-500/20' : 'text-gray-600 hover:bg-gray-50'" class="flex items-center w-full p-2 text-sm transition-colors duration-75 rounded-lg pl-11 group">
                                    <Truck class="w-4 h-4 mr-2" />
                                    <span>Incoming Singkong</span>
                                </Link>
                            </li>
                            <li>
                                <Link :href="route('incoming.rmpm.dashboard')" :class="route().current('incoming.rmpm.dashboard') ? 'bg-blue-600 text-white shadow-md shadow-blue-500/20' : 'text-gray-600 hover:bg-gray-50'" class="flex items-center w-full p-2 text-sm transition-colors duration-75 rounded-lg pl-11 group">
                                    <Package class="w-4 h-4 mr-2" />
                                    <span>Incoming RMPM</span>
                                </Link>
                            </li>
                        </ul>
                    </li>

                    <!-- Surabaya Section -->
                    <li>
                        <button @click="isSurabayaOpen = !isSurabayaOpen" type="button" class="flex items-center w-full p-2 text-base text-gray-600 transition duration-75 rounded-lg group hover:bg-gray-50">
                            <Package class="flex-shrink-0 w-5 h-5 text-gray-400 transition duration-75 group-hover:text-blue-600" />
                            <span class="flex-1 ms-3 text-left rtl:text-right whitespace-nowrap font-bold">Surabaya</span>
                            <ChevronDown class="w-4 h-4 transition-transform" :class="{'rotate-180': isSurabayaOpen}" />
                        </button>
                        <ul v-show="isSurabayaOpen" class="py-2 space-y-2">
                            <li>
                                <Link :href="route('fg-surabaya.dashboard')" :class="route().current('fg-surabaya.dashboard') ? 'bg-blue-600 text-white shadow-md shadow-blue-500/20' : 'text-gray-600 hover:bg-gray-50'" class="flex items-center w-full p-2 text-sm transition-colors duration-75 rounded-lg pl-11 group">
                                    <ClipboardList class="w-4 h-4 mr-2" />
                                    <span>Formulasi</span>
                                </Link>
                            </li>
                            <li>
                                <Link :href="route('cs-noodle-sby.dashboard')" :class="route().current('cs-noodle-sby.dashboard') ? 'bg-blue-600 text-white shadow-md shadow-blue-500/20' : 'text-gray-600 hover:bg-gray-50'" class="flex items-center w-full p-2 text-sm transition-colors duration-75 rounded-lg pl-11 group">
                                    <Box class="w-4 h-4 mr-2" />
                                    <span>CS Noodle</span>
                                </Link>
                            </li>
                            <li>
                                <Link :href="route('cs-fg-sby.dashboard')" :class="route().current('cs-fg-sby.dashboard') ? 'bg-blue-600 text-white shadow-md shadow-blue-500/20' : 'text-gray-600 hover:bg-gray-50'" class="flex items-center w-full p-2 text-sm transition-colors duration-75 rounded-lg pl-11 group">
                                    <Box class="w-4 h-4 mr-2" />
                                    <span>CS FG-Sby</span>
                                </Link>
                            </li>
                        </ul>
                    </li>

                    <!-- Logout -->
                    <li class="pt-4 mt-4 border-t border-gray-200">
                        <button @click="logout" class="flex items-center w-full p-2 text-base font-medium text-red-600 transition duration-75 rounded-lg group hover:bg-red-50">
                            <LogOut class="flex-shrink-0 w-5 h-5 text-red-500 transition duration-75 group-hover:text-red-700" />
                            <span class="flex-1 ms-3 text-left">Logout</span>
                        </button>
                    </li>
                </ul>
            </div>
        </aside>

        <!-- Content Area -->
        <main :class="{'sm:ml-64': sidebarOpen}" class="p-4 mt-14 transition-all">
            <slot />
        </main>
    </div>
</template>
