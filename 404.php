<?php
/**
 * 404 - PAGE NOT FOUND
 * Included by any page that cannot find the requested record, and also used
 * by Apache through the ErrorDocument rule in .htaccess.
 */
if (!defined('BASE_URL')) {
    require_once __DIR__ . '/includes/init.php';
}

if (!headers_sent()) {
    http_response_code(404);
}

$pageTitle       = 'Page not found';
$pageDescription = 'The page you were looking for does not exist.';

require_once __DIR__ . '/includes/header.php';
?>

<section class="section">
    <div class="container container--tight text-center">
        <div class="empty" style="border-style:solid;padding:60px 26px;">
            <span class="empty__icon" style="width:88px;height:88px;">
                <?= icon('search', 'icon') ?>
            </span>
            <p class="eyebrow" style="justify-content:center;">Error 404</p>
            <h1 style="font-size:clamp(1.6rem,1.2rem+1.5vw,2.2rem);">This page took a wrong turn</h1>
            <p>
                The page or record you asked for does not exist any more, or the
                link you followed is out of date.
            </p>
            <img src="<?= e(url('assets/images/road.jpg')) ?>"
                 alt="An empty road stretching into the distance"
                 loading="lazy"
                 style="width:min(420px,100%);border-radius:var(--radius-lg);margin:8px auto 20px;display:block;">
            <div class="cluster" style="justify-content:center;">
                <a class="btn btn--primary" href="<?= e(url('index.php')) ?>">Back to homepage</a>
                <a class="btn btn--outline" href="<?= e(url('buses.php')) ?>">Browse the fleet</a>
                <a class="btn btn--outline" href="<?= e(url('routes.php')) ?>">Browse routes</a>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
