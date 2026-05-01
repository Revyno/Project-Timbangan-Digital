<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — Ladang Lima Timbangan</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans min-h-screen bg-gradient-to-br from-blue-700 to-blue-500 flex items-center justify-center p-4 relative overflow-hidden">
    
    <!-- Animated background decorators -->
    <div class="fixed top-[-100px] left-[-100px] w-[500px] h-[500px] rounded-full bg-blue-400 blur-[80px] opacity-40 "></div>
    <div class="fixed bottom-[-100px] right-[-100px] w-[400px] h-[400px] rounded-full bg-blue-300 blur-[80px] opacity-40 "></div>

    <div class="w-full max-w-md bg-white/95 backdrop-blur-xl border border-white/50 rounded-2xl p-10 relative z-10 shadow-[0_20px_40px_rgba(0,0,0,0.1)]">
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center h-16 mx-auto mb-6">
                <img src="{{ asset('images/logo.webp') }}" alt="Ladang Lima Logo" class="h-full w-auto">
            </div>
            <p class="text-slate-800 font-bold text-lg">Dashboard Timbangan Digital V7.0</p>
            <p class="text-sm text-slate-500 mt-1">Silahkan masukkan email dan password Anda</p>
        </div>

        @if($errors->any())
            <div class="bg-red-100 border border-red-300 text-red-500 px-4 py-3 rounded-xl text-sm mb-4">
                @foreach($errors->all() as $err){{ $err }}<br>@endforeach
            </div>
        @endif

        @if(session('error'))
            <div class="bg-red-100 border border-red-300 text-red-500 px-4 py-3 rounded-xl text-sm mb-4">
                {{ session('error') }}
            </div>
        @endif

        <form method="POST" action="{{ route('login') }}" class="space-y-5">
            @csrf
            <div>
                <label for="email" class="block text-sm font-semibold text-slate-600 mb-2">Email</label>
                <input type="email" id="email" name="email" value="{{ old('email') }}" required autofocus 
                       class="w-full px-4 py-3 bg-slate-50 border border-slate-300 rounded-xl text-slate-800 text-sm focus:outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-500/20 transition-all">
            </div>

            <div>
                <label for="password" class="block text-sm font-semibold text-slate-600 mb-2">Password</label>
                <div class="relative flex items-center">
                    <input type="password" id="password" name="password" required
                           class="w-full px-4 py-3 pr-12 bg-slate-50 border border-slate-300 rounded-xl text-slate-800 text-sm focus:outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-500/20 transition-all">
                    <button type="button" id="togglePassword" class="absolute right-3 text-slate-500 hover:text-blue-500 focus:outline-none transition-colors" tabindex="-1" title="Tampilkan Password">
                        <svg id="eye-icon" class="w-6 h-6" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                          <!-- Default Icon: Eye (to show password) -->
                          <path stroke="currentColor" stroke-width="2" d="M21 12c0 1.2-4.03 6-9 6s-9-4.8-9-6c0-1.2 4.03-6 9-6s9 4.8 9 6Z"/>
                          <path stroke="currentColor" stroke-width="2" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/>
                        </svg>
                    </button>
                </div>
            </div>

            <div class="flex items-center">
                <label class="flex items-center gap-2 cursor-pointer text-sm font-medium text-slate-600">
                    <input type="checkbox" name="remember" id="remember" {{ old('remember') ? 'checked' : '' }}
                           class="w-4 h-4 cursor-pointer accent-blue-700 text-blue-600 border-slate-300 rounded focus:ring-blue-500">
                    Ingat Saya
                </label>
            </div>

            <button type="submit" class="w-full py-3 px-4 bg-blue-700 hover:bg-blue-800 text-white border-none rounded-xl text-sm font-semibold cursor-pointer transition-all transform hover:-translate-y-0.5 shadow-lg hover:shadow-blue-700/30">
                Masuk ke Dashboard &rarr;
            </button>
        </form>
    </div>

    <script>
        const togglePassword = document.querySelector('#togglePassword');
        const password = document.querySelector('#password');
        const eyeIcon = document.querySelector('#eye-icon');

        // Flowbite Eye SVG (to show password)
        const iconEye = `<path stroke="currentColor" stroke-width="2" d="M21 12c0 1.2-4.03 6-9 6s-9-4.8-9-6c0-1.2 4.03-6 9-6s9 4.8 9 6Z"/><path stroke="currentColor" stroke-width="2" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/>`;
        // Flowbite Eye Slash SVG (to hide password)
        const iconEyeSlash = `<path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.933 13.909A4.357 4.357 0 0 1 3 12c0-1 4-6 9-6m7.6 3.8A5.068 5.068 0 0 1 21 12c0 1-3 6-9 6-.314 0-.62-.014-.918-.04M5 19 19 5m-4 7a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/>`;

        togglePassword.addEventListener('click', function () {
            const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
            password.setAttribute('type', type);
            
            if (type === 'password') {
                eyeIcon.innerHTML = iconEye;
                togglePassword.setAttribute('title', 'Tampilkan Password');
            } else {
                eyeIcon.innerHTML = iconEyeSlash;
                togglePassword.setAttribute('title', 'Sembunyikan Password');
            }
        });
    </script>
</body>
</html>
