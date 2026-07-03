<?php
// Sidebar role control - $_current_role sudah di-set di header.php
$_sidebar_role = isset($_current_role) ? $_current_role : (isset($_SESSION['role']) ? strtolower($_SESSION['role']) : 'petugas');
?>

<!-- includes/sidebar.php -->
<aside class="sidebar" id="sidebar">
    <div class="sidebar-brand">
        <i class="fas fa-parking"></i>
        <span>SIPARK-GBK</span>
    </div>
    
    <ul class="sidebar-menu">
        <li class="menu-item">
            <a href="<?= BASE_URL ?>dashboard/dashboard.php">
                <i class="fas fa-chart-pie"></i>
                <span>Dashboard</span>
            </a>
        </li>
        
        <li class="menu-item">
            <a href="<?= BASE_URL ?>dashboard/area_parkir/index.php">
                <i class="fas fa-map-marked-alt"></i>
                <span>Area Parkir</span>
            </a>
        </li>
        
        <li class="menu-item">
            <a href="<?= BASE_URL ?>crud/kendaraan.php">
                <i class="fas fa-car"></i>
                <span>Kendaraan</span>
            </a>
        </li>
        
        <li class="menu-item">
            <a href="<?= BASE_URL ?>crud/tarif.php">
                <i class="fas fa-tags"></i>
                <span>Tarif Parkir</span>
            </a>
        </li>
        
        <li class="menu-item">
            <a href="<?= BASE_URL ?>crud/petugas.php">
                <i class="fas fa-user-tie"></i>
                <span>Data Petugas</span>
            </a>
        </li>
        
        <?php if ($_sidebar_role === 'admin'): ?>
        <li class="menu-item">
            <a href="<?= BASE_URL ?>crud/users.php">
                <i class="fas fa-users-cog"></i>
                <span>Data Users</span>
            </a>
        </li>
        <?php endif; ?>

        <li class="menu-item">
            <a href="<?= BASE_URL ?>crud/transaksi.php">
                <i class="fas fa-history"></i>
                <span>Transaksi</span>
            </a>
        </li>
    </ul>

    <div class="sidebar-footer">
        <a href="<?= BASE_URL ?>auth/logout.php" class="btn-logout" style="text-decoration: none;">
            <i class="fas fa-sign-out-alt"></i>
            <span>Logout</span>
        </a>
    </div>
</aside>