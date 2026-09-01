<?php
/**
 * ADMIN - ROUTES : DELETE
 * The route_stops table uses ON DELETE CASCADE, so the stoppages disappear
 * with the route. The schedules table uses ON DELETE RESTRICT, so a route
 * that still has trips is protected - the check below explains that clearly.
 */
require_once __DIR__ . '/../../includes/init.php';
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    setFlash('error', 'Invalid request.');
    redirect('admin/routes/index.php');
}

requireCsrf('admin/routes/index.php');

$id    = (int) post('id', 0);
$route = $id > 0 ? fetchOne('SELECT id, route_name, route_code FROM routes WHERE id = ?', [$id]) : null;

if (!$route) {
    setFlash('error', 'That route does not exist any more.');
    redirect('admin/routes/index.php');
}

$tripCount = (int) fetchValue('SELECT COUNT(*) FROM schedules WHERE route_id = ?', [$id]);

if ($tripCount > 0) {
    setFlash(
        'warning',
        'This route is used by ' . $tripCount . ' schedule' . ($tripCount === 1 ? '' : 's')
        . '. Remove those trips first, or set the route to Inactive instead.'
    );
    redirect('admin/routes/view.php?id=' . $id);
}

$stopCount = (int) fetchValue('SELECT COUNT(*) FROM route_stops WHERE route_id = ?', [$id]);

try {
    q('DELETE FROM routes WHERE id = ?', [$id]);   // stoppages cascade away

    logActivity(
        'Route deleted',
        'Deleted route "' . $route['route_name'] . '" (' . $route['route_code'] . ') with ' . $stopCount . ' stoppages.'
    );
    setFlash('success', 'Route "' . $route['route_name'] . '" and its ' . $stopCount . ' stoppages were deleted.');

} catch (PDOException $ex) {
    error_log('[ROUTE DELETE] ' . $ex->getMessage());
    setFlash('error', 'Unable to delete the route because other records still depend on it.');
}

redirect('admin/routes/index.php');
