<?php
/**
 * ADMIN - ROUTES : UPDATE
 */
require_once __DIR__ . '/../../includes/init.php';
requireAdmin();
require_once __DIR__ . '/_validate.php';

$pageTitle    = 'Edit route';
$pageSubtitle = 'Update an existing route';
$adminSection = 'routes';

$id      = (int) ($_SERVER['REQUEST_METHOD'] === 'POST' ? post('id', 0) : get('id', 0));
$current = $id > 0 ? fetchOne('SELECT * FROM routes WHERE id = ?', [$id]) : null;

if (!$current) {
    setFlash('error', 'That route does not exist any more.');
    redirect('admin/routes/index.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    requireCsrf('admin/routes/edit.php?id=' . $id);

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

    $errors = validateRouteInput($input, $id);

    if ($errors) {
        keepErrors($errors);
        keepOld($_POST);
        setFlash('error', 'The route could not be updated. Please check the form.');
        redirect('admin/routes/edit.php?id=' . $id);
    }

    try {
        q(
            'UPDATE routes
                SET route_name = ?, route_code = ?, start_point = ?, destination = ?,
                    description = ?, distance_km = ?, duration_min = ?, status = ?
              WHERE id = ?',
            [
                $input['route_name'],
                $input['route_code'],
                $input['start_point'],
                $input['destination'],
                $input['description'] !== '' ? $input['description'] : null,
                (float) $input['distance_km'],
                (int) $input['duration_min'],
                $input['status'],
                $id,
            ]
        );

        logActivity('Route updated', 'Updated route "' . $input['route_name'] . '" (' . $input['route_code'] . ').');
        setFlash('success', 'Route "' . $input['route_name'] . '" was updated successfully.');
        redirect('admin/routes/view.php?id=' . $id);

    } catch (PDOException $ex) {
        error_log('[ROUTE EDIT] ' . $ex->getMessage());
        keepErrors([
            $ex->getCode() === '23000'
                ? 'Another route already uses this route code.'
                : 'The route could not be updated because of a database error.',
        ]);
        keepOld($_POST);
        setFlash('error', 'The route could not be updated.');
        redirect('admin/routes/edit.php?id=' . $id);
    }
}

$errors = takeErrors();
$hasOld = !empty($_SESSION['old']);

$route = [
    'id'           => $id,
    'route_name'   => $hasOld ? old('route_name')   : $current['route_name'],
    'route_code'   => $hasOld ? old('route_code')   : $current['route_code'],
    'start_point'  => $hasOld ? old('start_point')  : $current['start_point'],
    'destination'  => $hasOld ? old('destination')  : $current['destination'],
    'description'  => $hasOld ? old('description')  : (string) $current['description'],
    'distance_km'  => $hasOld ? old('distance_km')  : $current['distance_km'],
    'duration_min' => $hasOld ? old('duration_min') : $current['duration_min'],
    'status'       => $hasOld ? old('status')       : $current['status'],
];

$formAction  = url('admin/routes/edit.php?id=' . $id);
$submitLabel = 'Update route';
$isEdit      = true;

require_once __DIR__ . '/../../includes/admin_header.php';
?>

<div class="admin-head">
    <div>
        <h1>Edit <?= e($current['route_name']) ?></h1>
        <p>Route code <?= e($current['route_code']) ?> &middot; created <?= e(dateText($current['created_at'])) ?>.</p>
    </div>
    <div class="admin-head__actions">
        <a class="btn btn--outline" href="<?= e(url('admin/routes/view.php?id=' . $id)) ?>">
            <?= icon('pin') ?> Manage stoppages
        </a>
        <a class="btn btn--outline" href="<?= e(url('admin/routes/index.php')) ?>">Back to routes</a>
    </div>
</div>

<?php
require __DIR__ . '/_form.php';
clearOld();
require_once __DIR__ . '/../../includes/admin_footer.php';
?>
