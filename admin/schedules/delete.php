<?php
/**
 * ADMIN - SCHEDULES : DELETE
 * Nothing depends on a schedule, so it can always be removed. The request
 * must still be a POST carrying a valid CSRF token.
 */
require_once __DIR__ . '/../../includes/init.php';
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    setFlash('error', 'Invalid request.');
    redirect('admin/schedules/index.php');
}

requireCsrf('admin/schedules/index.php');

$id = (int) post('id', 0);

$schedule = $id > 0 ? fetchOne(
    'SELECT s.id, s.departure_time, b.bus_name, r.route_code
       FROM schedules s
       JOIN buses  b ON b.id = s.bus_id
       JOIN routes r ON r.id = s.route_id
      WHERE s.id = ?',
    [$id]
) : null;

if (!$schedule) {
    setFlash('error', 'That schedule does not exist any more.');
    redirect('admin/schedules/index.php');
}

try {
    q('DELETE FROM schedules WHERE id = ?', [$id]);

    logActivity(
        'Schedule deleted',
        'Deleted trip: ' . $schedule['bus_name'] . ' on ' . $schedule['route_code']
        . ' at ' . timeText($schedule['departure_time']) . '.'
    );
    setFlash('success', 'The schedule entry was deleted.');

} catch (PDOException $ex) {
    error_log('[SCHEDULE DELETE] ' . $ex->getMessage());
    setFlash('error', 'Unable to delete the schedule entry.');
}

redirect('admin/schedules/index.php');
