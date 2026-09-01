<?php
/**
 * ADMIN LAYOUT - CLOSING MARKUP, TOASTS, CONFIRM MODAL AND SCRIPTS
 */
$flashes = takeFlashes();
?>
        </main><!-- /.admin__content -->

        <footer class="admin__content" style="padding-top:0;">
            <p class="text-muted" style="font-size:.78rem;border-top:1px solid var(--border);padding-top:16px;margin:0;">
                &copy; <?= date('Y') ?> <?= e(UNIVERSITY_NAME) ?> &middot;
                <?= e(SITE_NAME) ?> admin panel &middot;
                Database: <code><?= e(DB_NAME) ?></code> on port <code><?= e(DB_PORT) ?></code>
            </p>
        </footer>

    </div><!-- /.admin__main -->
</div><!-- /.admin -->

<!-- Flash messages -->
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

<!-- Confirmation dialog used by every delete button -->
<div class="modal" id="confirmModal" role="dialog" aria-modal="true" aria-labelledby="confirmTitle" hidden>
    <div class="modal__box">
        <div class="modal__icon"><?= icon('alert') ?></div>
        <h3 id="confirmTitle">Please confirm</h3>
        <p id="confirmText">This action cannot be undone.</p>
        <div class="modal__actions">
            <button class="btn btn--outline" type="button" data-confirm-cancel>Cancel</button>
            <button class="btn btn--danger" type="button" data-confirm-ok>Yes, delete</button>
        </div>
    </div>
</div>

<script src="<?= e(asset('assets/js/main.js')) ?>"></script>
<script src="<?= e(asset('assets/js/validation.js')) ?>"></script>
<script src="<?= e(asset('assets/js/admin.js')) ?>"></script>
</body>
</html>
