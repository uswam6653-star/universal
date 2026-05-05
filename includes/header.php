<?php
set_time_limit(60);
require_once __DIR__ . '/../core/session.php';

// 1. Fetch System Settings
$settings = [];
$stmt = $pdo->query("SELECT * FROM system_settings");
while ($row = $stmt->fetch()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

// 2. Identify Current Page & Security Check
$current_url = substr($_SERVER['SCRIPT_NAME'], strlen('/universal/')); // Adjust offset
$db_url_match = $current_url; 

// Fetch Page Info
$pageStmt = $pdo->prepare("SELECT * FROM sys_pages WHERE page_url LIKE ? LIMIT 1");
$pageStmt->execute(["%$current_url%"]); 
$currentPageData = $pageStmt->fetch();

$pageTitle = $currentPageData['page_name'] ?? 'Dashboard';
$pageId = $currentPageData['id'] ?? 0;

// 3. Security Access Check & Granular Permissions Load
$userPerms = [];
if (isset($_SESSION['user_id'])) {
    $uStmt = $pdo->prepare("SELECT identity_no FROM users WHERE id = ?");
    $uStmt->execute([$_SESSION['user_id']]);
    $uMeta = $uStmt->fetchColumn();
    $uParts = explode('|', $uMeta ?? '');
    // Index 9 is reserved for Granular Permissions
    $userPerms = array_filter(array_map('trim', explode(',', $uParts[9] ?? '')));
    $_SESSION['granular_perms'] = $userPerms; // Sync to session for button-level checks
}

if ($pageId > 0 && $_SESSION['role'] !== 'super_admin') {
    // Check Role Access first
    $accessStmt = $pdo->prepare("SELECT * FROM role_access WHERE role_key = ? AND page_id = ?");
    $accessStmt->execute([$_SESSION['role'], $pageId]);
    $roleHasAccess = ($accessStmt->rowCount() > 0);

    // Bypass: If role doesn't have access, check if specific user has individual permission (Page ID override)
    if (!$roleHasAccess && !in_array((string)$pageId, $userPerms)) {
        die('<div class="alert alert-danger m-5">⛔ Access Denied: You do not have permission to view this page.</div>');
    }
}

// 4. Breadcrumb Logic (Recursive Upwards)
$breadcrumbs = [];
if ($currentPageData) {
    $crumbId = $currentPageData['id'];
    while($crumbId != 0) {
        $crumbStmt = $pdo->prepare("SELECT id, parent_id, page_name, page_url FROM sys_pages WHERE id = ?");
        $crumbStmt->execute([$crumbId]);
        $crumb = $crumbStmt->fetch();
        if ($crumb) {
            array_unshift($breadcrumbs, $crumb);
            $crumbId = $crumb['parent_id'];
        } else {
            $crumbId = 0;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en"> <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> | <?= htmlspecialchars($settings['system_name'] ?? 'Universal System') ?></title>
    
    <script>
        const storedTheme = localStorage.getItem('theme');
        if (storedTheme) {
            document.documentElement.setAttribute('data-bs-theme', storedTheme);
        } else {
            const systemTheme = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
            document.documentElement.setAttribute('data-bs-theme', systemTheme);
        }
    </script>

    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/bootstrap.min.css" />
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/bootstrap-icons.css" />
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/adminlte.min.css" />
    
    <style> 
        .app-brand-logo { height: 30px; width: auto; } 
        .user-image { width: 30px; height: 30px; object-fit: cover; }
        .nav-link.active { background-color: rgba(var(--bs-primary-rgb), 0.1) !important; color: var(--bs-primary) !important; font-weight: bold; }
        /* ===== Center & Constrain Main Content ===== */
        .app-content .container-fluid {
            max-width: 1400px;
            margin: 0 auto;
        }
    </style>
</head>
<body class="layout-fixed sidebar-expand-lg bg-body-tertiary">
<div class="app-wrapper">
    <nav class="app-header navbar navbar-expand bg-body">
        <div class="container-fluid">
            <ul class="navbar-nav">
                <li class="nav-item"> <a class="nav-link" data-lte-toggle="sidebar" href="#" role="button"><i class="bi bi-list"></i></a> </li>
                <li class="nav-item d-none d-md-block"> <a href="#" class="nav-link"><?= $pageTitle ?></a> </li>
            </ul>
            <ul class="navbar-nav ms-auto">
                 <li class="nav-item">
                    <button class="btn btn-link nav-link" id="theme-toggle" type="button">
                        <i class="bi bi-sun-fill" id="theme-icon"></i>
                    </button>
                </li>
                <li class="nav-item dropdown user-menu">
                    <a href="#" class="nav-link dropdown-toggle" data-bs-toggle="dropdown">
                        <img src="<?= !empty($_SESSION['avatar']) ? $_SESSION['avatar'] : BASE_URL.'assets/img/avatar.png?v='.time() ?>" class="user-image rounded-circle shadow" alt="User Image">
                        <span class="d-none d-md-inline ms-1"><?= htmlspecialchars($_SESSION['name'] ?? 'User') ?></span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-lg dropdown-menu-end">
                        <li class="user-header text-bg-primary">
                            <img src="<?= !empty($_SESSION['avatar']) ? $_SESSION['avatar'] : BASE_URL.'assets/img/avatar.png?v='.time() ?>" class="rounded-circle shadow" alt="User Image">
                            <p>
                                <?= htmlspecialchars($_SESSION['name'] ?? 'User') ?>
                                <small><?= ucfirst(str_replace('_', ' ', $_SESSION['role'] ?? 'Guest')) ?></small>
                            </p>
                        </li>
                        <li class="user-footer"> 
                            <a href="<?= BASE_URL ?>profile.php" class="btn btn-default btn-flat">Profile</a>
                            <a href="<?= BASE_URL ?>logout.php" class="btn btn-default btn-flat float-end">Sign out</a> 
                        </li>
                    </ul>
                </li>
            </ul>
        </div>
    </nav>
    
    <?php include __DIR__ . '/sidebar.php'; ?>
    
    <main class="app-main">
        <div class="app-content-header">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-sm-6"><h3 class="mb-0 text-primary fw-bold"><?= $pageTitle ?></h3></div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-end">
                            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>">Home</a></li>
                            <?php foreach($breadcrumbs as $b): ?>
                                <li class="breadcrumb-item <?= ($b['id'] == $pageId) ? 'active' : '' ?>">
                                    <?= htmlspecialchars($b['page_name']) ?>
                                </li>
                            <?php endforeach; ?>
                        </ol>
                    </div>
                </div>
            </div>
        </div>
        <div class="app-content">
            <div class="container-fluid">