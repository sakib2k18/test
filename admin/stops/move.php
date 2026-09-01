<?php
/**
 * ADMIN - ROUTE STOPS : REORDER
 * Swaps the position of a stop with the one above or below it. Both rows are
 * written inside a transaction so the list can never end up half-updated.
 */
require_once __DIR__ . '/../../includes/init.php';
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('admin/routes/index.php');
}

$id        = (int) post('id', 0);
$direction = post('direction');
$stop      = $id > 0 ? fetchOne('SELECT * FROM route_stops WHERE id = ?', [$id]) : null;

if (!$stop || !inList($direction, ['up', 'down'])) {
    setFlash('error', 'Invalid request.');
    redirect('admin/routes/index.php');
}

$routeId = (int) $stop['route_id'];
requireCsrf('admin/routes/view.php?id=' . $routeId);

/* Find the neighbour to swap with. */
$neighbour = $direction === 'up'
    ? fetchOne(
        'SELECT id, stop_order FROM route_stops
          WHERE route_id = ? AND stop_order < ?
          ORDER BY stop_order DESC LIMIT 1',
        [$routeId, (int) $stop['stop_order']]
    )
    : fetchOne(
        'SELECT id, stop_order FROM route_stops
          WHERE route_id = ? AND stop_order > ?
          ORDER BY stop_order ASC LIMIT 1',
        [$routeId, (int) $stop['stop_order']]
    );

if (!$neighbour) {
    setFlash('info', 'The stoppage is already at the ' . ($direction === 'up' ? 'beginning' : 'end') . ' of the route.');
    redirect('admin/routes/view.php?id=' . $routeId);
}

try {
    db()->beginTransaction();
    q('UPDATE route_stops SET stop_order = ? WHERE id = ?', [(int) $neighbour['stop_order'], $id]);
    q('UPDATE route_stops SET stop_order = ? WHERE id = ?', [(int) $stop['stop_order'], (int) $neighbour['id']]);
    db()->commit();

    logActivity('Stop reordered', 'Moved stoppage "' . $stop['stop_name'] . '" ' . $direction . '.');
    setFlash('success', 'Stoppage order updated.');

} catch (PDOException $ex) {
    if (db()->inTransaction()) {
        db()->rollBack();
    }
    error_log('[STOP MOVE] ' . $ex->getMessage());
    setFlash('error', 'The stoppage could not be moved.');
}

redirect('admin/routes/view.php?id=' . $routeId);
