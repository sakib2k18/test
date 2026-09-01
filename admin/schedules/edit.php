<?php
/**
 * ADMIN - SCHEDULES : UPDATE
 */
require_once __DIR__ . '/../../includes/init.php';
requireAdmin();
require_once __DIR__ . '/_validate.php';

$pageTitle    = 'Edit schedule';
$pageSubtitle = 'Update an existing trip';
$adminSection = 'schedules';

$id      = (int) ($_SERVER['REQUEST_METHOD'] === 'POST' ? post('id', 0) : get('id', 0));
$current = $id > 0 ? fetchOne('SELECT * FROM schedules WHERE id = ?', [$id]) : null;

if (!$current) {
    setFlash('error', 'That schedule does not exist any more.');
    redirect('admin/schedules/index.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    requireCsrf('admin/schedules/edit.php?id=' . $id);

    $input = [
        'bus_id'         => post('bus_id'),
        'route_id'       => post('route_id'),
        'departure_time' => post('departure_time'),
        'arrival_time'   => post('arrival_time'),
        'operating_days' => (array) ($_POST['operating_days'] ?? []),
        'status'         => post('status'),
        'notes'          => post('notes'),
    ];

    $errors = validateScheduleInput($input);

    if ($errors) {
        keepErrors($errors);
        keepOld(array_merge($_POST, ['operating_days' => $input['operating_days']]));
        setFlash('error', 'The schedule could not be updated. Please check the form.');
        redirect('admin/schedules/edit.php?id=' . $id);
    }

    $days  = normaliseDays($input['operating_days']);
    $clash = findScheduleClash(
        (int) $input['bus_id'],
        $input['departure_time'],
        $input['arrival_time'],
        explode(',', $days),
        $id
    );

    try {
        q(
            'UPDATE schedules
                SET bus_id = ?, route_id = ?, departure_time = ?, arrival_time = ?,
                    operating_days = ?, status = ?, notes = ?
              WHERE id = ?',
            [
                (int) $input['bus_id'],
                (int) $input['route_id'],
                $input['departure_time'],
                $input['arrival_time'],
                $days,
                $input['status'],
                $input['notes'] !== '' ? $input['notes'] : null,
                $id,
            ]
        );

        $bus   = fetchOne('SELECT bus_name FROM buses WHERE id = ?', [(int) $input['bus_id']]);
        $route = fetchOne('SELECT route_code FROM routes WHERE id = ?', [(int) $input['route_id']]);

        logActivity(
            'Schedule updated',
            'Updated trip: ' . $bus['bus_name'] . ' on ' . $route['route_code']
            . ' at ' . timeText($input['departure_time']) . '.'
        );

        setFlash('success', 'The schedule was updated successfully.');
        if ($clash) {
            setFlash('warning', 'Please double-check: ' . $clash);
        }
        redirect('admin/schedules/index.php');

    } catch (PDOException $ex) {
        error_log('[SCHEDULE EDIT] ' . $ex->getMessage());
        keepErrors(['The schedule could not be updated because of a database error.']);
        keepOld($_POST);
        setFlash('error', 'The schedule could not be updated.');
        redirect('admin/schedules/edit.php?id=' . $id);
    }
}

$errors    = takeErrors();
$hasOld    = !empty($_SESSION['old']);
$allBuses  = fetchAll('SELECT id, bus_name, bus_type, capacity, status FROM buses ORDER BY bus_name');
$allRoutes = fetchAll('SELECT id, route_name, route_code, status FROM routes ORDER BY route_code');

$schedule = [
    'id'             => $id,
    'bus_id'         => (int) ($hasOld ? old('bus_id')   : $current['bus_id']),
    'route_id'       => (int) ($hasOld ? old('route_id') : $current['route_id']),
    'departure_time' => $hasOld ? old('departure_time') : substr((string) $current['departure_time'], 0, 5),
    'arrival_time'   => $hasOld ? old('arrival_time')   : substr((string) $current['arrival_time'], 0, 5),
    'status'         => $hasOld ? old('status')         : $current['status'],
    'notes'          => $hasOld ? old('notes')          : (string) $current['notes'],
];

$selectedDays = $hasOld
    ? (array) old('operating_days', [])
    : array_map('trim', explode(',', (string) $current['operating_days']));

$formAction  = url('admin/schedules/edit.php?id=' . $id);
$submitLabel = 'Update schedule';
$isEdit      = true;

require_once __DIR__ . '/../../includes/admin_header.php';
?>

<div class="admin-head">
    <div>
        <h1>Edit schedule</h1>
        <p>Created <?= e(dateText($current['created_at'])) ?> &middot; last updated <?= e(dateText($current['updated_at'])) ?>.</p>
    </div>
    <div class="admin-head__actions">
        <a class="btn btn--outline" href="<?= e(url('admin/schedules/index.php')) ?>">Back to schedules</a>
    </div>
</div>

<?php
require __DIR__ . '/_form.php';
clearOld();
require_once __DIR__ . '/../../includes/admin_footer.php';
?>
