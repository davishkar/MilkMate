<?php
require_once dirname(__DIR__) . '/config/app.php';
requireLogin();
$companyName = getSetting('company_name', 'MilkMate Dairy');
$pageTitle = $pageTitle ?? 'Dashboard';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($pageTitle) ?> — <?= htmlspecialchars($companyName) ?></title>
  <meta name="description" content="<?= htmlspecialchars($companyName) ?> - Dairy Management System">
  <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🥛</text></svg>">
  <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/fonts.css">
  <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/app.css">
  <?php if (isset($extraCSS)) echo $extraCSS; ?>
</head>
<body>
  <div id="page-loader" class="page-loader">
    <div class="sidebar-logo-icon" style="width:56px;height:56px;font-size:26px;border-radius:16px;">🥛</div>
    <div class="spinner" style="width:28px;height:28px;"></div>
  </div>

  <div class="app-wrapper">
    <!-- Sidebar -->
    <aside class="sidebar" id="sidebar">
      <div class="sidebar-logo">
        <div class="sidebar-logo-icon">🥛</div>
        <span class="sidebar-logo-text">Milk<span>Mate</span></span>
      </div>

      <nav class="sidebar-nav">
        <div class="nav-section-label">Main</div>
        <a href="<?= APP_URL ?>/dashboard.php" class="nav-item" data-path="dashboard">
          <span class="nav-icon">📊</span><span class="nav-label">Dashboard</span>
        </a>

        <div class="nav-section-label">Operations</div>
        <a href="<?= APP_URL ?>/modules/farmers/index.php" class="nav-item" data-path="farmers">
          <span class="nav-icon">👨‍🌾</span><span class="nav-label">Farmers</span>
        </a>
        <a href="<?= APP_URL ?>/modules/collections/index.php" class="nav-item" data-path="collections">
          <span class="nav-icon">🍼</span><span class="nav-label">Milk Collection</span>
        </a>
        <a href="<?= APP_URL ?>/modules/billing/index.php" class="nav-item" data-path="billing">
          <span class="nav-icon">🧾</span><span class="nav-label">Billing</span>
        </a>

        <div class="nav-section-label">Finance</div>
        <a href="<?= APP_URL ?>/modules/accounts/index.php" class="nav-item" data-path="accounts">
          <span class="nav-icon">💰</span><span class="nav-label">Accounts</span>
        </a>
        <a href="<?= APP_URL ?>/modules/accounts/expenses.php" class="nav-item" data-path="expenses">
          <span class="nav-icon">📉</span><span class="nav-label">Expenses</span>
        </a>

        <div class="nav-section-label">Reports</div>
        <a href="<?= APP_URL ?>/modules/reports/index.php" class="nav-item" data-path="reports">
          <span class="nav-icon">📋</span><span class="nav-label">Reports</span>
        </a>

        <?php if (isAdmin()): ?>
        <div class="nav-section-label">Admin</div>
        <a href="<?= APP_URL ?>/modules/settings/index.php" class="nav-item" data-path="settings">
          <span class="nav-icon">⚙️</span><span class="nav-label">Settings</span>
        </a>
        <?php endif; ?>
      </nav>

      <div class="sidebar-footer">
        <div class="sidebar-user">
          <div class="user-avatar"><?= strtoupper(substr($_SESSION['name'] ?? 'U', 0, 1)) ?></div>
          <div class="user-info">
            <div class="user-name"><?= htmlspecialchars($_SESSION['name'] ?? '') ?></div>
            <div class="user-role"><?= htmlspecialchars($_SESSION['role'] ?? '') ?></div>
          </div>
        </div>
        <a href="<?= APP_URL ?>/auth/logout.php" class="btn btn-ghost btn-sm w-full mt-3" style="justify-content:center;">
          🚪 Logout
        </a>
      </div>
    </aside>

    <div class="sidebar-overlay" id="sidebar-overlay"></div>

    <!-- Main Content -->
    <div class="main-content">
      <!-- Navbar -->
      <nav class="navbar">
        <button class="navbar-toggle" id="sidebar-toggle" aria-label="Toggle Sidebar">☰</button>
        <span class="navbar-title"><?= htmlspecialchars($pageTitle) ?></span>
        <div class="navbar-spacer"></div>
        <div class="navbar-search">
          <span>🔍</span>
          <input type="text" placeholder="Search..." id="global-search">
        </div>
        <div style="display:flex;gap:8px;">
          <button class="navbar-icon-btn" title="Notifications">
            🔔<span class="notif-dot"></span>
          </button>
          <button class="navbar-icon-btn" onclick="window.print()" title="Print">🖨️</button>
          <button class="navbar-icon-btn" title="Today: <?= date('d M Y') ?>">📅</button>
        </div>
      </nav>

      <!-- Page Body -->
      <main class="page-body" id="main-content">
