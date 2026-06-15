<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Login - Sistem Penjadwalan ACO</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { font-family: 'Inter', sans-serif; }
        body {
            background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 35%, #bfdbfe 100%);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        .glass {
            background: rgba(255, 255, 255, 0.75);
            backdrop-filter: blur(18px);
            border: 1px solid rgba(255, 255, 255, 0.7);
            box-shadow: 0 10px 30px rgba(37, 99, 235, 0.08);
        }
        .glow {
            box-shadow: 0 10px 25px rgba(37, 99, 235, 0.18);
        }
        .input-field {
            background: rgba(255, 255, 255, 0.8);
            border: 1px solid #e2e8f0;
            color: #1e293b;
            transition: all 0.3s ease;
            border-radius: 0.75rem;
            padding: 0.75rem 1rem;
            width: 100%;
        }
        .input-field:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1);
            outline: none;
            background: white;
        }
        .input-field::placeholder { color: #94a3b8; }
        .btn-login {
            transition: all 0.3s ease;
            background: #2563eb;
            color: white;
            font-weight: 700;
            padding: 0.75rem 1rem;
            border-radius: 0.75rem;
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            box-shadow: 0 10px 25px rgba(37, 99, 235, 0.18);
        }
        .btn-login:hover {
            background: #1d4ed8;
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(37, 99, 235, 0.35);
        }
        .nav-blur {
            background: rgba(255, 255, 255, 0.92);
            backdrop-filter: blur(14px);
            border-bottom: 2px solid rgba(37, 99, 235, 0.15);
            box-shadow: 0 4px 20px rgba(37, 99, 235, 0.06);
        }
    </style>
</head>

<body class="antialiased">

    <!-- Navbar -->


    <!-- Form Login -->
    <div class="flex-1 flex items-center justify-center px-4 py-12">
        <div class="max-w-md w-full glass rounded-3xl overflow-hidden glow">
            <!-- Header -->
            <div class="p-8 text-center border-b border-blue-100/50">
                <div class="flex justify-center mb-4">
                    <img src="{{ asset('image/logo.png') }}" alt="Logo" class="w-20 h-20 object-contain drop-shadow-[0_0_12px_rgba(59,130,246,0.3)]">
                </div>
                <h2 class="text-3xl font-extrabold text-blue-900 tracking-wide">
                    Login Sistem
                </h2>
                <p class="text-slate-500 text-sm mt-2">
                    Masukkan email dan password Anda
                </p>
            </div>

            <!-- Form -->
            <div class="p-8">
                @if ($errors->any())
                    <div class="bg-red-50 border border-red-200 text-red-700 p-4 mb-6 rounded-2xl text-sm flex items-center gap-2">
                        <i class="fas fa-exclamation-triangle"></i>
                        {{ $errors->first() }}
                    </div>
                @endif

                <form method="POST" action="{{ route('login') }}">
                    @csrf

                    <div class="mb-5">
                        <label class="block text-slate-700 text-sm font-semibold mb-2">
                            <i class="fas fa-envelope text-blue-500 mr-1.5"></i> Email
                        </label>
                        <input type="email" name="email" value="{{ old('email') }}" required autofocus
                            placeholder="admin@example.com" class="input-field">
                    </div>

                    <div class="mb-6">
                        <label class="block text-slate-700 text-sm font-semibold mb-2">
                            <i class="fas fa-lock text-blue-500 mr-1.5"></i> Password
                        </label>
                        <input type="password" name="password" required placeholder="Masukkan password" class="input-field">
                    </div>

                    <button type="submit" class="btn-login">
                        <i class="fas fa-sign-in-alt"></i>
                        Login
                    </button>

                    <a href="{{ route('welcome') }}" class="mt-4 block w-full border border-gray-200 text-slate-600 font-semibold py-3 px-4 rounded-xl flex items-center justify-center gap-2 hover:bg-gray-50 transition text-sm">
                        <i class="fas fa-arrow-left"></i>
                        Kembali ke Halaman Utama
                    </a>
                </form>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="border-t border-blue-100/50 py-6 text-center text-slate-500">
        <p class="font-semibold text-sm">&copy; {{ date('Y') }} Sistem Penjadwalan Mata Kuliah Otomatis</p>
        <p class="text-xs mt-1">Fakultas Teknik Universitas Wiraraja Sumenep Madura</p>
    </footer>

</body>
</html>
