<?php
/**
 * ADMIN - USERS : DELETE
 * Two safety rules are enforced here:
 *   1. an administrator account can never be deleted (there is only one),
 *   2. the logged-in admin can never delete their own account.
 */
require_once __DIR__ . '/../../includes/init.php';
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    setFlash('error', 'Invalid request.');
    redirect('admin/users/index.php');
}

requireCsrf('admin/users/index.php');

$id   = (int) post('id', 0);
$user = $id > 0 ? fetchOne('SELECT id, full_name, email, role FROM users WHERE id = ?', [$id]) : null;

if (!$user) {
    setFlash('error', 'That account does not exist any more.');
    redirect('admin/users/index.php');
}

if ($user['role'] === 'admin') {
    setFlash('warning', 'The administrator account is protected and cannot be deleted.');
    redirect('admin/users/index.php');
}

if ((int) $user['id'] === (int) ($_SESSION['user_id'] ?? 0)) {
    setFlash('warning', 'You cannot delete the account you are currently logged in with.');
    redirect('admin/users/index.php');
}

try {
    // activity_logs.user_id is ON DELETE SET NULL, so the log history survives.
    q('DELETE FROM users WHERE id = ?', [$id]);
    logActivity('User deleted', 'Deleted the account of ' . $user['full_name'] . ' (' . $user['email'] . ').');
    setFlash('success', 'The account of ' . $user['full_name'] . ' was deleted.');
} catch (PDOException $ex) {
    error_log('[USER DELETE] ' . $ex->getMessage());
    setFlash('error', 'Unable to delete this account.');
}

redirect('admin/users/index.php');
