<?php
// Shared application shell: presentation only. Authentication and data queries stay in each page.
$page = basename($_SERVER['PHP_SELF']);
$pageLabels = [
    'index.php' => 'ড্যাশবোর্ড',
    'projects.php' => 'প্রকল্পসমূহ',
    'expenses.php' => 'ব্যয় ব্যবস্থাপনা',
    'advances.php' => 'কর্মীর অগ্রিম',
    'ledger.php' => 'Employee Ledger',
    'reports.php' => 'রিপোর্ট',
    'expense_heads.php' => 'ব্যয়ের খাত',
    'users.php' => 'ব্যবহারকারী ও অনুমতি',
    'years.php' => 'অর্থবছর',
    'project_account.php' => 'Project Account',
    'profile.php' => 'আমার প্রোফাইল',
];
$pageLabel = $pageLabels[$page] ?? 'JV Construction';
require_once __DIR__ . '/profile_photo.php';
$me = user();
$displayName = (string) ($me['name'] ?? 'User');
$userInitial = function_exists('mb_substr') ? mb_substr($displayName, 0, 1, 'UTF-8') : substr($displayName, 0, 1);
$roleLabel = ucwords(str_replace('_', ' ', (string) ($me['role'] ?? 'staff')));
$avatarUrl = user_avatar_url((int) ($me['id'] ?? 0));
$workspaceLinks = [
    ['url' => 'index.php', 'icon' => 'grid', 'label' => 'ড্যাশবোর্ড'],
    ['url' => 'projects.php', 'icon' => 'building', 'label' => 'প্রকল্পসমূহ'],
    ['url' => 'expenses.php', 'icon' => 'receipt', 'label' => 'ব্যয় ব্যবস্থাপনা'],
    ['url' => 'advances.php', 'icon' => 'wallet', 'label' => 'কর্মীর অগ্রিম'],
    ['url' => 'ledger.php', 'icon' => 'book', 'label' => 'Employee Ledger'],
    ['url' => 'reports.php', 'icon' => 'chart', 'label' => 'রিপোর্ট'],
    ['url' => 'expense_heads.php', 'icon' => 'layers', 'label' => 'ব্যয়ের খাত'],
];
$adminLinks = [
    ['url' => 'users.php', 'icon' => 'users', 'label' => 'ব্যবহারকারী ও অনুমতি'],
    ['url' => 'years.php', 'icon' => 'calendar', 'label' => 'অর্থবছর'],
];
?>
<!doctype html>
<html lang="bn">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#f7f7f1">
    <title><?= e($pageLabel) ?> | JV Construction</title>
    <link rel="stylesheet" href="assets/style.css?v=1.1.0">
    <link rel="stylesheet" href="assets/dashboard-v2.css?v=2.0.0">
    <script>
    (function () {
        try {
            var saved = localStorage.getItem('jv-ui-theme');
            document.documentElement.dataset.theme = saved === 'dark' || saved === 'light'
                ? saved : (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
        } catch (e) { document.documentElement.dataset.theme = 'light'; }
    })();
    </script>
    <link rel="stylesheet" href="assets/workspace-polish.css?v=3.1.0">
    <?php if (in_array($page, ['projects.php', 'expenses.php', 'advances.php', 'ledger.php', 'reports.php', 'expense_heads.php', 'users.php', 'years.php'], true)): ?>
    <link rel="stylesheet" href="assets/records-v1.css?v=1.0.0">
    <?php endif; ?>
    <?php if (in_array($page, ['ledger.php', 'reports.php', 'expense_heads.php', 'users.php', 'years.php'], true)): ?>
    <link rel="stylesheet" href="assets/phase3-v1.css?v=1.0.0">
    <?php endif; ?>
    <link rel="stylesheet" href="assets/red-actions-v1.css?v=1.0.0">
    <link rel="stylesheet" href="assets/phase4-polish.css?v=4.0.0">
</head>
<body class="jv-redesign <?= $page === 'index.php' ? 'is-dashboard' : 'is-inner-page' ?>">
<a class="skip-link" href="#main-content">মূল কনটেন্টে যান</a>
<svg xmlns="http://www.w3.org/2000/svg" class="svg-sprite" aria-hidden="true" focusable="false">
    <symbol id="jv-grid" viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7" rx="2"/><rect x="14" y="3" width="7" height="7" rx="2"/><rect x="3" y="14" width="7" height="7" rx="2"/><rect x="14" y="14" width="7" height="7" rx="2"/></symbol>
    <symbol id="jv-building" viewBox="0 0 24 24"><path d="M4 21V5a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v16M2 21h20M8 7h2m4 0h2M8 11h2m4 0h2M8 15h2m4 0h2M10 21v-4h4v4"/></symbol>
    <symbol id="jv-receipt" viewBox="0 0 24 24"><path d="M5 3h14v18l-3.5-2-3.5 2-3.5-2L5 21zM9 8h6M9 12h6M9 16h3"/></symbol>
    <symbol id="jv-wallet" viewBox="0 0 24 24"><rect x="2.5" y="5" width="19" height="15" rx="3"/><path d="M3 9h18M15 14.5h2"/></symbol>
    <symbol id="jv-book" viewBox="0 0 24 24"><path d="M4 4.5h13a3 3 0 0 1 3 3V21H7a3 3 0 0 1-3-3V4.5ZM4 17.8A3.2 3.2 0 0 1 7 15h13M9 9h7"/></symbol>
    <symbol id="jv-chart" viewBox="0 0 24 24"><path d="M3 3v18h18M7 15l4-4 3 2 5-7"/></symbol>
    <symbol id="jv-layers" viewBox="0 0 24 24"><path d="m12 3 9 5-9 5-9-5 9-5ZM3 12l9 5 9-5M3 16l9 5 9-5"/></symbol>
    <symbol id="jv-users" viewBox="0 0 24 24"><circle cx="9" cy="8" r="3"/><path d="M3 20v-2a6 6 0 0 1 12 0v2H3ZM17 5a3 3 0 0 1 0 6M17 15a5 5 0 0 1 4 5"/></symbol>
    <symbol id="jv-calendar" viewBox="0 0 24 24"><rect x="3" y="5" width="18" height="16" rx="2"/><path d="M7 3v4M17 3v4M3 11h18"/></symbol>
    <symbol id="jv-logout" viewBox="0 0 24 24"><path d="M10 5H5a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h5M14 16l4-4-4-4M18 12H9"/></symbol>
    <symbol id="jv-menu" viewBox="0 0 24 24"><path d="M4 7h16M4 12h16M4 17h16"/></symbol>
    <symbol id="jv-x" viewBox="0 0 24 24"><path d="M6 6l12 12M18 6 6 18"/></symbol>
    <symbol id="jv-chevron" viewBox="0 0 24 24"><path d="m9 18 6-6-6-6"/></symbol>
    <symbol id="jv-chevron-down" viewBox="0 0 24 24"><path d="m6 9 6 6 6-6"/></symbol>
    <symbol id="jv-arrow-up-right" viewBox="0 0 24 24"><path d="M7 17 17 7M7 7h10v10"/></symbol>
    <symbol id="jv-plus" viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></symbol>
    <symbol id="jv-clock" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></symbol>
    <symbol id="jv-check" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="m8 12 2.5 2.5L16 9"/></symbol>
    <symbol id="jv-clipboard" viewBox="0 0 24 24"><rect x="5" y="4" width="14" height="18" rx="2"/><rect x="9" y="2" width="6" height="4" rx="1"/><path d="M9 12h6M9 16h4"/></symbol>
    <symbol id="jv-home" viewBox="0 0 24 24"><path d="m3 11 9-8 9 8v10H3V11Z"/><path d="M9 21v-7h6v7"/></symbol>
    <symbol id="jv-sun" viewBox="0 0 24 24"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.93 4.93l1.42 1.42m11.3 11.3 1.42 1.42M2 12h2m16 0h2M4.93 19.07l1.42-1.42m11.3-11.3 1.42-1.42"/></symbol>
    <symbol id="jv-moon" viewBox="0 0 24 24"><path d="M20.5 15A8.5 8.5 0 0 1 9 3.5 8.5 8.5 0 1 0 20.5 15Z"/></symbol>
    <symbol id="jv-fullscreen" viewBox="0 0 24 24"><path d="M8 3H4a1 1 0 0 0-1 1v4m13-5h4a1 1 0 0 1 1 1v4M8 21H4a1 1 0 0 1-1-1v-4m13 5h4a1 1 0 0 0 1-1v-4"/></symbol>
    <symbol id="jv-user" viewBox="0 0 24 24"><circle cx="12" cy="8" r="4"/><path d="M4 21v-2a8 8 0 0 1 16 0v2"/></symbol>
    <symbol id="jv-spark" viewBox="0 0 24 24"><path d="m12 2 2.5 7.5L22 12l-7.5 2.5L12 22l-2.5-7.5L2 12l7.5-2.5L12 2Z"/></symbol>
</svg>
<div class="app">
    <div class="sidebar-backdrop" id="sidebar-backdrop" hidden></div>
    <aside class="sidebar" id="app-sidebar" aria-label="প্রধান নেভিগেশন">
        <div class="sidebar-brand-row">
            <a class="logo" href="index.php" aria-label="JV Construction ড্যাশবোর্ড">
                <span class="logo-box" aria-hidden="true">J<span>V</span></span>
                <span class="logo-title"><b>JV Construction</b><small>PROJECT CONTROL</small></span>
            </a>
            <button type="button" class="sidebar-close" data-close-sidebar aria-label="Close navigation">
                <svg class="ui-icon" aria-hidden="true"><use href="#jv-x"></use></svg>
            </button>
        </div>
        <div class="sidebar-rule"></div>
        <div class="sidebar-scroll">
            <div class="nav-label">WORKSPACE <span></span></div>
            <nav class="side-navigation" aria-label="কাজের মেনু">
                <?php foreach ($workspaceLinks as $item): ?>
                    <a class="nav <?= $page === $item['url'] ? 'active' : '' ?>" href="<?= e($item['url']) ?>" <?= $page === $item['url'] ? 'aria-current="page"' : '' ?>>
                        <svg class="nav-icon" aria-hidden="true"><use href="#jv-<?= e($item['icon']) ?>"></use></svg>
                        <span><?= e($item['label']) ?></span>
                        <?php if ($page === $item['url']): ?><span class="active-indicator" aria-hidden="true"></span><?php endif; ?>
                    </a>
                <?php endforeach; ?>
            </nav>
            <?php if (is_admin()): ?>
                <div class="nav-label nav-label-admin">ADMINISTRATION <span></span></div>
                <nav class="side-navigation" aria-label="প্রশাসনিক মেনু">
                    <?php foreach ($adminLinks as $item): ?>
                        <a class="nav <?= $page === $item['url'] ? 'active' : '' ?>" href="<?= e($item['url']) ?>" <?= $page === $item['url'] ? 'aria-current="page"' : '' ?>>
                            <svg class="nav-icon" aria-hidden="true"><use href="#jv-<?= e($item['icon']) ?>"></use></svg>
                            <span><?= e($item['label']) ?></span>
                            <?php if ($page === $item['url']): ?><span class="active-indicator" aria-hidden="true"></span><?php endif; ?>
                        </a>
                    <?php endforeach; ?>
                </nav>
            <?php endif; ?>
        </div>
        <div class="sidebar-bottom">
            <div class="sidebar-help">
                <span class="sidebar-help-icon"><svg class="ui-icon" aria-hidden="true"><use href="#jv-spark"></use></svg></span>
                <div><b>সহজ হিসাব, নির্ভুল সিদ্ধান্ত</b><small>প্রতিটি প্রকল্পের তথ্য এক জায়গায়।</small></div>
            </div>
            <div class="sidebar-profile">
                <a class="sidebar-identity" href="profile.php" title="আমার প্রোফাইল">
                    <span class="avatar"><img src="<?= e($avatarUrl) ?>" alt="" loading="lazy" width="36" height="36"></span>
                    <span class="profile-text"><b><?= e($displayName) ?></b><small><?= e($roleLabel) ?></small></span>
                </a>
                <a class="sidebar-signout" href="logout.php" aria-label="লগআউট" title="লগআউট"><svg class="ui-icon profile-arrow" aria-hidden="true"><use href="#jv-logout"></use></svg></a>
            </div>
        </div>
    </aside>
    <main class="main">
        <header class="topbar">
            <div class="header-start">
                <button class="sidebar-toggle" type="button" data-toggle-sidebar aria-label="Open navigation" aria-controls="app-sidebar" aria-expanded="false"><svg class="ui-icon" aria-hidden="true"><use href="#jv-menu"></use></svg></button>
                <div class="header-heading"><span class="header-path">JV / PROJECT WORKSPACE</span><span class="header-title"><?= e($pageLabel) ?></span></div>
            </div>
            <div class="header-end">
                <span class="header-date"><svg class="ui-icon" aria-hidden="true"><use href="#jv-calendar"></use></svg><?= e(date('d M Y')) ?></span>
                <a class="header-report-link" href="reports.php"><svg class="ui-icon" aria-hidden="true"><use href="#jv-chart"></use></svg> রিপোর্ট</a>
                <div class="header-actions" role="group" aria-label="Display settings">
                    <button class="icon-action theme-switch" type="button" data-theme-toggle aria-label="Dark mode চালু করুন" aria-pressed="false" title="Light / Dark mode"><svg class="ui-icon" aria-hidden="true"><use href="#jv-moon"></use></svg></button>
                    <button class="icon-action fullscreen-switch" type="button" data-fullscreen-toggle aria-label="Full screen চালু করুন" title="Full screen"><svg class="ui-icon" aria-hidden="true"><use href="#jv-fullscreen"></use></svg></button>
                </div>
                <details class="account-menu">
                    <summary aria-label="অ্যাকাউন্ট মেনু"><span class="top-avatar"><img src="<?= e($avatarUrl) ?>" alt="" width="38" height="38"></span><span class="account-name"><?= e($displayName) ?><small><?= e($roleLabel) ?></small></span><svg class="ui-icon account-chevron" aria-hidden="true"><use href="#jv-chevron-down"></use></svg></summary>
                    <div class="account-dropdown"><div class="account-dropdown-info"><span class="dropdown-avatar"><img src="<?= e($avatarUrl) ?>" alt="" width="44" height="44"></span><span><b><?= e($displayName) ?></b><small><?= e($roleLabel) ?></small></span></div><a class="account-profile-link" href="profile.php"><svg class="ui-icon" aria-hidden="true"><use href="#jv-user"></use></svg> প্রোফাইল ও ছবি</a><a href="logout.php"><svg class="ui-icon" aria-hidden="true"><use href="#jv-logout"></use></svg> লগআউট</a></div>
                </details>
            </div>
        </header>
        <div class="content" id="main-content" tabindex="-1">
            <div class="workspace-location" aria-label="Page location">
                <div class="location-label"><span class="location-dot" aria-hidden="true"></span> WORKSPACE / <?= e($pageLabel) ?></div>
                <nav class="breadcrumb-pill" aria-label="Breadcrumb"><a href="index.php" aria-label="Dashboard"><svg class="ui-icon" aria-hidden="true"><use href="#jv-home"></use></svg><span>Home</span></a><svg class="ui-icon breadcrumb-arrow" aria-hidden="true"><use href="#jv-chevron"></use></svg><span aria-current="page"><?= e($pageLabel) ?></span></nav>
            </div>
            <?php if (!empty($_SESSION['flash'])): [$msg, $type] = $_SESSION['flash']; unset($_SESSION['flash']); ?>
                <div class="notice <?= e($type) ?>" role="status"><?= e($msg) ?></div>
            <?php endif; ?>
