<?php
/**
 * ADMIN - BUSES : DELETE
 * ---------------------------------------------------------------------------
 * Deleting is only accepted through a POST request with a valid CSRF token,
 * so a bus can never be removed by simply visiting a link.
 *
 * Relational integrity: the schedules table references buses with
 * ON DELETE RESTRICT. The check below turns that database rule into a clear
 * warning instead of a raw SQL error.
 * ---------------------------------------------------------------------------
 */
require_once __DIR__ . '/../../includes/init.php';
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    setFlash('error', 'Invalid request.');
    redirect('admin/buses/index.php');
}

requireCsrf('admin/buses/index.php');

$id  = (int) post('id', 0);
$bus = $id > 0 ? fetchOne('SELECT id, bus_name, reg_number, image FROM buses WHERE id = ?', [$id]) : null;

if (!$bus) {
    setFlash('error', 'That bus does not exist any more.');
    redirect('admin/buses/index.php');
}

/* Is the bus still used by the timetable? */
$tripCount = (int) fetchValue('SELECT COUNT(*) FROM schedules WHERE bus_id = ?', [$id]);

if ($tripCount > 0) {
    setFlash(
        'warning',
        'This bus is currently assigned to ' . $tripCount . ' active schedule'
        . ($tripCount === 1 ? '' : 's') . '. Delete those trips first, or set the bus to Inactive instead.'
    );
    redirect('admin/buses/view.php?id=' . $id);
}

try {
    q('DELETE FROM buses WHERE id = ?', [$id]);
    deleteBusImage($bus['image']);          // remove the uploaded photo too

    logActivity('Bus deleted', 'Deleted bus "' . $bus['bus_name'] . '" (' . $bus['reg_number'] . ').');
    setFlash('success', 'Bus "' . $bus['bus_name'] . '" was deleted.');

} catch (PDOException $ex) {
    error_log('[BUS DELETE] ' . $ex->getMessage());
    setFlash('error', 'Unable to delete the bus because other records still depend on it.');
}

redirect('admin/buses/index.php');
