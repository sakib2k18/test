<?php
/**
 * Public site footer: closes <main>, prints the footer, the flash-message
 * toasts, the shared confirmation modal and the JavaScript files.
 */

$flashes = takeFlashes();

/* A few live links for the footer so it is never out of date. */
try {
    $footerRoutes = fetchAll(
        'SELECT id, route_name, route_code FROM routes WHERE status = ? ORDER BY route_code LIMIT 4',
        ['Active']
    );
} catch (Throwable $ex) {
    $footerRoutes = [];
}
?>
</main><!-- /#main -->

<footer class="footer">
    <div class="container">
        <div class="footer__grid">

            <div>
                <span class="brand">
                    <span class="brand__mark"><?= icon('bus') ?></span>
                    <span class="brand__text">
                        <b><?= e(UNIVERSITY_SHORT) ?> Bus Service</b>
                        <span>Transport Portal</span>
                    </span>
                </span>
                <p>
                    The official transport portal of <?= e(UNIVERSITY_NAME) ?>.
                    Check the fleet, routes, stoppages and daily timetable of every
                    university bus from one place.
                </p>
            </div>

            <div>
                <h4>Quick Links</h4>
                <ul>
                    <li><a href="<?= e(url('index.php')) ?>">Home</a></li>
                    <li><a href="<?= e(url('about.php')) ?>">About the Service</a></li>
                    <li><a href="<?= e(url('buses.php')) ?>">Our Buses</a></li>
                    <li><a href="<?= e(url('announcements.php')) ?>">Notices</a></li>
                    <li><a href="<?= e(url('contact.php')) ?>">Contact</a></li>
                </ul>
            </div>

            <div>
                <h4>Popular Routes</h4>
                <ul>
                    <?php if ($footerRoutes): ?>
                        <?php foreach ($footerRoutes as $r): ?>
                            <li>
                                <a href="<?= e(url('route-details.php?id=' . (int) $r['id'])) ?>">
                                    <?= e($r['route_code']) ?> &middot; <?= e($r['route_name']) ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    <li><a href="<?= e(url('routes.php')) ?>">All routes</a></li>
                    <li><a href="<?= e(url('schedules.php')) ?>">Daily schedules</a></li>
                </ul>
            </div>

            <div>
                <h4>Transport Office</h4>
                <ul class="footer__contact">
                    <li><?= icon('pin') ?> <span><?= e(OFFICE_ADDRESS) ?></span></li>
                    <li><?= icon('phone') ?> <a href="tel:<?= e(preg_replace('/[^0-9+]/', '', OFFICE_PHONE)) ?>"><?= e(OFFICE_PHONE) ?></a></li>
                    <li><?= icon('mail') ?> <a href="mailto:<?= e(OFFICE_EMAIL) ?>"><?= e(OFFICE_EMAIL) ?></a></li>
                    <li><?= icon('clock') ?> <span><?= e(OFFICE_HOURS) ?></span></li>
                </ul>
            </div>

        </div>

        <div class="footer__bottom">
            <span>&copy; <?= date('Y') ?> <?= e(UNIVERSITY_NAME) ?>. All rights reserved.</span>
            <span>Built with PHP &amp; MySQL &middot; University project</span>
        </div>
    </div>
</footer>

<!-- Flash messages are printed by the server and animated by main.js -->
<div class="toast-stack" id="toastStack" role="status" aria-live="polite">
    <?php foreach ($flashes as $flash): ?>
        <?php
        $iconName = ['success' => 'check', 'error' => 'alert', 'warning' => 'alert', 'info' => 'info'];
        $type     = in_array($flash['type'], ['success', 'error', 'warning', 'info'], true) ? $flash['type'] : 'info';
        ?>
        <div class="toast toast--<?= e($type) ?>" data-toast>
            <?= icon($iconName[$type]) ?>
            <span><?= e($flash['message']) ?></span>
            <button class="toast__close" type="button" aria-label="Dismiss message"><?= icon('close') ?></button>
        </div>
    <?php endforeach; ?>
</div>

<!-- Shared confirmation dialog, used before every destructive action -->
<div class="modal" id="confirmModal" role="dialog" aria-modal="true" aria-labelledby="confirmTitle" hidden>
    <div class="modal__box">
        <div class="modal__icon"><?= icon('alert') ?></div>
        <h3 id="confirmTitle">Are you sure?</h3>
        <p id="confirmText">This action cannot be undone.</p>
        <div class="modal__actions">
            <button class="btn btn--outline" type="button" data-confirm-cancel>Cancel</button>
            <button class="btn btn--danger" type="button" data-confirm-ok>Yes, continue</button>
        </div>
    </div>
</div>

<script src="<?= e(asset('assets/js/main.js')) ?>"></script>
<script src="<?= e(asset('assets/js/validation.js')) ?>"></script>
</body>
</html>
