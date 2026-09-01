<?php
/**
 * ADMIN - CONTACT MESSAGES : READ, MARK AS READ, DELETE
 * These are the messages sent through the public contact form.
 */
require_once __DIR__ . '/../../includes/init.php';
requireAdmin();

$pageTitle    = 'Messages';
$pageSubtitle = 'Messages from the contact form';
$adminSection = 'messages';

/* ---------------- Actions (mark read / unread / delete) ---------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    requireCsrf('admin/messages/index.php');

    $id      = (int) post('id', 0);
    $action  = post('action');
    $message = $id > 0 ? fetchOne('SELECT id, name, subject FROM contact_messages WHERE id = ?', [$id]) : null;

    if (!$message) {
        setFlash('error', 'That message does not exist any more.');
        redirect('admin/messages/index.php');
    }

    try {
        if ($action === 'read') {
            q('UPDATE contact_messages SET is_read = 1 WHERE id = ?', [$id]);
            setFlash('success', 'Message marked as read.');

        } elseif ($action === 'unread') {
            q('UPDATE contact_messages SET is_read = 0 WHERE id = ?', [$id]);
            setFlash('success', 'Message marked as unread.');

        } elseif ($action === 'delete') {
            q('DELETE FROM contact_messages WHERE id = ?', [$id]);
            logActivity('Message deleted', 'Deleted the message "' . $message['subject'] . '" from ' . $message['name'] . '.');
            setFlash('success', 'The message was deleted.');

        } else {
            setFlash('error', 'Unknown action.');
        }
    } catch (PDOException $ex) {
        error_log('[MESSAGE ACTION] ' . $ex->getMessage());
        setFlash('error', 'The action could not be completed.');
    }

    redirect('admin/messages/index.php');
}

/* ---------------- List ---------------- */
$filter = get('filter');
if (!inList($filter, ['unread', 'read'])) { $filter = ''; }

$where  = [];
$params = [];

if ($filter === 'unread') { $where[] = 'is_read = 0'; }
if ($filter === 'read')   { $where[] = 'is_read = 1'; }

$whereSql = $where ? ' WHERE ' . implode(' AND ', $where) : '';

$messages = fetchAll(
    'SELECT * FROM contact_messages' . $whereSql . ' ORDER BY is_read ASC, created_at DESC',
    $params
);

$unread = (int) fetchValue('SELECT COUNT(*) FROM contact_messages WHERE is_read = 0');

require_once __DIR__ . '/../../includes/admin_header.php';
?>

<div class="admin-head">
    <div>
        <h1>Messages</h1>
        <p><?= e(count($messages)) ?> message<?= count($messages) === 1 ? '' : 's' ?> &middot; <?= e($unread) ?> unread.</p>
    </div>
    <div class="admin-head__actions">
        <a class="chip <?= $filter === '' ? 'chip--accent' : 'chip--outline' ?>"
           href="<?= e(url('admin/messages/index.php')) ?>">All</a>
        <a class="chip <?= $filter === 'unread' ? 'chip--accent' : 'chip--outline' ?>"
           href="<?= e(url('admin/messages/index.php?filter=unread')) ?>">Unread</a>
        <a class="chip <?= $filter === 'read' ? 'chip--accent' : 'chip--outline' ?>"
           href="<?= e(url('admin/messages/index.php?filter=read')) ?>">Read</a>
    </div>
</div>

<?php if ($messages): ?>
    <div class="stack" style="gap:14px;">
        <?php foreach ($messages as $m): ?>
            <article class="panel" id="msg-<?= (int) $m['id'] ?>">
                <div class="panel__head">
                    <div>
                        <h2>
                            <?= e($m['subject']) ?>
                            <?php if (!(int) $m['is_read']): ?>
                                <span class="badge badge--warning">New</span>
                            <?php endif; ?>
                        </h2>
                        <p>
                            From <strong><?= e($m['name']) ?></strong>
                            &lt;<a href="mailto:<?= e($m['email']) ?>"><?= e($m['email']) ?></a>&gt;
                            &middot; <?= e(date('d M Y, g:i A', strtotime($m['created_at']))) ?>
                        </p>
                    </div>

                    <div class="cluster" style="gap:6px;">
                        <a class="btn btn--outline btn--sm"
                           href="mailto:<?= e($m['email']) ?>?subject=<?= e(rawurlencode('Re: ' . $m['subject'])) ?>">
                            <?= icon('mail') ?> Reply
                        </a>

                        <form method="post" action="<?= e(url('admin/messages/index.php')) ?>">
                            <?= csrfField() ?>
                            <input type="hidden" name="id" value="<?= (int) $m['id'] ?>">
                            <input type="hidden" name="action" value="<?= (int) $m['is_read'] ? 'unread' : 'read' ?>">
                            <button class="btn btn--outline btn--sm" type="submit">
                                <?= icon((int) $m['is_read'] ? 'close' : 'check') ?>
                                Mark <?= (int) $m['is_read'] ? 'unread' : 'read' ?>
                            </button>
                        </form>

                        <form method="post" action="<?= e(url('admin/messages/index.php')) ?>"
                              data-confirm="Delete the message from <?= e($m['name']) ?>?">
                            <?= csrfField() ?>
                            <input type="hidden" name="id" value="<?= (int) $m['id'] ?>">
                            <input type="hidden" name="action" value="delete">
                            <button class="icon-btn icon-btn--danger" type="submit" title="Delete message">
                                <?= icon('trash') ?><span class="sr-only">Delete message</span>
                            </button>
                        </form>
                    </div>
                </div>
                <div class="panel__body">
                    <p style="white-space:pre-line;color:var(--muted);font-size:.94rem;margin:0;"><?= e($m['message']) ?></p>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
<?php else: ?>
    <div class="empty">
        <span class="empty__icon"><?= icon('inbox') ?></span>
        <h3><?= $filter !== '' ? 'No ' . e($filter) . ' messages' : 'No messages yet' ?></h3>
        <p>
            <?= $filter !== ''
                ? 'Nothing to show with this filter.'
                : 'Messages sent through the public contact form will appear here.' ?>
        </p>
        <?php if ($filter !== ''): ?>
            <a class="btn btn--outline" href="<?= e(url('admin/messages/index.php')) ?>">Show all messages</a>
        <?php else: ?>
            <a class="btn btn--primary" href="<?= e(url('contact.php')) ?>" target="_blank" rel="noopener">
                Open the contact page
            </a>
        <?php endif; ?>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../../includes/admin_footer.php'; ?>
