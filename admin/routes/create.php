<?php
/**
 * ADMIN - ROUTES : CREATE
 */
require_once __DIR__ . '/../../includes/init.php';
requireAdmin();
require_once __DIR__ . '/_validate.php';

$pageTitle    = 'Add route';
$pageSubtitle = 'Create a new bus route';
$adminSection = 'routes';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    requireCsrf('admin/routes/create.php');

    $input = [
        'route_name'   => post('route_name'),
        'route_code'   => strtoupper(post('route_code')),
        'start_point'  => post('start_point'),
        'destination'  => post('destination'),
        'description'  => post('description'),
        'distance_km'  => post('distance_km'),
        'duration_min' => post('duration_min'),
        'status'       => post('status'),
    ];

    $errors = validateRouteInput($input);

    if ($errors) {
        keepErrors($errors);
        keepOld($_POST);
        setFlash('error', 'The route could not be added. Please check the form.');
        redirect('admin/routes/create.php');
    }

    try {
        q(
            'INSERT INTO routes
               (route_name, route_code, start_point, destination, description,
                distance_km, duration_min, status)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
            [
                $input['route_name'],
                $input['route_code'],
                $input['start_point'],
                $input['destination'],
                $input['description'] !== '' ? $input['description'] : null,
                (float) $input['distance_km'],
                (int) $input['duration_min'],
                $input['status'],
            ]
        );

        $routeId = (int) db()->lastInsertId();

        logActivity('Route added', 'Added route "' . $input['route_name'] . '" (' . $input['route_code'] . ').');
        setFlash('success', 'Route "' . $input['route_name'] . '" was added. Now add its stoppages below.');
        redirect('admin/routes/view.php?id=' . $routeId);

    } catch (PDOException $ex) {
        error_log('[ROUTE CREATE] ' . $ex->getMessage());
        keepErrors([
            $ex->getCode() === '23000'
                ? 'Another route already uses this route code.'
                : 'The route could not be saved because of a database error.',
        ]);
        keepOld($_POST);
        setFlash('error', 'The route could not be added.');
        redirect('admin/routes/create.php');
    }
}

$errors = takeErrors();

$route = [
    'id'           => 0,
    'route_name'   => old('route_name'),
    'route_code'   => old('route_code'),
    'start_point'  => old('start_point', 'KUET Main Campus'),
    'destination'  => old('destination'),
    'description'  => old('description'),
    'distance_km'  => old('distance_km'),
    'duration_min' => old('duration_min'),
    'status'       => old('status', 'Active'),
];

$formAction  = url('admin/routes/create.php');
$submitLabel = 'Save route';
$isEdit      = false;

require_once __DIR__ . '/../../includes/admin_header.php';
?>

<div class="admin-head">
    <div>
        <h1>Add a new route</h1>
        <p>Describe the corridor first; the individual stoppages are added afterwards.</p>
    </div>
    <div class="admin-head__actions">
        <a class="btn btn--outline" href="<?= e(url('admin/routes/index.php')) ?>">Back to routes</a>
    </div>
</div>

<?php
require __DIR__ . '/_form.php';
clearOld();
require_once __DIR__ . '/../../includes/admin_footer.php';
?>
