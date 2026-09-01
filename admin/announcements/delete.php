<?php
/**
 * ADMIN - ANNOUNCEMENTS : DELETE
 */
require_once __DIR__ . '/../../includes/init.php';
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    setFlash('error', 'Invalid request.');
    redirect('admin/announcements/index.php');
}

requireCsrf('admin/announcements/index.php');

$id     = (int) post('id', 0);
$notice = $id > 0 ? fetchOne('SELECT id, title FROM announcements WHERE id = ?', [$id]) : null;

if (!$notice) {
    setFlash('error', 'That announcement does not exist any more.');
    redirect('admin/announcements/index.php');
}

try {
    q('DELETE FROM announcements WHERE id = ?', [$id]);
    logActivity('Announcement deleted', 'Deleted the notice "' . $notice['title'] . '".');
    setFlash('success', 'The announcement was deleted.');
} catch (PDOException $ex) {
    error_log('[ANNOUNCEMENT DELETE] ' . $ex->getMessage());
    setFlash('error', 'Unable to delete the announcement.');
}

redirect('admin/announcements/index.php');
