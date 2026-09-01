<?php
/**
 * ADMIN - ANNOUNCEMENTS : PUBLISH / UNPUBLISH
 * A one-click status switch used from the announcement list.
 */
require_once __DIR__ . '/../../includes/init.php';
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    setFlash('error', 'Invalid request.');
    redirect('admin/announcements/index.php');
}

requireCsrf('admin/announcements/index.php');

$id     = (int) post('id', 0);
$notice = $id > 0 ? fetchOne('SELECT id, title, status FROM announcements WHERE id = ?', [$id]) : null;

if (!$notice) {
    setFlash('error', 'That announcement does not exist any more.');
    redirect('admin/announcements/index.php');
}

$newStatus = $notice['status'] === 'Published' ? 'Draft' : 'Published';

try {
    q('UPDATE announcements SET status = ? WHERE id = ?', [$newStatus, $id]);

    logActivity(
        $newStatus === 'Published' ? 'Announcement published' : 'Announcement unpublished',
        ($newStatus === 'Published' ? 'Published' : 'Unpublished') . ' the notice "' . $notice['title'] . '".'
    );

    setFlash(
        'success',
        $newStatus === 'Published'
            ? 'The announcement is now visible on the public site.'
            : 'The announcement was moved back to drafts and is hidden from visitors.'
    );

} catch (PDOException $ex) {
    error_log('[ANNOUNCEMENT TOGGLE] ' . $ex->getMessage());
    setFlash('error', 'Unable to change the status of the announcement.');
}

redirect('admin/announcements/index.php');
