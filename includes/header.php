<?php
require_once __DIR__ . '/db_connect.php';
requireAuth();
$current_page = basename($_SERVER['PHP_SELF']);
$page_crumb = $page_crumb ?? 'HOME';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CampusGist — Your Campus, Connected</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
</head>
<body>
<div class="app-wrapper" id="appWrapper">
    <!-- Left Navigation Sidebar -->
    <aside class="cg-sidebar" id="cgSidebar">
        <div>
            <!-- Brand Header + Collapse Rail Toggle -->
            <div class="sidebar-header-row">
                <a href="index.php" class="brand-box">
                    <div class="brand-logo">C</div>
                    <div class="brand-text">
                        <div class="brand-title">campusgist</div>
                        <div class="brand-subtitle">Your Campus, Connected</div>
                    </div>
                </a>
                <button type="button" class="sidebar-collapse-btn d-none d-lg-inline-flex" id="railToggleBtn" title="Toggle compact sidebar">
                    <i class="bi bi-layout-sidebar-inset"></i>
                </button>
            </div>

            <!-- Collapsible Section: DISCOVER -->
            <button type="button" class="nav-section-toggle" data-target="discoverLinks" aria-expanded="true">
                <span class="nav-section-label-text">Discover</span>
                <i class="bi bi-chevron-down section-chevron"></i>
            </button>
            <nav class="nav-group-links" id="discoverLinks">
                <a href="index.php" class="cg-nav-link <?= $current_page === 'index.php' ? 'active' : '' ?>" title="Overview">
                    <i class="bi bi-grid"></i> <span class="nav-label">Overview</span>
                </a>
                <a href="events.php" class="cg-nav-link <?= $current_page === 'events.php' ? 'active' : '' ?>" title="Explore events">
                    <i class="bi bi-stars"></i> <span class="nav-label">Explore events</span>
                </a>
                <a href="schedule.php" class="cg-nav-link <?= $current_page === 'schedule.php' ? 'active' : '' ?>" title="My schedule">
                    <i class="bi bi-calendar2-check"></i> <span class="nav-label">My schedule</span>
                </a>
                <a href="announcements.php" class="cg-nav-link <?= $current_page === 'announcements.php' ? 'active' : '' ?>" title="Announcements">
                    <i class="bi bi-megaphone"></i> <span class="nav-label">Announcements</span>
                </a>
            </nav>

            <!-- Collapsible Section: WORKSPACE (Staff/Admin) -->
            <?php if (isStaff()): ?>
                <button type="button" class="nav-section-toggle mt-3" data-target="workspaceLinks" aria-expanded="true">
                    <span class="nav-section-label-text">Workspace</span>
                    <i class="bi bi-chevron-down section-chevron"></i>
                </button>
                <nav class="nav-group-links" id="workspaceLinks">
                    <a href="dashboard.php" class="cg-nav-link <?= $current_page === 'dashboard.php' ? 'active' : '' ?>" title="Organizer dashboard">
                        <i class="bi bi-command"></i> <span class="nav-label">Organizer dashboard</span>
                    </a>
                    <a href="manage.php" class="cg-nav-link <?= $current_page === 'manage.php' ? 'active' : '' ?>" title="Manage events">
                        <i class="bi bi-plus-lg"></i> <span class="nav-label">Manage events</span>
                    </a>
                </nav>
            <?php endif; ?>
        </div>

        <div class="sidebar-footer">
            <div class="mb-1 d-flex align-items-center">
                <span class="live-dot"></span>
                <span class="footer-text"><strong>Campus network</strong> &bull; LIVE</span>
            </div>
            <div class="footer-text" style="font-size: 0.65rem; letter-spacing: 0.08em;">CAMPUSGIST / 01.0</div>
        </div>
    </aside>

    <!-- Main Content Shell -->
    <div class="cg-main" id="cgMain">
        <!-- Top Bar -->
        <header class="cg-topbar">
            <div class="d-flex align-items-center gap-2">
                <button class="btn btn-sm btn-outline-secondary d-lg-none" id="sidebarToggle" type="button">
                    <i class="bi bi-list"></i>
                </button>
                <span class="breadcrumb-label">CAMPUS / <?= e($page_crumb) ?></span>
            </div>

            <div class="d-flex align-items-center gap-2 flex-wrap">
                <span class="topbar-pill d-none d-sm-inline-block"><?= date('M j, Y') ?></span>
                <a href="admin.php" class="topbar-pill">Admin portal &#8599;</a>

                <?php if (isLoggedIn()): ?>
                    <div class="d-flex align-items-center gap-2 ms-1">
                        <span class="avatar-circle">
                            <?= strtoupper(substr($_SESSION['full_name'], 0, 2)) ?>
                        </span>
                        <div class="lh-1 d-none d-md-block">
                            <div style="font-size: 0.78rem; font-weight: 600;"><?= e($_SESSION['email']) ?></div>
                            <span style="font-size: 0.62rem; color: #64748b; text-transform: uppercase;">
                                <?= e($_SESSION['role']) ?>
                            </span>
                        </div>
                        <a href="logout.php" class="btn btn-sm btn-link text-decoration-none text-dark fw-semibold">Sign out</a>
                    </div>
                <?php else: ?>
                    <a href="login.php" class="btn btn-sm btn-cg-primary">Sign in</a>
                <?php endif; ?>
            </div>
        </header>

        <!-- Page Content Container -->
        <main class="container-fluid px-3 px-lg-5 py-3">