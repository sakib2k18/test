<?php
/**
 * ADMIN LAYOUT - HEAD, SIDEBAR AND TOPBAR
 * ---------------------------------------------------------------------------
 * Every admin page starts with:
 *     require_once '.../includes/init.php';
 *     requireAdmin();                       <- server-side authorisation
 *     $pageTitle = '...'; $adminSection = 'buses';
 *     require_once '.../includes/admin_header.php';
 * ---------------------------------------------------------------------------
 */

if (!defined('BASE_URL')) {
    require_once __DIR__ . '/init.php';
}

/* Safety net: no admin page may ever render without this check passing. */
requireAdmin();

$pageTitle    = $pageTitle    ?? 'Dashboard';
$pageSubtitle = $pageSubtitle ?? 'Transport Office control panel';
$adminSection = $adminSection ?? 'dashboard';
$admin        = currentUser();

/* Live counters shown as badges next to the sidebar links. */
$sideCounts = [
    'buses'         => (int) fetchValue('SELECT COUNT(*) FROM buses'),
    'routes'        => (int) fetchValue('SELECT COUNT(*) FROM routes'),
    'schedules'     => (int) fetchValue('SELECT COUNT(*) FROM schedules'),
    'announcements' => (int) fetchValue('SELECT COUNT(*) FROM announcements'),
    'users'         => (int) fetchValue('SELECT COUNT(*) FROM users'),
    'messages'      => (int) fetchValue('SELECT COUNT(*) FROM contact_messages WHERE is_read = 0'),
];

$sideNav = [
    'Manage' => [
        ['key' => 'dashboard',     'label' => 'Dashboard',     'icon' => 'grid',      'href' => 'admin/index.php'],
        ['key' => 'buses',         'label' => 'Buses',         'icon' => 'bus',       'href' => 'admin/buses/index.php',         'count' => $sideCounts['buses']],
        ['key' => 'routes',        'label' => 'Routes & Stops','icon' => 'route',     'href' => 'admin/routes/index.php',        'count' => $sideCounts['routes']],
        ['key' => 'schedules',     'label' => 'Schedules',     'icon' => 'clock',     'href' => 'admin/schedules/index.php',     'count' => $sideCounts['schedules']],
        ['key' => 'announcements', 'label' => 'Announcements', 'icon' => 'megaphone', 'href' => 'admin/announcements/index.php', 'count' => $sideCounts['announcements']],
    ],
    'People' => [
        ['key' => 'users',    'label' => 'Users',    'icon' => 'users', 'href' => 'admin/users/index.php',    'count' => $sideCounts['users']],
        ['key' => 'messages', 'label' => 'Messages', 'icon' => 'inbox', 'href' => 'admin/messages/index.php', 'count' => $sideCounts['messages']],
    ],
    'Account' => [
        ['key' => 'profile', 'label' => 'My Account',  'icon' => 'settings', 'href' => 'admin/profile.php'],
        ['key' => 'site',    'label' => 'View Website','icon' => 'eye',      'href' => 'index.php'],
    ],
];
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <meta name="theme-color" content="#0d2a4f">
    <title><?= e($pageTitle) ?> &middot; Admin &middot; <?= e(SITE_NAME) ?></title>

    <link rel="icon" href="<?= e(url('assets/images/placeholders/favicon.svg')) ?>" type="image/svg+xml">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="<?= e(asset('assets/css/style.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset('assets/css/admin.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset('assets/css/responsive.css')) ?>">
</head>
<body class="admin-body">

<a class="skip-link" href="#adminMain">Skip to main content</a>

<div class="admin">

    <!-- ============================ SIDEBAR ============================ -->
    <aside class="admin-side" id="adminSide" aria-label="Admin navigation">
        <div class="admin-side__head">
            <a class="brand" href="<?= e(url('admin/index.php')) ?>">
                <span class="brand__mark"><?= icon('bus') ?></span>
                <span class="brand__text">
                    <b><?= e(UNIVERSITY_SHORT) ?> Bus</b>
                    <span>Admin Panel</span>
                </span>
            </a>
            <button class="admin-side__close" id="adminSideClose" type="button" aria-label="Close menu">
                <?= icon('close') ?>
            </button>
        </div>

        <?php foreach ($sideNav as $group => $items): ?>
            <div class="admin-side__group">
                <p class="admin-side__label"><?= e($group) ?></p>
                <ul class="admin-side__nav">
                    <?php foreach ($items as $item): ?>
                        <li>
                            <a href="<?= e(url($item['href'])) ?>"
                               class="<?= $adminSection === $item['key'] ? 'active' : '' ?>"
                               <?= $adminSection === $item['key'] ? 'aria-current="page"' : '' ?>>
                                <?= icon($item['icon']) ?>
                                <span><?= e($item['label']) ?></span>
                                <?php if (!empty($item['count'])): ?>
                                    <span class="admin-side__count"><?= e($item['count']) ?></span>
                                <?php endif; ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endforeach; ?>

        <div class="admin-side__foot">
            <div class="admin-side__user">
                <span class="nav-user__avatar" aria-hidden="true">
                    <?= e(strtoupper(mb_substr($admin['full_name'], 0, 1))) ?>
                </span>
                <span>
                    <b><?= e($admin['full_name']) ?></b>
                    <span><?= e($admin['email']) ?></span>
                </span>
            </div>
            <a class="btn btn--accent btn--sm btn--block" href="<?= e(url('logout.php')) ?>">
                <?= icon('logout') ?> Log out
            </a>
        </div>
    </aside>

    <div class="admin-backdrop" id="adminBackdrop"></div>

    <!-- ============================ MAIN ============================ -->
    <div class="admin__main">

        <header class="admin-top">
            <button class="admin-burger" id="adminBurger" type="button"
                    aria-label="Open admin menu" aria-expanded="false" aria-controls="adminSide">
                <?= icon('menu') ?>
            </button>

            <div class="admin-top__title">
                <?= e($pageTitle) ?>
                <span><?= e($pageSubtitle) ?></span>
            </div>

            <div class="admin-top__actions">
                <a class="btn btn--outline btn--sm" href="<?= e(url('index.php')) ?>" target="_blank" rel="noopener">
                    <?= icon('eye') ?> View site
                </a>
            </div>
        </header>

        <main class="admin__content" id="adminMain">
