<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta http-equiv="Cache-Control" content="no-store" />
    <meta http-equiv="Pragma" content="no-cache" />
    <meta http-equiv="Expires" content="0" />

    <title>@yield('title', 'Dashboard')</title>

    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        * { font-family: 'Inter', sans-serif; }
        body {
            background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 35%, #bfdbfe 100%);
            min-height: 100vh;
        }

        ::-webkit-scrollbar { width: 5px; height: 5px; }
        ::-webkit-scrollbar-track { background: #e2e8f0; }
        ::-webkit-scrollbar-thumb { background: #94a3b8; border-radius: 10px; }

        /* Sidebar */
        aside { transition: transform 0.3s ease-in-out; }
        @media (max-width: 768px) {
            aside { transform: translateX(-100%); position: fixed; z-index: 50; height: 100vh; width: 260px; }
            aside.open { transform: translateX(0); }
        }

        /* Glass effect (sama dengan welcome) */
        .glass-sidebar {
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(18px);
            border-right: 1px solid rgba(255, 255, 255, 0.7);
            box-shadow: 0 10px 30px rgba(37, 99, 235, 0.08);
        }

        .glass-topbar {
            background: rgba(255, 255, 255, 0.92);
            backdrop-filter: blur(14px);
            border-bottom: 2px solid rgba(37, 99, 235, 0.15);
            box-shadow: 0 4px 20px rgba(37, 99, 235, 0.06);
        }

        .menu-item {
            transition: all .2s ease;
            border-radius: 10px;
            color: #475569;
        }
        .menu-item:hover {
            background: rgba(37, 99, 235, 0.08);
            color: #1e40af;
        }
        .menu-active {
            background: rgba(37, 99, 235, 0.12);
            color: #1d4ed8;
            font-weight: 600;
            border-left: 3px solid #3b82f6;
        }

        .section-label {
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            color: #94a3b8;
            padding: 0 12px;
            margin-top: 20px;
            margin-bottom: 6px;
            font-weight: 600;
        }

        .card-hover { transition: all .35s ease; }
        .card-hover:hover {
            transform: translateY(-6px);
            box-shadow: 0 20px 40px rgba(37, 99, 235, 0.12);
        }

        /* Modal */
        .modal-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 1000; justify-content: center; align-items: flex-start; padding-top: 5rem; overflow-y: auto; }
        .modal-overlay.active { display: flex; }
        .modal-box { background: white; border-radius: 16px; box-shadow: 0 25px 50px rgba(0,0,0,0.15); width: 100%; max-width: 480px; margin: 0 1rem; }
        .modal-box.wide { max-width: 640px; }

        /* Tombol - konsisten dengan welcome page */
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            border-radius: 0.75rem;
            font-weight: 600;
            font-size: 0.875rem;
            transition: all 0.3s ease;
            cursor: pointer;
            border: none;
        }
        .btn:hover { transform: translateY(-2px); }
        .btn-primary {
            background: #2563eb;
            color: white;
            box-shadow: 0 10px 25px rgba(37, 99, 235, 0.18);
        }
        .btn-primary:hover { background: #1d4ed8; box-shadow: 0 10px 25px rgba(37, 99, 235, 0.35); }
        .btn-success {
            background: #059669;
            color: white;
            box-shadow: 0 8px 20px rgba(5, 150, 105, 0.18);
        }
        .btn-success:hover { background: #047857; }
        .btn-danger {
            background: #dc2626;
            color: white;
        }
        .btn-danger:hover { background: #b91c1c; }
        .btn-warning {
            background: #d97706;
            color: white;
        }
        .btn-warning:hover { background: #b45309; }
        .btn-ghost {
            background: white;
            color: #475569;
            border: 1px solid #e2e8f0;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }
        .btn-ghost:hover { background: #f8fafc; border-color: #cbd5e1; }
        .btn-sm { padding: 0.35rem 0.75rem; font-size: 0.75rem; border-radius: 0.5rem; }

        /* Tabel */
        .table-container { @apply overflow-x-auto rounded-xl border border-gray-200 bg-white; }
        table { @apply min-w-full divide-y divide-gray-200; }
        thead { @apply bg-gray-50; }
        th { @apply px-5 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider; }
        tbody { @apply bg-white divide-y divide-gray-100; }
        td { @apply px-5 py-4 text-sm text-gray-700; }
        tr:hover { @apply bg-blue-50/50; }

        /* Badge */
        .badge { @apply inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium; }
        .badge-green { @apply bg-green-100 text-green-700; }
        .badge-red { @apply bg-red-100 text-red-700; }
        .badge-blue { @apply bg-blue-100 text-blue-700; }
        .badge-yellow { @apply bg-yellow-100 text-yellow-700; }
        .badge-purple { @apply bg-purple-100 text-purple-700; }
        .badge-indigo { @apply bg-indigo-100 text-indigo-700; }

        /* Kertas putih + bayangan biru (kayak welcome) */
        .card {
            background: white;
            border-radius: 1rem;
            box-shadow: 0 10px 30px rgba(37, 99, 235, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.7);
        }

        .empty-state { @apply text-center py-12 text-gray-400; }
        .empty-state i { @apply text-5xl mb-3; }

        /* Input field style */
        input, select, textarea {
            @apply border border-gray-300 rounded-lg px-4 py-2.5 w-full;
            transition: all 0.3s ease;
        }
        input:focus, select:focus, textarea:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1);
            outline: none;
        }
    </style>
    @stack('styles')
</head>

<body class="antialiased">
    <div class="flex h-screen overflow-hidden">

        <!-- Sidebar - glass effect kayak welcome -->
        <aside class="w-[260px] flex flex-col glass-sidebar shrink-0">

            <!-- Logo -->
            <div class="p-5 border-b border-blue-100/50">
                <div class="flex items-center gap-3">
                    <img src="{{ asset('image/logo.png') }}" alt="Logo" class="w-10 h-10 object-contain">
                    <div>
                        <span class="text-sm font-bold text-blue-900 tracking-tight">Sistem Penjadwalan</span>
                        <p class="text-[10px] text-slate-500">Fakultas Teknik</p>
                    </div>
                </div>
            </div>

            <!-- Navigation -->
            <nav class="flex-1 px-3 py-4 overflow-y-auto space-y-0.5">

                {{-- ADMIN --}}
                @if (auth()->user()->role == 'admin')
                    <div class="section-label">Utama</div>
                    <a href="{{ route('dashboard') }}" class="menu-item flex items-center px-4 py-2.5 text-sm @if(request()->routeIs('dashboard')) menu-active @endif">
                        <i class="fas fa-tachometer-alt w-5 mr-3 text-center text-blue-500"></i> Dashboard
                    </a>

                    <div class="section-label">Master Data</div>
                    <a href="{{ route('prodi.index') }}" class="menu-item flex items-center px-4 py-2.5 text-sm @if(request()->routeIs('prodi.*')) menu-active @endif">
                        <i class="fas fa-building w-5 mr-3 text-center text-blue-500"></i> Program Studi
                    </a>
                    <a href="{{ route('angkatan.index') }}" class="menu-item flex items-center px-4 py-2.5 text-sm @if(request()->routeIs('angkatan.*')) menu-active @endif">
                        <i class="fas fa-layer-group w-5 mr-3 text-center text-blue-500"></i> Angkatan
                    </a>
                    <a href="{{ route('ruangan.index') }}" class="menu-item flex items-center px-4 py-2.5 text-sm @if(request()->routeIs('ruangan.*')) menu-active @endif">
                        <i class="fas fa-door-open w-5 mr-3 text-center text-blue-500"></i> Ruangan
                    </a>
                    <a href="{{ route('dosen.index') }}" class="menu-item flex items-center px-4 py-2.5 text-sm @if(request()->routeIs('dosen.*')) menu-active @endif">
                        <i class="fas fa-chalkboard-user w-5 mr-3 text-center text-blue-500"></i> Dosen
                    </a>
                    <a href="{{ route('matakuliah.index') }}" class="menu-item flex items-center px-4 py-2.5 text-sm @if(request()->routeIs('matakuliah.*')) menu-active @endif">
                        <i class="fas fa-book w-5 mr-3 text-center text-blue-500"></i> Mata Kuliah
                    </a>

                    <div class="section-label">Pengaturan</div>
                    <a href="{{ route('slot.index') }}" class="menu-item flex items-center px-4 py-2.5 text-sm @if(request()->routeIs('slot.*')) menu-active @endif">
                        <i class="fas fa-clock w-5 mr-3 text-center text-blue-500"></i> Slot Waktu
                    </a>
                    <a href="{{ route('user.index') }}" class="menu-item flex items-center px-4 py-2.5 text-sm @if(request()->routeIs('user.*')) menu-active @endif">
                        <i class="fas fa-users-cog w-5 mr-3 text-center text-blue-500"></i> Manajemen User
                    </a>

                    <div class="section-label">Audit</div>
                    <a href="{{ route('admin.audit') }}" class="menu-item flex items-center px-4 py-2.5 text-sm @if(request()->routeIs('admin.audit')) menu-active @endif">
                        <i class="fas fa-clipboard-check w-5 mr-3 text-center text-blue-500"></i> Pengecekan Jadwal
                    </a>
                @endif

                {{-- KAPRODI --}}
                @if (auth()->user()->role == 'kaprodi')
                    <div class="section-label">Utama</div>
                    <a href="{{ route('dashboardkaprodi') }}" class="menu-item flex items-center px-4 py-2.5 text-sm @if(request()->routeIs('dashboardkaprodi')) menu-active @endif">
                        <i class="fas fa-tachometer-alt w-5 mr-3 text-center text-blue-500"></i> Dashboard
                    </a>

                    <div class="section-label">Data Prodi</div>
                    <a href="{{ route('kelas.index') }}" class="menu-item flex items-center px-4 py-2.5 text-sm @if(request()->routeIs('kelas.*')) menu-active @endif">
                        <i class="fas fa-users w-5 mr-3 text-center text-blue-500"></i> Kelas
                    </a>
                    <a href="{{ route('dosen.index') }}" class="menu-item flex items-center px-4 py-2.5 text-sm @if(request()->routeIs('dosen.*')) menu-active @endif">
                        <i class="fas fa-chalkboard-user w-5 mr-3 text-center text-blue-500"></i> Dosen
                    </a>
                    <a href="{{ route('matakuliah.index') }}" class="menu-item flex items-center px-4 py-2.5 text-sm @if(request()->routeIs('matakuliah.*')) menu-active @endif">
                        <i class="fas fa-book w-5 mr-3 text-center text-blue-500"></i> Mata Kuliah
                    </a>
                @endif

                {{-- Semua Role --}}
                <div class="section-label">Jadwal</div>
                <a href="{{ route('jadwalauth.index') }}" class="menu-item flex items-center px-4 py-2.5 text-sm @if(request()->routeIs('jadwal*')) menu-active @endif">
                    <i class="fas fa-calendar-alt w-5 mr-3 text-center text-blue-500"></i> Jadwal Kuliah
                </a>
            </nav>

            <!-- Bottom -->
            <div class="p-3 border-t border-blue-100/50 space-y-1">
                <div class="px-4 py-2 text-xs text-slate-500 truncate flex items-center gap-2">
                    <span class="w-6 h-6 rounded-full bg-blue-600 flex items-center justify-center text-white text-[10px] font-bold shadow-sm">
                        {{ substr(auth()->user()->name ?? 'U', 0, 1) }}
                    </span>
                    <span class="truncate">{{ auth()->user()->name ?? 'User' }}</span>
                    <span class="capitalize opacity-60">({{ auth()->user()->role ?? '' }})</span>
                </div>
                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button type="submit" class="flex items-center w-full px-4 py-2.5 text-sm text-slate-500 rounded-lg hover:bg-red-50 hover:text-red-600 transition">
                        <i class="fas fa-sign-out-alt w-5 mr-3 text-center text-red-400"></i> Logout
                    </button>
                </form>
            </div>
        </aside>

        <!-- Main -->
        <div class="flex-1 flex flex-col overflow-y-auto">

            <!-- Topbar - glass kayak welcome nav -->
            <header class="glass-topbar sticky top-0 z-10">
                <div class="px-6 py-3 flex justify-between items-center">
                    <div class="flex items-center">
                        <button id="sidebarToggle" class="text-blue-500 hover:text-blue-700 mr-4 md:hidden">
                            <i class="fas fa-bars text-lg"></i>
                        </button>
                        <div>
                            <p class="text-base font-bold text-blue-900">@yield('header', 'Dashboard')</p>
                            <p class="text-xs text-slate-500">Sistem Penjadwalan Mata Kuliah</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <div class="text-right hidden sm:block">
                            <p class="text-sm font-semibold text-slate-700">{{ auth()->user()->name ?? 'User' }}</p>
                            <p class="text-xs text-slate-400 capitalize">{{ auth()->user()->role ?? '' }}</p>
                        </div>
                        <div class="w-9 h-9 rounded-full bg-blue-600 flex items-center justify-center text-white text-sm font-semibold shadow-lg" style="box-shadow: 0 4px 12px rgba(37,99,235,0.35);">
                            {{ substr(auth()->user()->name ?? 'U', 0, 1) }}
                        </div>
                    </div>
                </div>
            </header>

            <!-- Content -->
            <main class="p-6 flex-1">
                @yield('content')
            </main>

            <!-- Footer -->
            <footer class="bg-white/80 backdrop-blur-sm border-t border-blue-100/50 py-3">
                <div class="px-6 text-center text-xs text-slate-500">
                    &copy; {{ date('Y') }} Sistem Penjadwalan Mata Kuliah - ACO &mdash; Fakultas Teknik Universitas Wiraraja Sumenep Madura
                </div>
            </footer>
        </div>
    </div>

    <!-- Global Modal -->
    <div id="globalModal" class="modal-overlay">
        <div class="modal-box relative">
            <div class="flex justify-between items-center px-6 py-4 border-b border-gray-200">
                <h3 id="globalModalTitle" class="text-lg font-bold text-blue-900">Modal</h3>
                <button onclick="closeGlobalModal()" class="text-gray-400 hover:text-gray-600 transition p-1 hover:bg-gray-100 rounded-lg">
                    <i class="fas fa-times text-lg"></i>
                </button>
            </div>
            <div id="globalModalBody" class="px-6 py-4"></div>
        </div>
    </div>

    <!-- Global Alert -->
    <div id="globalAlert" class="fixed top-4 right-4 z-[9999] space-y-2"></div>

    <script>
        // Sidebar toggle
        const sidebar = document.querySelector('aside');
        const sidebarToggle = document.getElementById('sidebarToggle');
        if (sidebarToggle) {
            sidebarToggle.addEventListener('click', () => sidebar.classList.toggle('open'));
        }
        document.addEventListener('click', (e) => {
            if (window.innerWidth < 768 && !sidebar.contains(e.target) && !sidebarToggle.contains(e.target)) {
                sidebar.classList.remove('open');
            }
        });

        // Global Alert (toast)
        function showAlert(message, type = 'success', duration = 4000) {
            const container = document.getElementById('globalAlert');
            const icons = { success: 'fa-check-circle', error: 'fa-exclamation-triangle', warning: 'fa-exclamation-circle', info: 'fa-info-circle' };
            const colors = {
                success: 'bg-green-50 border-green-400 text-green-700',
                error: 'bg-red-50 border-red-400 text-red-700',
                warning: 'bg-yellow-50 border-yellow-400 text-yellow-700',
                info: 'bg-blue-50 border-blue-400 text-blue-700'
            };
            const icon = icons[type] || icons.info;
            const color = colors[type] || colors.info;
            const alert = document.createElement('div');
            alert.className = `${color} border-l-4 px-4 py-3 rounded-xl shadow-lg flex items-start gap-3 min-w-[300px] max-w-md animate-slide-in`;
            alert.innerHTML = `<i class="fas ${icon} mt-0.5"></i><div class="flex-1 text-sm">${message}</div><button onclick="this.parentElement.remove()" class="text-current opacity-50 hover:opacity-100 ml-2"><i class="fas fa-times"></i></button>`;
            container.appendChild(alert);
            setTimeout(() => { alert.style.opacity = '0'; setTimeout(() => alert.remove(), 300); }, duration);
        }

        // Global Modal
        const globalModal = document.getElementById('globalModal');
        const globalModalTitle = document.getElementById('globalModalTitle');
        const globalModalBody = document.getElementById('globalModalBody');
        function openGlobalModal(title, contentHtml, wide = false) {
            globalModalTitle.textContent = title;
            globalModalBody.innerHTML = contentHtml;
            document.querySelector('.modal-box').classList.toggle('wide', wide);
            globalModal.classList.add('active');
        }
        function closeGlobalModal() { globalModal.classList.remove('active'); }
        globalModal.addEventListener('click', (e) => { if (e.target === globalModal) closeGlobalModal(); });
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                if (globalModal.classList.contains('active')) closeGlobalModal();
                document.querySelectorAll('#modal:not(.hidden)').forEach(() => {
                    if (typeof closeModal === 'function') closeModal();
                });
            }
        });

        // Animasi
        const style = document.createElement('style');
        style.textContent = `
            @keyframes slide-in { from { transform: translateX(100%); opacity: 0; } to { transform: translateX(0); opacity: 1; } }
            .animate-slide-in { animation: slide-in .3s ease-out; }
            .modal-overlay.active { display: flex; }
            .modal-overlay { display: none; }
            .modal-box { animation: slide-up .25s ease-out; }
            @keyframes slide-up { from { transform: translateY(20px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
        `;
        document.head.appendChild(style);
    </script>

    @stack('scripts')
</body>
</html>
