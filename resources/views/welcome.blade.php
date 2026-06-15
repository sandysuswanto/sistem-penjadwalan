<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Sistem Penjadwalan Mata Kuliah - ACO</title>

    <!-- Tailwind -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">

    <style>
        * {
            font-family: 'Inter', sans-serif;
        }

        body {
            background:
                linear-gradient(135deg,
                    #eff6ff 0%,
                    #dbeafe 35%,
                    #bfdbfe 100%);
            min-height: 100vh;
            overflow-x: hidden;
            color: #1e293b;
        }

        .glass {
            background: rgba(255, 255, 255, 0.75);
            backdrop-filter: blur(18px);
            border: 1px solid rgba(255, 255, 255, 0.7);
            box-shadow: 0 10px 30px rgba(37, 99, 235, 0.08);
        }

        .nav-blur {
            background: rgba(255, 255, 255, 0.92);
            backdrop-filter: blur(14px);

            /* garis bawah biru */
            border-bottom: 2px solid rgba(37, 99, 235, 0.15);

            /* shadow biru halus */
            box-shadow:
                0 4px 20px rgba(37, 99, 235, 0.06);
        }

        .card-hover {
            transition: all 0.35s ease;
        }

        .card-hover:hover {
            transform: translateY(-6px);
            box-shadow: 0 20px 40px rgba(37, 99, 235, 0.12);
        }

        .btn-primary {
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
        }

        .hero-title {
            line-height: 1.2;
            letter-spacing: -1px;
            color: #1e3a8a;
        }

        .hero-title span {
            background: linear-gradient(to right, #2563eb, #60a5fa);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .floating {
            animation: floating 5s ease-in-out infinite;
        }

        @keyframes floating {
            0% {
                transform: translateY(0px);
            }

            50% {
                transform: translateY(-10px);
            }

            100% {
                transform: translateY(0px);
            }
        }

        .section-title {
            font-weight: 800;
            letter-spacing: -1px;
            color: #1e3a8a;
        }

        .text-soft {
            color: #475569;
        }

        .glow {
            box-shadow:
                0 10px 25px rgba(37, 99, 235, 0.18);
        }
    </style>
</head>

<body class="antialiased">

    <div class="min-h-screen">

        <!-- Navbar -->
        <nav class="nav-blur fixed w-full z-50">

            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

                <div class="flex justify-between items-center h-20">

                    <!-- Logo -->
                    <div class="flex items-center gap-4">

                        <img src="{{ asset('image/logo.png') }}" alt="Logo" class="w-14 h-14 object-contain">

                        <div>

                            <h1 class="font-bold text-xl leading-tight text-slate-800">
                                Sistem Penjadwalan
                            </h1>

                            <p class="text-slate-500 text-sm">
                                Fakultas Teknik Universitas Wiraraja
                            </p>

                        </div>

                    </div>

                    <!-- Menu -->
                    <div class="flex items-center gap-4">

                        <a href="{{ route('login') }}"
                            class="px-5 py-2 text-slate-700 hover:bg-blue-50 rounded-xl transition flex items-center gap-2">
                            <i class="fas fa-sign-in-alt text-blue-500"></i>
                            Login
                        </a>

                        <a href="{{ route('jadwalglobal.list') }}"
                            class="px-5 py-2 bg-blue-600 text-white rounded-xl hover:bg-blue-700 transition flex items-center gap-2 font-semibold glow">
                            <i class="fas fa-calendar"></i>
                            Jadwal
                        </a>

                    </div>

                </div>

            </div>

        </nav>

        <!-- Hero -->
        <section class="relative flex items-center justify-center min-h-screen px-6 md:px-10 pt-28 mb-12">

            <div class="max-w-7xl mx-auto grid md:grid-cols-2 gap-16 items-center">

                <!-- Left -->
                <div>

                    <h1 class="text-4xl md:text-5xl font-black hero-title mb-6">
                        Fakultas Teknik
                        <span>Universitas Wiraraja</span>
                        Sumenep Madura
                    </h1>

                    <!-- Deskripsi + Statistik -->
                    <p class="text-lg md:text-xl leading-relaxed max-w-2xl font-light mb-8 text-soft">

                        <span class="font-semibold text-blue-700">
                            Sistem penjadwalan mata kuliah otomatis
                        </span>
                        untuk membantu proses penyusunan jadwal secara cepat,
                        efektif, dan meminimalkan bentrok jadwal dosen,
                        ruangan, maupun kelas.

                        Saat ini sistem memiliki
                        <span class="font-bold text-blue-700">
                            {{ number_format($totalMatkul, 0, ',', '.') }}
                        </span>
                        mata kuliah yang terdiri dari
                        <span class="font-semibold text-blue-700">
                            {{ number_format($matkulGanjil, 0, ',', '.') }}
                        </span>
                        semester ganjil dan
                        <span class="font-semibold text-blue-700">
                            {{ number_format($matkulGenap, 0, ',', '.') }}
                        </span>
                        semester genap, dengan
                        <span class="font-bold text-blue-700">
                            {{ number_format($totalDosen, 0, ',', '.') }}
                        </span>
                        dosen,
                        <span class="font-bold text-blue-700">
                            {{ number_format($totalRuangan, 0, ',', '.') }}
                        </span>
                        ruangan, serta
                        <span class="font-bold text-blue-700">
                            {{ number_format($totalKelas, 0, ',', '.') }}
                        </span>
                        kelas aktif yang digunakan dalam proses penjadwalan.

                    </p>
                    <!-- Button -->
                    <div class="flex flex-wrap gap-4">

                        <a href="{{ route('login') }}"
                            class="px-8 py-4 bg-white border border-blue-200 text-blue-700 font-semibold rounded-2xl shadow-sm hover:bg-blue-50 btn-primary flex items-center gap-3">

                            <i class="fas fa-sign-in-alt"></i>
                            Login Admin / Kaprodi

                        </a>

                        <a href="{{ route('jadwalglobal.list') }}"
                            class="px-8 py-4 bg-blue-600 text-white font-semibold rounded-2xl shadow-lg hover:bg-blue-700 btn-primary flex items-center gap-3 glow">

                            <i class="fas fa-calendar-week"></i>
                            Lihat Jadwal

                        </a>

                    </div>

                </div>

                <!-- Right -->
                <div class="relative floating">

                    <div class="glass rounded-3xl p-1">

                        <img src="{{ asset('image/bg.jpg') }}" alt="Background Fakultas Teknik"
                            class="w-full h-[420px] object-cover rounded-3xl">

                    </div>

                </div>

            </div>

        </section>

        <!-- Keterangan Sistem -->
        <section class="pb-24 px-6 md:px-10">

            <div class="max-w-6xl mx-auto">

                <!-- Heading -->
                <div class="text-center mb-14">

                    <h2 class="text-3xl md:text-5xl section-title mb-4">
                        Keterangan Sistem
                    </h2>

                    <p class="text-soft text-sm md:text-lg max-w-3xl mx-auto leading-relaxed">
                        Sistem Penjadwalan Mata Kuliah Otomatis menggunakan
                        metode Ant Colony Optimization (ACO) untuk membantu
                        proses penyusunan jadwal perkuliahan secara otomatis,
                        cepat, dan lebih optimal.
                    </p>

                </div>

                <!-- Card -->
                <div class="grid md:grid-cols-2 gap-6">

                    <!-- Card 1 -->
                    <div class="glass rounded-3xl p-6 md:p-8 card-hover">

                        <div class="text-4xl mb-4 text-blue-500">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>

                        <h3 class="text-xl font-bold mb-3 text-slate-800">
                            Penanganan Bentrok Jadwal
                        </h3>

                        <p class="text-soft leading-relaxed">
                            Sistem dapat menangani bentrok jadwal dosen,
                            ruangan, dan kelas sehingga jadwal perkuliahan
                            dapat tersusun lebih teratur dan efisien.
                        </p>

                    </div>

                    <!-- Card 2 -->
                    <div class="glass rounded-3xl p-6 md:p-8 card-hover">

                        <div class="text-4xl mb-4 text-blue-500">
                            <i class="fas fa-sliders-h"></i>
                        </div>

                        <h3 class="text-xl font-bold mb-3 text-slate-800">
                            Constraint / Batasan Sistem
                        </h3>

                        <p class="text-soft leading-relaxed">
                            Sistem menerapkan constraint seperti
                            ketersediaan dosen, penggunaan ruangan tertentu,
                            dan penyesuaian slot jadwal perkuliahan.
                        </p>

                    </div>

                    <!-- Card 3 -->
                    <div class="glass rounded-3xl p-6 md:p-8 card-hover">

                        <div class="text-4xl mb-4 text-blue-500">
                            <i class="fas fa-door-open"></i>
                        </div>

                        <h3 class="text-xl font-bold mb-3 text-slate-800">
                            Penyesuaian Ruangan
                        </h3>

                        <p class="text-soft leading-relaxed">
                            Sistem menyesuaikan kapasitas dan jenis ruangan
                            sesuai kebutuhan kelas dan mata kuliah agar
                            pembelajaran berjalan optimal.
                        </p>

                    </div>

                    <!-- Card 4 -->
                    <div class="glass rounded-3xl p-6 md:p-8 card-hover">

                        <div class="text-4xl mb-4 text-blue-500">
                            <i class="fas fa-moon"></i>
                        </div>

                        <h3 class="text-xl font-bold mb-3 text-slate-800">
                            Jadwal Ramadan
                        </h3>

                        <p class="text-soft leading-relaxed">
                            Sistem mendukung penyesuaian jadwal Ramadan
                            dengan durasi perkuliahan 35 menit per SKS.
                        </p>

                    </div>

                </div>

            </div>

        </section>

        <!-- Footer -->
        <footer class="border-t border-blue-100 py-8 text-center">

            <p class="font-semibold text-slate-700">
                © {{ date('Y') }}
                Sistem Penjadwalan Mata Kuliah Otomatis
            </p>

            <p class="text-sm mt-2 text-slate-500">
                Fakultas Teknik Universitas Wiraraja Sumenep Madura
            </p>

        </footer>

    </div>

</body>

</html>
