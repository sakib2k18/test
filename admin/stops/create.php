<?php
/**
 * ADMIN - ROUTE STOPS : CREATE
 * The form lives on admin/routes/view.php; this file only handles the POST.
 */
require_once __DIR__ . '/../../includes/init.php';
requireAdmin();
require_once __DIR__ . '/../routes/_validate.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('admin/routes/index.php');
}

$routeId = (int) post('route_id', 0);
$route   = $routeId > 0 ? fetchOne('SELECT id, route_name FROM routes WHERE id = ?', [$routeId]) : null;

if (!$route) {
    setFlash('error', 'That route does not exist any more.');
    redirect('admin/routes/index.php');
}

requireCsrf('admin/routes/view.php?id=' . $routeId);

$input = [
    'stop_name'        => post('stop_name'),
    'stop_description' => post('stop_description'),
    'arrival_time'     => post('arrival_time'),
];

$errors = validateStopInput($input);

if ($errors) {
    keepErrors($errors);
    keepOld($_POST);
    setFlash('error', 'The stoppage could not be added.');
    redirect('admin/routes/view.php?id=' . $routeId);
}

try {
    /* New stops go to the end of the list. */
    $nextOrder = (int) fetchValue(
        'SELECT COALESCE(MAX(stop_order), 0) + 1 FROM route_stops WHERE route_id = ?',
        [$routeId]
    );

    q(
        'INSERT INTO route_stops (route_id, stop_name, stop_description, stop_order, arrival_time)
         VALUES (?, ?, ?, ?, ?)',
        [
            $routeId,
            $input['stop_name'],
            $input['stop_description'] !== '' ? $input['stop_description'] : null,
            $nextOrder,
            $input['arrival_time'] !== '' ? $input['arrival_time'] : null,
        ]
    );

    logActivity('Stop added', 'Added stoppage "' . $input['stop_name'] . '" to route "' . $route['route_name'] . '".');
    setFlash('success', 'Stoppage "' . $input['stop_name'] . '" was added.');

} catch (PDOException $ex) {
    error_log('[STOP CREATE] ' . $ex->getMessage());
    keepOld($_POST);
    setFlash('error', 'The stoppage could not be saved because of a database error.');
}

redirect('admin/routes/view.php?id=' . $routeId);
