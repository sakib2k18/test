<?php
/**
 * ADMIN - ROUTE STOPS : DELETE
 * After removing a stop the remaining positions are renumbered so the list
 * stays 1, 2, 3, ... without gaps.
 */
require_once __DIR__ . '/../../includes/init.php';
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('admin/routes/index.php');
}

$id   = (int) post('id', 0);
$stop = $id > 0 ? fetchOne('SELECT * FROM route_stops WHERE id = ?', [$id]) : null;

if (!$stop) {
    setFlash('error', 'That stoppage does not exist any more.');
    redirect('admin/routes/index.php');
}

$routeId = (int) $stop['route_id'];
requireCsrf('admin/routes/view.php?id=' . $routeId);

try {
    q('DELETE FROM route_stops WHERE id = ?', [$id]);

    /* Renumber what is left. */
    $remaining = fetchAll(
        'SELECT id FROM route_stops WHERE route_id = ? ORDER BY stop_order ASC, id ASC',
        [$routeId]
    );
    foreach ($remaining as $index => $row) {
        q('UPDATE route_stops SET stop_order = ? WHERE id = ?', [$index + 1, (int) $row['id']]);
    }

    logActivity('Stop deleted', 'Deleted stoppage "' . $stop['stop_name'] . '".');
    setFlash('success', 'Stoppage "' . $stop['stop_name'] . '" was deleted.');

} catch (PDOException $ex) {
    error_log('[STOP DELETE] ' . $ex->getMessage());
    setFlash('error', 'Unable to delete the stoppage.');
}

redirect('admin/routes/view.php?id=' . $routeId);
