<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — Timbangan Adruino</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
            background: #0f172a;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
            position: relative;
            overflow: hidden;
        }
        body::before, body::after {
            content: '';
            position: fixed;
            border-radius: 50%;
            filter: blur(80px);
            opacity: .3;
            animation: float 8s ease-in-out infinite alternate;
        }
        body::before {
            width: 500px; height: 500px;
            background: radial-gradient(circle, #6366f1, transparent);
            top: -100px; left: -100px;
        }
        body::after {
            width: 400px; height: 400px;
            background: radial-gradient(circle, #22d3ee, transparent);
            bottom: -100px; right: -100px;
            animation-delay: -4s;
        }
        @keyframes float { from{transform:translate(0,0);} to{transform:translate(30px,30px);} }

        .login-box {
            width: 100%; max-width: 400px;
            background: rgba(30,41,59,.85);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(99,102,241,.25);
            border-radius: 20px;
            padding: 2.5rem 2rem;
            position: relative;
            z-index: 1;
        }
        .logo-area { text-align: center; margin-bottom: 2rem; }
        .logo-circle {
            width: 72px; height: 72px;
            background: linear-gradient(135deg, #6366f1, #22d3ee);
            border-radius: 20px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            margin-bottom: 1rem;
            box-shadow: 0 8px 24px rgba(99,102,241,.4);
        }
        h1 { font-size: 1.4rem; font-weight: 700; color: #f1f5f9; }
        p  { font-size: .85rem; color: #94a3b8; margin-top: .3rem; }

        .form-group { margin-bottom: 1.1rem; }
        label { display: block; font-size: .8rem; font-weight: 600; color: #94a3b8; margin-bottom: .4rem; }
        input[type=email], input[type=password] {
            width: 100%;
            padding: .7rem 1rem;
            background: rgba(15,23,42,.7);
            border: 1px solid #334155;
            border-radius: 10px;
            color: #f1f5f9;
            font-size: .9rem;
            font-family: inherit;
        }
        input:focus { outline: none; border-color: #6366f1; box-shadow: 0 0 0 3px rgba(99,102,241,.2); }

        .btn-login {
            width: 100%;
            padding: .8rem;
            background: linear-gradient(135deg, #6366f1, #4f46e5);
            color: #fff;
            border: none;
            border-radius: 10px;
            font-size: .95rem;
            font-weight: 600;
            cursor: pointer;
            transition: all .2s;
        }
        .btn-login:hover { filter: brightness(1.1); transform: translateY(-1px); box-shadow: 0 8px 20px rgba(99,102,241,.4); }

        .alert-error {
            background: rgba(239,68,68,.12);
            border: 1px solid rgba(239,68,68,.3);
            color: #f87171;
            padding: .7rem 1rem;
            border-radius: 10px;
            font-size: .8rem;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
<div class="login-box">
    <div class="logo-area">
        {{-- <div class="logo-circle"></div> --}}
        <h1>Pt Ladang Lima Surabaya</h1>
        <p>WiFi Scale System V7.0 — Silahkan login</p>
    </div>

    @if($errors->any())
        <div class="alert-error">
            @foreach($errors->all() as $err){{ $err }}@endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('login') }}">
        @csrf
        <div class="form-group">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" value="{{ old('email') }}" required autofocus>
        </div>
        <div class="form-group">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" required>
        </div>
        <button type="submit" class="btn-login">Masuk ke Dashboard →</button>
      {{-- forget password --}}
      {{-- <p style="text-align: center; margin-top: 1rem;">
        <a href="{{ route('password.request') }}" style="color: #94a3b8; font-size: .8rem; text-decoration: none;">Lupa password?</a>
      </p> --}}
      {{-- <a href="{{ route('password.request') }}" style="color: #94a3b8; font-size: .8rem; text-decoration: none;">Lupa password?</a> --}}
    </form>
</div>
</body>
</html>
