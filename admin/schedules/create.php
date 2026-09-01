<?php
/**
 * ADMIN - SCHEDULES : CREATE
 */
require_once __DIR__ . '/../../includes/init.php';
requireAdmin();
require_once __DIR__ . '/_validate.php';

$pageTitle    = 'Add schedule';
$pageSubtitle = 'Assign a bus to a route at a fixed time';
$adminSection = 'schedules';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    requireCsrf('admin/schedules/create.php');

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
        setFlash('error', 'The schedule could not be added. Please check the form.');
        redirect('admin/schedules/create.php');
    }

    $days = normaliseDays($input['operating_days']);

    /* Warn (but do not block) when the same bus is double-booked. */
    $clash = findScheduleClash(
        (int) $input['bus_id'],
        $input['departure_time'],
        $input['arrival_time'],
        explode(',', $days)
    );

    try {
        q(
            'INSERT INTO schedules
               (bus_id, route_id, departure_time, arrival_time, operating_days, status, notes)
             VALUES (?, ?, ?, ?, ?, ?, ?)',
            [
                (int) $input['bus_id'],
                (int) $input['route_id'],
                $input['departure_time'],
                $input['arrival_time'],
                $days,
                $input['status'],
                $input['notes'] !== '' ? $input['notes'] : null,
            ]
        );

        $bus   = fetchOne('SELECT bus_name FROM buses WHERE id = ?', [(int) $input['bus_id']]);
        $route = fetchOne('SELECT route_code FROM routes WHERE id = ?', [(int) $input['route_id']]);

        logActivity(
            'Schedule added',
            'Added trip: ' . $bus['bus_name'] . ' on ' . $route['route_code']
            . ' at ' . timeText($input['departure_time']) . '.'
        );

        setFlash('success', 'The schedule was added successfully.');
        if ($clash) {
            setFlash('warning', 'Please double-check: ' . $clash);
        }
        redirect('admin/schedules/index.php');

    } catch (PDOException $ex) {
        error_log('[SCHEDULE CREATE] ' . $ex->getMessage());
        keepErrors(['The schedule could not be saved because of a database error.']);
        keepOld($_POST);
        setFlash('error', 'The schedule could not be added.');
        redirect('admin/schedules/create.php');
    }
}

$errors    = takeErrors();
$allBuses  = fetchAll('SELECT id, bus_name, bus_type, capacity, status FROM buses ORDER BY bus_name');
$allRoutes = fetchAll('SELECT id, route_name, route_code, status FROM routes ORDER BY route_code');

if (!$allBuses || !$allRoutes) {
    /* A schedule needs both a bus and a route to exist first. */
    setFlash('warning', 'Add at least one bus and one route before creating a schedule.');
    redirect($allBuses ? 'admin/routes/create.php' : 'admin/buses/create.php');
}

$schedule = [
    'id'             => 0,
    'bus_id'         => (int) old('bus_id', (int) get('bus', 0)),
    'route_id'       => (int) old('route_id', (int) get('route', 0)),
    'departure_time' => old('departure_time'),
    'arrival_time'   => old('arrival_time'),
    'status'         => old('status', 'Active'),
    'notes'          => old('notes'),
];

$selectedDays = (array) old('operating_days', ['Saturday', 'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday']);
$formAction   = url('admin/schedules/create.php');
$submitLabel  = 'Save schedule';
$isEdit       = false;

require_once __DIR__ . '/../../includes/admin_header.php';
?>

<div class="admin-head">
    <div>
        <h1>Add a schedule</h1>
        <p>One schedule links exactly one bus to one route at a fixed departure time.</p>
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
