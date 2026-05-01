<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Timbangan IoT') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link href="https://cdnjs.cloudflare.com/ajax/libs/flowbite/2.3.0/flowbite.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.css" rel="stylesheet">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="font-sans antialiased bg-gray-100">
    <nav class="fixed top-0 z-50 w-full bg-blue-700 border-b border-blue-800 shadow-sm">
        <div class="px-3 py-3 lg:px-5 lg:pl-3">
            <div class="flex items-center justify-between">
                <div class="flex items-center justify-start">
                    <button data-drawer-target="logo-sidebar" data-drawer-toggle="logo-sidebar" aria-controls="logo-sidebar" type="button" class="inline-flex items-center p-2 text-sm text-blue-100 rounded-lg sm:hidden hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-300">
                        <span class="sr-only">Open sidebar</span>
                        <svg class="w-6 h-6" aria-hidden="true" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                           <path clip-rule="evenodd" fill-rule="evenodd" d="M2 4.75A.75.75 0 012.75 4h14.5a.75.75 0 010 1.5H2.75A.75.75 0 012 4.75zm0 10.5a.75.75 0 01.75-.75h7.5a.75.75 0 010 1.5h-7.5a.75.75 0 01-.75-.75zM2 10a.75.75 0 01.75-.75h14.5a.75.75 0 010 1.5H2.75A.75.75 0 012 10z"></path>
                        </svg>
                    </button>
                    <a href="{{ route('dashboard') }}" class="flex items-center ms-2 md:me-24 gap-2">
                        <img src="{{ asset('images/logo.webp') }}" alt="Ladang Lima" class="h-8 w-auto brightness-0 invert">
                    </a>
                </div>
                <div class="flex items-center">
                    <div class="flex items-center ms-3">
                        <div class="flex items-center gap-4">
                            <!-- Notification Bell -->
                            <div class="relative">
                                <button type="button" id="notificationButton" class="p-2 text-blue-100 rounded-lg hover:bg-blue-600 transition-all">
                                    <svg class="w-6 h-6" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M17.133 12.632v-1.8a5.406 5.406 0 0 0-4.154-5.262.955.955 0 0 0 .021-.106V3.1a1 1 0 0 0-2 0v2.364a.955.955 0 0 0 .021.106 5.406 5.406 0 0 0-4.154 5.262v1.8C6.867 15.018 5 15.614 5 16.807 5 17.4 5 18 5.538 18h12.924C19 18 19 17.4 19 16.807c0-1.193-1.867-1.789-1.867-4.175ZM8.823 19a3.453 3.453 0 0 0 6.354 0H8.823Z"/>
                                    </svg>
                                    <div id="notification-dot" class="absolute top-2 right-2 w-3 h-3 bg-red-500 border-2 border-blue-700 rounded-full hidden"></div>
                                </button>
                            </div>

                            <div class="text-right hidden md:block">
                                <p class="text-sm font-bold text-white">{{ auth()->user()->name }}</p>
                                <p class="text-xs text-blue-200 capitalize">{{ str_replace('_', ' ', auth()->user()->role) }} - Shift {{ auth()->user()->shift ?? '-' }}</p>
                            </div>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="text-white bg-red-600 hover:bg-red-700 focus:ring-4 focus:ring-red-300 font-medium rounded-lg text-xs px-4 py-2 focus:outline-none shadow-sm transition-all">Logout</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <aside id="logo-sidebar" class="fixed top-0 left-0 z-40 w-64 h-screen pt-20 transition-transform -translate-x-full bg-white border-r border-gray-200 sm:translate-x-0 shadow-sm" aria-label="Sidebar">
        <div class="h-full px-3 pb-4 overflow-y-auto bg-white">
            <ul class="space-y-2 font-medium">
                <li>
                    <a href="{{ route('dashboard') }}" class="flex items-center p-2 rounded-lg group transition-colors {{ request()->routeIs('dashboard') ? 'bg-blue-600 text-white shadow-md shadow-blue-500/20' : 'text-gray-600 hover:bg-gray-50' }}">
                        <svg class="w-5 h-5 transition duration-75 {{ request()->routeIs('dashboard') ? 'text-white' : 'text-gray-400 group-hover:text-blue-600' }}" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 22 21">
                            <path d="M16.975 11H10V4.025a1 1 0 0 0-1.066-.998 8.5 8.5 0 1 0 9.039 9.039.999.999 0 0 0-1-1.066h.002Z"/>
                            <path d="M12.5 0c-.157 0-.311.01-.565.027A1 1 0 0 0 11 1.02V10h8.975a1 1 0 0 0 1-.935c.013-.188.028-.374.028-.565A8.51 8.51 0 0 0 12.5 0Z"/>
                        </svg>
                        <span class="ms-3">Overview</span>
                    </a>
                </li>

                <!-- Pasuruan Dropdown -->
                <li>
                    <button type="button" class="flex items-center w-full p-2 text-base text-gray-600 transition duration-75 rounded-lg group hover:bg-gray-50" aria-controls="dropdown-pasuruan" data-collapse-toggle="dropdown-pasuruan">
                        <svg class="flex-shrink-0 w-5 h-5 text-gray-400 transition duration-75 group-hover:text-blue-600" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 18">
                            <path d="M14 2a3.963 3.963 0 0 0-1.4.267 6.439 6.439 0 0 1-1.331 6.638A4 4 0 1 0 14 2Zm1 9h-1.264A6.957 6.957 0 0 1 15 15v2a2.97 2.97 0 0 1-.184 1H19a1 1 0 0 0 1-1v-1a5.006 5.006 0 0 0-5-5ZM6.5 9a4.5 4.5 0 1 0 0-9 4.5 4.5 0 0 0 0 9ZM8 10H5a5.006 5.006 0 0 0-5 5v2a1 1 0 0 0 1 1h11a1 1 0 0 0 1-1v-2a5.006 5.006 0 0 0-5-5Z"/>
                        </svg>
                        <span class="flex-1 ms-3 text-left rtl:text-right whitespace-nowrap font-bold">Pasuruan</span>
                        <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 10 6">
                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 4 4 4-4"/>
                        </svg>
                    </button>
                    @php
                        $isPasuruanActive = request()->routeIs('dashboard') || request()->routeIs('fg-psn.*') || request()->routeIs('incoming.singkong.*') || request()->routeIs('incoming.rmpm.*') && !request()->routeIs('fg-surabaya.*');
                    @endphp
                    <ul id="dropdown-pasuruan" class="{{ $isPasuruanActive ? '' : 'hidden' }} py-2 space-y-2">
                        <li>
                            <a href="{{ route('fg.dashboard') }}" class="flex items-center w-full p-2 text-sm transition-colors duration-75 rounded-lg pl-11 group {{ request()->routeIs('fg.dashboard') ? 'bg-blue-600 text-white shadow-md shadow-blue-500/20' : 'text-gray-600 hover:bg-gray-50' }}">
                                <svg class="w-5 h-5 transition duration-75 {{ request()->routeIs('fg.dashboard') ? 'text-white' : 'text-gray-400 group-hover:text-blue-600' }}" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25ZM6.75 12h.008v.008H6.75V12Zm0 3h.008v.008H6.75V15Zm0 3h.008v.008H6.75V18Z" />
                                </svg>
                                <span class="ms-3">Formulasi</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('fg-psn.dashboard') }}" class="flex items-center w-full p-2 text-sm transition-colors duration-75 rounded-lg pl-11 group {{ request()->routeIs('fg-psn.*') ? 'bg-blue-600 text-white shadow-md shadow-blue-500/20' : 'text-gray-600 hover:bg-gray-50' }}">
                                <svg class="w-5 h-5 transition duration-75 {{ request()->routeIs('fg-psn.*') ? 'text-white' : 'text-gray-400 group-hover:text-blue-600' }}" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 7.5l-9-5.25L3 7.5m18 0l-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9" />
                                </svg>
                                <span class="ms-3">Finished Goods</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('incoming.singkong.dashboard') }}" class="flex items-center w-full p-2 text-sm transition-colors duration-75 rounded-lg pl-11 group {{ request()->routeIs('incoming.singkong.*') ? 'bg-blue-600 text-white shadow-md shadow-blue-500/20' : 'text-gray-600 hover:bg-gray-50' }}">
                                <svg class="w-5 h-5 transition duration-75 {{ request()->routeIs('incoming.singkong.*') ? 'text-white' : 'text-gray-400 group-hover:text-blue-600' }}" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 0 1-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 0 0-3.213-9.193 2.056 2.056 0 0 0-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.987-1.106a48.554 48.554 0 0 0-10.026 0 1.106 1.106 0 0 0-.987 1.106v7.635m12-6.622v5.25m0 0h.99m-0.99 0 3.141 1.571a1.125 1.125 0 0 1 .632 1.01V15m0 0h-4.5" />
                                </svg>
                                <span class="ms-3">Incoming Singkong</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('incoming.rmpm.dashboard') }}" class="flex items-center w-full p-2 text-sm transition-colors duration-75 rounded-lg pl-11 group {{ request()->routeIs('incoming.rmpm.*') ? 'bg-blue-600 text-white shadow-md shadow-blue-500/20' : 'text-gray-600 hover:bg-gray-50' }}">
                                <svg class="w-5 h-5 transition duration-75 {{ request()->routeIs('incoming.rmpm.*') ? 'text-white' : 'text-gray-400 group-hover:text-blue-600' }}" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 0 1-2.247 2.118H6.622a2.25 2.25 0 0 1-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125Z" />
                                </svg>
                                <span class="ms-3">Incoming RMPM</span>
                            </a>
                        </li>
                    </ul>
                </li>

                <!-- Surabaya Dropdown -->
                <li>
                    <button type="button" class="flex items-center w-full p-2 text-base text-gray-600 transition duration-75 rounded-lg group hover:bg-gray-50" aria-controls="dropdown-surabaya" data-collapse-toggle="dropdown-surabaya">
                         <svg class="flex-shrink-0 w-5 h-5 text-gray-400 transition duration-75 group-hover:text-blue-600" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 18">
                            <path d="M14 2a3.963 3.963 0 0 0-1.4.267 6.439 6.439 0 0 1-1.331 6.638A4 4 0 1 0 14 2Zm1 9h-1.264A6.957 6.957 0 0 1 15 15v2a2.97 2.97 0 0 1-.184 1H19a1 1 0 0 0 1-1v-1a5.006 5.006 0 0 0-5-5ZM6.5 9a4.5 4.5 0 1 0 0-9 4.5 4.5 0 0 0 0 9ZM8 10H5a5.006 5.006 0 0 0-5 5v2a1 1 0 0 0 1 1h11a1 1 0 0 0 1-1v-2a5.006 5.006 0 0 0-5-5Z"/>
                        </svg><span class="flex-1 ms-3 text-left rtl:text-right whitespace-nowrap font-bold">Surabaya</span>
                        <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 10 6">
                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 4 4 4-4"/>
                        </svg>
                    </button>
                    @php
                        $isSbyActive = request()->routeIs('fg-surabaya.*') || request()->routeIs('cs-noodle-sby.*') || request()->routeIs('cs-fg-sby.*');
                    @endphp
                    <ul id="dropdown-surabaya" class="{{ $isSbyActive ? '' : 'hidden' }} py-2 space-y-2">
                        <li>
                            <a href="{{ route('fg-surabaya.dashboard') }}" class="flex items-center w-full p-2 text-sm transition-colors duration-75 rounded-lg pl-11 group {{ request()->routeIs('fg-surabaya.*') ? 'bg-blue-600 text-white shadow-md shadow-blue-500/20' : 'text-gray-600 hover:bg-gray-50' }}">
                                <svg class="w-5 h-5 transition duration-75 {{ request()->routeIs('fg-surabaya.*') ? 'text-white' : 'text-gray-400 group-hover:text-blue-600' }}" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25ZM6.75 12h.008v.008H6.75V12Zm0 3h.008v.008H6.75V15Zm0 3h.008v.008H6.75V18Z" />
                                </svg>
                                <span class="ms-3">Formulasi</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('cs-noodle-sby.dashboard') }}" class="flex items-center w-full p-2 text-sm transition-colors duration-75 rounded-lg pl-11 group {{ request()->routeIs('cs-noodle-sby.*') ? 'bg-blue-600 text-white shadow-md shadow-blue-500/20' : 'text-gray-600 hover:bg-gray-50' }}">
                                <svg class="w-5 h-5 transition duration-75 {{ request()->routeIs('cs-noodle-sby.*') ? 'text-white' : 'text-gray-400 group-hover:text-blue-600' }}" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.568 3H5.25A2.25 2.25 0 0 0 3 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 0 0 5.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 0 0 9.568 3Z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 6h.008v.008H6V6Z" />
                                </svg>
                                <span class="ms-3">CS Noodle</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('cs-fg-sby.dashboard') }}" class="flex items-center w-full p-2 text-sm transition-colors duration-75 rounded-lg pl-11 group {{ request()->routeIs('cs-fg-sby.*') ? 'bg-blue-600 text-white shadow-md shadow-blue-500/20' : 'text-gray-600 hover:bg-gray-50' }}">
                                <svg class="w-5 h-5 transition duration-75 {{ request()->routeIs('cs-fg-sby.*') ? 'text-white' : 'text-gray-400 group-hover:text-blue-600' }}" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 7.5l-9-5.25L3 7.5m18 0l-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9" />
                                </svg>
                                <span class="ms-3">CS FG-Sby</span>
                            </a>
                        </li>
                    </ul>
                </li>
                <!-- Logout -->
                <li class="pt-4 mt-4 space-y-2 border-t border-gray-200">
                    <form method="POST" action="{{ route('logout') }}" class="w-full">
                        @csrf
                        <button type="submit" class="flex items-center w-full p-2 text-base font-medium text-red-600 transition duration-75 rounded-lg group hover:bg-red-50">
                            <svg class="flex-shrink-0 w-5 h-5 text-red-500 transition duration-75 group-hover:text-red-700" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 18 16">
                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M1 8h11m0 0L8 4m4 4-4 4m4-11h3a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2h-3"/>
                            </svg>
                            <span class="flex-1 ms-3 text-left rtl:text-right whitespace-nowrap">Logout</span>
                        </button>
                    </form>
                </li>
            </ul>
        </div>
    </aside>

    <div class="p-4 mt-14 sm:ml-64">
        @yield('content')
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/flowbite/2.3.0/flowbite.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    @auth
    <script type="module">
        window.addEventListener('DOMContentLoaded', () => {
            if (window.Echo) {
                const userRole = "{{ Auth::user()->role }}";
                const userType = "{{ Auth::user()->tipe }}";
                
                const channels = userRole === 'admin' 
                    ? ['fg_psn', 'incoming_singkong', 'incoming_rmpm', 'fg_surabaya', 'cs_noodle_sby', 'cs_fg_sby']
                    : [userType];

                channels.forEach(channel => {
                    console.log('Listening to IoT channel: iot-weights.' + channel);
                    window.Echo.channel('iot-weights.' + channel)
                        .listen('.WeightReceived', (e) => {
                            console.log('Weight received via WebSocket on ' + channel + ':', e);
                            
                            // Show notification dot
                            const dot = document.getElementById('notification-dot');
                            if (dot) dot.classList.remove('hidden');

                            // SweetAlert Notification
                            Swal.fire({
                                icon: 'success',
                                title: 'Data Masuk!',
                                text: `Berat: ${e.weight} kg (${e.product})`,
                                timer: 3000,
                                showConfirmButton: false,
                                toast: true,
                                position: 'top-end'
                            });

                            // Reload page to show new data
                            setTimeout(() => {
                                if (window.Livewire) {
                                    window.Livewire.dispatch('refresh');
                                    setTimeout(() => window.location.reload(), 500);
                                } else {
                                    window.location.reload();
                                }
                            }, 1000);
                        });
                });
            }


            // Hide dot on click
            const notifBtn = document.getElementById('notificationButton');
            const notifDot = document.getElementById('notification-dot');
            if (notifBtn && notifDot) {
                notifBtn.addEventListener('click', () => {
                    notifDot.classList.add('hidden');
                });
            }
        });
    </script>
    @endauth

    @stack('scripts')
</body>
</html>
