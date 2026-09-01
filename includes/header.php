<?php
/**
 * Public site header: <head>, sticky navbar and the mobile off-canvas drawer.
 * A page sets $pageTitle / $pageDescription before including this file.
 */

if (!defined('BASE_URL')) {
    require_once __DIR__ . '/init.php';
}

$pageTitle       = $pageTitle       ?? SITE_NAME;
$pageDescription = $pageDescription ?? SITE_DESCRIPTION;
$user            = currentUser();

/* Main navigation - one array drives both the desktop bar and the drawer. */
$navItems = [
    ['file' => 'index.php',         'label' => 'Home',      'icon' => 'grid'],
    ['file' => 'buses.php',         'label' => 'Buses',     'icon' => 'bus'],
    ['file' => 'routes.php',        'label' => 'Routes',    'icon' => 'route'],
    ['file' => 'schedules.php',     'label' => 'Schedules', 'icon' => 'clock'],
    ['file' => 'announcements.php', 'label' => 'Notices',   'icon' => 'megaphone'],
    ['file' => 'about.php',         'label' => 'About',     'icon' => 'info'],
    ['file' => 'contact.php',       'label' => 'Contact',   'icon' => 'mail'],
];

/* Detail pages should keep their parent nav item highlighted. */
$navAliases = [
    'bus-details.php'          => 'buses.php',
    'route-details.php'        => 'routes.php',
    'announcement-details.php' => 'announcements.php',
];
$activeNav = $navAliases[currentPage()] ?? currentPage();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="<?= e($pageDescription) ?>">
    <meta name="theme-color" content="#0d2a4f">
    <title><?= e($pageTitle) ?> &middot; <?= e(SITE_NAME) ?></title>

    <link rel="icon" href="<?= e(url('assets/images/placeholders/favicon.svg')) ?>" type="image/svg+xml">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="<?= e(asset('assets/css/style.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset('assets/css/responsive.css')) ?>">
</head>
<body class="site">

<a class="skip-link" href="#main">Skip to main content</a>

<header class="navbar" id="navbar">
    <div class="container navbar__inner">

        <a class="brand" href="<?= e(url('index.php')) ?>">
            <span class="brand__mark"><?= icon('bus') ?></span>
            <span class="brand__text">
                <b><?= e(UNIVERSITY_SHORT) ?> Bus Service</b>
                <span>Transport Portal</span>
            </span>
        </a>

        <nav aria-label="Main navigation">
            <ul class="nav-links">
                <?php foreach ($navItems as $item): ?>
                    <li>
                        <a href="<?= e(url($item['file'])) ?>"
                           class="<?= $activeNav === $item['file'] ? 'active' : '' ?>"
                           <?= $activeNav === $item['file'] ? 'aria-current="page"' : '' ?>>
                            <?= e($item['label']) ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </nav>

        <div class="nav-actions">
            <?php if ($user): ?>
                <?php if (isAdmin()): ?>
                    <a class="btn btn--outline btn--sm" href="<?= e(url('admin/index.php')) ?>">
                        <?= icon('settings') ?> Admin Panel
                    </a>
                <?php endif; ?>
                <span class="nav-user" title="<?= e($user['email']) ?>">
                    <span class="nav-user__avatar" aria-hidden="true"><?= e(strtoupper(mb_substr($user['full_name'], 0, 1))) ?></span>
                    <?= e(explode(' ', $user['full_name'])[0]) ?>
                </span>
                <a class="btn btn--primary btn--sm" href="<?= e(url('logout.php')) ?>">
                    <?= icon('logout') ?> Logout
                </a>
            <?php else: ?>
                <a class="btn btn--outline btn--sm" href="<?= e(url('login.php')) ?>">Log in</a>
                <a class="btn btn--primary btn--sm" href="<?= e(url('register.php')) ?>">Sign up</a>
            <?php endif; ?>
        </div>

        <button class="nav-toggle" id="navToggle" type="button"
                aria-label="Open navigation menu" aria-expanded="false" aria-controls="navDrawer">
            <?= icon('menu') ?>
        </button>
    </div>
</header>

<!-- Mobile off-canvas navigation -->
<div class="nav-backdrop" id="navBackdrop"></div>

<aside class="nav-drawer" id="navDrawer" aria-label="Mobile navigation" aria-hidden="true">
    <div class="nav-drawer__head">
        <span class="brand">
            <span class="brand__mark"><?= icon('bus') ?></span>
            <span class="brand__text">
                <b><?= e(UNIVERSITY_SHORT) ?> Bus</b>
                <span>Menu</span>
            </span>
        </span>
        <button class="icon-btn" id="navClose" type="button" aria-label="Close navigation menu">
            <?= icon('close') ?>
        </button>
    </div>

    <nav>
        <ul class="nav-drawer__list">
            <?php foreach ($navItems as $item): ?>
                <li>
                    <a href="<?= e(url($item['file'])) ?>" class="<?= $activeNav === $item['file'] ? 'active' : '' ?>">
                        <?= icon($item['icon']) ?> <?= e($item['label']) ?>
                    </a>
                </li>
            <?php endforeach; ?>
            <?php if (isAdmin()): ?>
                <li>
                    <a href="<?= e(url('admin/index.php')) ?>"><?= icon('settings') ?> Admin Panel</a>
                </li>
            <?php endif; ?>
        </ul>
    </nav>

    <div class="nav-drawer__foot">
        <?php if ($user): ?>
            <span class="nav-user">
                <span class="nav-user__avatar" aria-hidden="true"><?= e(strtoupper(mb_substr($user['full_name'], 0, 1))) ?></span>
                <?= e($user['full_name']) ?>
            </span>
            <a class="btn btn--primary btn--block" href="<?= e(url('logout.php')) ?>"><?= icon('logout') ?> Logout</a>
        <?php else: ?>
            <a class="btn btn--outline btn--block" href="<?= e(url('login.php')) ?>"><?= icon('login') ?> Log in</a>
            <a class="btn btn--primary btn--block" href="<?= e(url('register.php')) ?>"><?= icon('user') ?> Sign up</a>
        <?php endif; ?>
    </div>
</aside>

<main id="main" class="page">
