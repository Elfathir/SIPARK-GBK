<?php

require "config/database.php";

    // Total Kendaraan
    $totalKendaraan = $conn
    ->query("SELECT COUNT(*) FROM kendaraan")
    ->fetchColumn();

    // Total Petugas
    $totalPetugas = $conn
    ->query("SELECT COUNT(*) FROM petugas")
    ->fetchColumn();

    // Total Area
    $totalArea = $conn
    ->query("SELECT COUNT(*) FROM area_parkir")
    ->fetchColumn();

    // Total Transaksi
    $totalTransaksi = $conn
    ->query("SELECT COUNT(*) FROM transaksi_parkir")
    ->fetchColumn();

?>

<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SIPARK GBK | Sistem Informasi Parkir</title>

    <!-- AOS Animation -->
    <link href="https://unpkg.com/aos@2.3.4/dist/aos.css" rel="stylesheet">
    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <!-- CSS -->
    <link rel="stylesheet" href="assets/css/style_landing.css">
    <link rel="stylesheet" href="assets/css/landing.css">

</head>

<body>

    <!--  NAVBAR  -->
    <nav class="navbar navbar-expand-lg navbar-custom">
        <div class="container">
            <a class="navbar-brand" href="#">
                <img src="assets/img/logo.png"
                    class="navbar-logo"
                    alt="Logo">
                <span class="navbar-title">
                    SIPARK-GBK
                </span>
            </a>

            <button class="navbar-toggler"
                    data-bs-toggle="collapse"
                    data-bs-target="#navbarMenu">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse"
                id="navbarMenu">

                <ul class="navbar-nav mx-auto nav-pill">
                    <span class="nav-indicator"></span>

                    <li class="nav-item">
                        <a class="nav-link active" href="#">Beranda</a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link" href="#tentang">Tentang</a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link" href="#fitur">Fitur</a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link" href="#statistik">Statistik</a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link" href="#kontak">Kontak</a>
                    </li>
                </ul>

                <a href="auth/login.php"
                class="btn btn-login">
                    <i class="bi bi-box-arrow-in-right"></i>
                    Login
                </a>
            </div>
        </div>
    </nav>

    <!--  SECTION TICKER LOOP  -->
    <section class="ticker-gallery-section">
        <div class="ticker-wrap">
            
            <!-- Tombol Panah Kiri (Pojok Kiri Menimpa Gambar) -->
            <button class="ticker-btn prev-btn" aria-label="Sebelumnya">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="15 18 9 12 15 6"></polyline>
                </svg>
            </button>

            <!-- Tombol Panah Kanan (Pojok Kanan Menimpa Gambar) -->
            <button class="ticker-btn next-btn" aria-label="Selanjutnya">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="9 18 15 12 9 6"></polyline>
                </svg>
            </button>

            <div class="ticker-track">
                <!-- Set 1: Antrean Gambar Utama -->
                <img src="assets/img/loop1.png" alt="Gambar 1">
                <img src="assets/img/loop2.png" alt="Gambar 2">
                <img src="assets/img/loop3.png" alt="Gambar 3">
                <img src="assets/img/loop4.png" alt="Gambar 4">
                
                <!-- Set 2: Duplikat Persis Set 1 agar Loop Seamless -->
                <img src="assets/img/loop1.png" alt="Gambar 1">
                <img src="assets/img/loop2.png" alt="Gambar 2">
                <img src="assets/img/loop3.png" alt="Gambar 3">
                <img src="assets/img/loop4.png" alt="Gambar 4">
            </div>
        </div>
    </section>

    <!--  HERO  -->
    <section class="hero">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6" data-aos="fade-right">

                    <h1 class="fw-bold mb-4">
                        Sistem Informasi Parkir Kendaraan
                        <span id="typing-text"></span>
                    </h1>

                    
                    <p class="lead mb-4">
                        Platform digital terpadu untuk efisiensi manajemen 
                        operasional dan keamanan transaksi parkir di Kawasan Gelora Bung Karno.
                    </p>

                    <a href="auth/login.php" class="btn btn-main me-3">
                        Mulai Sekarang
                    </a>

                    <a href="#tentang" class="btn btn-outline-custom">
                        Pelajari
                    </a>

                </div>
                <div class="col-lg-6 text-center hero-image-wrapper" data-aos="fade-left">
                    <div class="hero-circle">
                        <div class="stagger-visualizer"></div>
                        <div class="orbit orbit1"></div>
                        <div class="orbit orbit2"></div>
                        <div class="orbit orbit3"></div>
                        <div class="orbit orbit4"></div>
                        <div class="circle-glow"></div>
                            <img src="assets/img/hero.png" class="img-fluid hero-image" alt="Parkir">
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- TENTANG -->
    <section id="tentang" class="about-section">
        <div class="container">
            <div class="row align-items-center gy-5">

                <!-- GAMBAR -->
                <div class="col-lg-6" data-aos="fade-right">
                    <div class="about-image">
                        <img
                            src="assets/img/about.png"
                            class="img-fluid"
                            alt="Tentang SIPARK">
                    </div>
                </div>

                <!-- DESKRIPSI -->
                <div class="col-lg-6" data-aos="fade-left">
                    <span class="section-badge">
                        TENTANG SIPARK
                    </span>

                    <h2 class="about-title">
                        Apa itu
                        <span>SIPARK-GBK</span>
                        ?
                    </h2>

                    <p class="about-text">
                        Platform pusat pengelolaan aktivitas
                        parkir kendaraan di lingkungan Gelora
                        Bung Karno. Sistem ini mempermudah
                        pengawasan transaksi, pembagian shift
                        kerja petugas, hingga pengaturan
                        kapasitas area parkir. Memanfaatkan 
                        arsitektur Master-Slave PostgreSQL, 
                        platform ini menawarkan performa yang 
                        stabil dan pengelolaan data yang 
                        terintegrasi dalam satu pintu.
                    </p>

                    <div class="row mt-4">
                        <div class="col-6">
                            <ul class="about-list">
                                <li>
                                    <i class="bi bi-check-circle-fill"></i>
                                    Pengelolaan Kendaraan
                                </li>

                                <li>
                                    <i class="bi bi-check-circle-fill"></i>
                                    Pengelolaan Petugas
                                </li>

                                <li>
                                    <i class="bi bi-check-circle-fill"></i>
                                    Pengaturan Area Parkir
                                </li>
                            </ul>
                        </div>

                        <div class="col-6">
                            <ul class="about-list">
                                <li>
                                    <i class="bi bi-check-circle-fill"></i>
                                    Penetapan Tarif Parkir
                                </li>

                                <li>
                                    <i class="bi bi-check-circle-fill"></i>
                                    Manajemen User
                                </li>

                                <li>
                                    <i class="bi bi-check-circle-fill"></i>
                                    Pencatatan Transaksi Parkir
                                </li>
                            </ul>
                        </div>
                    </div>

                    <a href="auth/login.php" class="btn btn-main mt-4">
                        Login Sistem
                    </a>

                </div>
            </div>
        </div>
    </section>

    <!--  FITUR  -->
    <section id="fitur" class="section-feature">
        <div class="container">
            <div class="text-center mb-5">

                <span class="section-badge">
                    FITUR UNGGULAN
                </span>

                <h2 class="section-title">
                    Kelola Seluruh Aktivitas Parkir
                </h2>

                <p class="section-subtitle">
                    SIPARK-GBK menyediakan berbagai fitur yang memudahkan
                    pengelolaan parkir secara cepat, aman, dan terintegrasi.
                </p>
            </div>

            <div class="row g-4">

                <!-- CARD 1 -->
                <div class="col-lg-3 col-md-6">
                    <div class="feature-box" data-aos="zoom-in" data-aos-delay="100">
                        <div class="feature-icon bg-primary">
                            <i class="bi bi-car-front-fill"></i>
                        </div>

                        <h4>Data Kendaraan</h4>

                        <p>
                            Kelola seluruh data kendaraan yang masuk ke
                            kawasan Gelora Bung Karno.
                        </p>
                    </div>
                </div>

                <!-- CARD 2 -->
                <div class="col-lg-3 col-md-6">
                    <div class="feature-box" data-aos="zoom-in" data-aos-delay="200">
                        <div class="feature-icon bg-success">
                            <i class="bi bi-person-badge-fill"></i>
                        </div>

                        <h4>Data Petugas</h4>

                        <p>
                            Mengelola akun petugas beserta informasi
                            shift kerja secara mudah.
                        </p>
                    </div>
                </div>

                <!-- CARD 3 -->
                <div class="col-lg-3 col-md-6">
                    <div class="feature-box" data-aos="zoom-in" data-aos-delay="300">
                        <div class="feature-icon bg-warning">
                            <i class="bi bi-p-square-fill"></i>
                        </div>

                        <h4>Area Parkir</h4>

                        <p>
                            Mengatur kapasitas area parkir kendaraan
                            berdasarkan lokasi parkir GBK.
                        </p>
                    </div>
                </div>

                <!-- CARD 4 -->
                <div class="col-lg-3 col-md-6">
                    <div class="feature-box" data-aos="zoom-in" data-aos-delay="400">
                        <div class="feature-icon bg-danger">
                            <i class="bi bi-receipt-cutoff"></i>
                        </div>

                        <h4>Transaksi</h4>

                        <p>
                            Mencatat kendaraan masuk dan keluar secara
                            otomatis beserta tarif parkir.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!--  STATISTIK  -->
    <section id="statistik" class="stat-section">
        <div class="container">
            <div class="text-center mb-5">
                <span class="section-badge">
                    STATISTIK SISTEM
                </span>

                <h2 class="section-title">
                    Data SIPARK GBK
                </h2>

                <p class="section-subtitle">
                    Statistik diperoleh langsung dari basis data PostgreSQL.
                </p>
            </div>

            <div class="row g-4">

                <!-- Kendaraan -->
                <div class="col-lg-3 col-md-6">
                    <div class="stat-box" data-aos="flip-up">
                        <div class="stat-icon">
                            <i class="bi bi-car-front-fill"></i>
                        </div>
                            <h2>
                            <?= $totalKendaraan ?>
                            </h2>

                            <p>
                            Data Kendaraan
                            </p>
                    </div>
                </div>

                <!-- Petugas -->
                <div class="col-lg-3 col-md-6">
                    <div class="stat-box" data-aos="flip-up">
                        <div class="stat-icon">
                            <i class="bi bi-person-badge-fill"></i>
                        </div>
                            <h2>
                            <?= $totalPetugas ?>
                            </h2>

                            <p>
                            Petugas
                            </p>
                    </div>
                </div>

                <!-- Area -->
                <div class="col-lg-3 col-md-6">
                    <div class="stat-box" data-aos="flip-up">
                        <div class="stat-icon">
                            <i class="bi bi-p-square-fill"></i>
                        </div>
                            <h2>
                            <?= $totalArea ?>
                            </h2>

                            <p>
                            Area Parkir
                            </p>
                    </div>
                </div>

                <!-- Transaksi -->
                <div class="col-lg-3 col-md-6">
                    <div class="stat-box" data-aos="flip-up">
                        <div class="stat-icon">
                            <i class="bi bi-receipt-cutoff"></i>
                        </div>
                            <h2>
                            <?= $totalTransaksi ?>
                            </h2>

                            <p>
                            Transaksi
                            </p>
                    </div>
                </div>
            </div>
        </div>
    </section>


    <!--  FOOTER  -->
    <footer id="kontak" class="footer">
        <div class="container">
            <div class="row gy-5">

                <!-- Kolom 1 -->
                <div class="col-lg-4">
                    <h3 class="footer-logo d-flex align-items-center">

                        <img
                            src="assets/img/logo.png"
                            alt="Logo SIPARK GBK"
                            class="footer-logo-img">

                        <span class="ms-2">
                            SIPARK GBK
                        </span>

                    </h3>

                    <p class="footer-text">
                        Sistem informasi parkir pintar berbasis
                        web yang dirancang khusus untuk mengoptimalkan
                        operasional di Kawasan GBK. Memanfaatkan 
                        teknologi replikasi database untuk menjamin
                        keamanan data, transparansi transaksi, 
                        dan performa sistem yang andal.
                    </p>
                </div>

                <!-- Kolom 2 -->
                <div class="col-lg-2">
                    <h5>Menu</h5>
                    <ul class="footer-menu">
                        <li><a href="#">Beranda</a></li>
                        <li><a href="#tentang">Tentang</a></li>
                        <li><a href="#fitur">Fitur</a></li>
                        <li><a href="#statistik">Statistik</a></li>
                    </ul>
                </div>

                <!-- Kolom 3 -->
                <div class="col-lg-3">
                    <h5>Fitur Sistem</h5>
                    <ul class="footer-menu">
                        <li>Data Kendaraan</li>
                        <li>Data Petugas</li>
                        <li>Area Parkir</li>
                        <li>Transaksi Parkir</li>
                        <li>Laporan</li>
                    </ul>
                </div>

                <!-- Kolom 4 -->
                <div class="col-lg-3">
                    <h5>Kontak</h5>
                    <ul class="footer-contact">

                        <li>
                            <i class="bi bi-geo-alt-fill"></i>
                            <a
                                href="https://maps.app.goo.gl/mtfqpsUQqgWnT7an7"
                                target="_blank"
                                class="footer-link">

                                Jl. Pintu Satu Senayan, Gelora, 
                                Kecamatan Tanah Abang, Kota 
                                Jakarta Pusat, DKI Jakarta 10270
                            </a>
                        </li>

                        <li>
                            <i class="bi bi-envelope-fill"></i>
                            <a href="mailto:2510511007@mahasiswa.upnvj.ac.id?subject=Pertanyaan%20SIPARK-GBK" class="footer-link">
                                2510511007@mahasiswa.upnvj.ac.id
                            </a>
                        </li>

                        <li>
                            <i class="bi bi-telephone-fill"></i>
                            +62 838 7568 **** (Hakim)
                        </li>
                    </ul>
                </div>
            </div>

            <hr class="footer-line">
            <div class="footer-bottom">
                <div class="row">
                    <div class="col-md-6">
                        © 2026 SIPARK GBK.
                        All Rights Reserved.
                    </div>

                    <div class="col-md-6 text-md-end">
                        Kelompok 2 Sistem Basis Data
                    </div>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>

    <!-- AOS -->
    <script src="https://unpkg.com/aos@2.3.4/dist/aos.js"></script>

    <script>
    AOS.init({
        duration: 900,
        once: true,
        offset: 80
    });
    </script>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/animejs/3.2.2/anime.min.js"></script>
    <script src="assets/js/ticker.js"></script>
    <script src="assets/js/hero.js"></script>
    <script src="assets/js/orbit.js"></script>
    <script src="assets/js/main.js"></script>

</body>
</html>