<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['id_user'])) {
    header("Location: ../auth/login.php");
    exit;
}

// Store role for use throughout
$_current_role = isset($_SESSION['role']) ? strtolower($_SESSION['role']) : 'petugas';

if (!defined('BASE_URL')) {
    $project_dir = str_replace($_SERVER['DOCUMENT_ROOT'], '', str_replace('\\', '/', dirname(dirname(__DIR__))));
    $project_dir = '/' . trim($project_dir, '/') . '/';
    define('BASE_URL', $project_dir);
}

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
db(); // initialize connection

$user_name = $_SESSION['nama_lengkap'];
$user_role = ucfirst($_SESSION['role']);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SIPARK-GBK - Admin Dashboard</title>
    <!-- FontAwesome 6.4.0 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/dashboard.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/table.css">
</head>
<body>

<div class="app-container">
    <?php include __DIR__ . '/sidebar.php'; ?>

    <div class="main-wrapper">
        <!-- Topbar Header -->
        <header class="topbar">
        <div class="topbar-left">
            <button class="btn-toggle-sidebar" id="btn-toggle-sidebar">
                <i class="fas fa-bars"></i>
            </button>
        </div>

            <div class="topbar-right">
                <!-- Database Status -->
                <div class="db-status-banner shadow-sm"
                    style="background-color:#ECFDF5;
                            border-color:#A7F3D0;
                            color:#047857;">
                    <i class="fas fa-database"></i>
                    <span>PostgreSQL Connected</span>
                </div>

                <!-- Realtime Real Clock -->
                <div class="realtime-clock">
                    <i class="far fa-calendar-alt"></i>
                    <span id="date-display">Loading tanggal...</span>
                    <span style="color: var(--sidebar-bg); margin: 0 4px;">|</span>
                    <i class="far fa-clock"></i>
                    <span id="clock-display">Loading jam...</span>
                </div>

                <!-- Profile Dropdown -->
                <div class="profile-dropdown-container">
                    <div class="profile-trigger" id="profile-trigger">
                        <div class="profile-avatar">
                            <?php echo strtoupper(substr($user_name, 0, 1)); ?>
                        </div>
                        <div class="profile-info">
                            <div class="profile-name"><?php echo htmlspecialchars($user_name); ?></div>
                            <div class="profile-role"><?php echo htmlspecialchars($user_role); ?></div>
                        </div>
                        <i class="fas fa-chevron-down" style="font-size: 11px; color: var(--text-secondary);"></i>
                    </div>
                    <ul class="dropdown-menu" id="profile-dropdown-menu">
                        <?php if ($_current_role === 'admin'): ?>
                        <li>
                            <a href="<?= BASE_URL ?>crud/users.php">
                                <i class="fas fa-user-cog"></i> Kelola Users
                            </a>
                        </li>
                        <?php endif; ?>
                        <li>
                            <a href="<?= BASE_URL ?>auth/logout.php">
                                <i class="fas fa-sign-out-alt"></i> Keluar
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </header>

        <!-- Main Body Wrapper -->
        <main class="content-body">