<?php
/**
 * 403 - ACCESS DENIED
 * Shown by requireAdmin() when a logged-in normal user tries to open an
 * admin page. The restriction is enforced on the server, so hiding the
 * admin links in the navigation is only a convenience, never the protection.
 */
if (!defined('BASE_URL')) {
    require_once __DIR__ . '/includes/init.php';
}

if (!headers_sent()) {
    http_response_code(403);
}

$pageTitle       = 'Access denied';
$pageDescription = 'You do not have permission to open this page.';

require_once __DIR__ . '/includes/header.php';
?>

<section class="section">
    <div class="container container--tight text-center">
        <div class="empty" style="border-style:solid;padding:60px 26px;">
            <span class="empty__icon" style="width:88px;height:88px;background:var(--danger-soft);color:var(--danger);">
                <?= icon('lock', 'icon') ?>
            </span>
            <p class="eyebrow" style="justify-content:center;color:var(--danger);">Error 403</p>
            <h1 style="font-size:clamp(1.6rem,1.2rem+1.5vw,2.2rem);">Access denied</h1>
            <p>
                This area belongs to the Transport Office administrator. Your account
                does not have permission to open it.
            </p>
            <div class="cluster" style="justify-content:center;">
                <a class="btn btn--primary" href="<?= e(url('index.php')) ?>">Back to homepage</a>
                <a class="btn btn--outline" href="<?= e(url('contact.php')) ?>">Contact the office</a>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
