<?php
/**
 * ADMIN - ROUTE STOPS : UPDATE
 */
require_once __DIR__ . '/../../includes/init.php';
requireAdmin();
require_once __DIR__ . '/../routes/_validate.php';

$pageTitle    = 'Edit stoppage';
$pageSubtitle = 'Update a stop on a route';
$adminSection = 'routes';

$id      = (int) ($_SERVER['REQUEST_METHOD'] === 'POST' ? post('id', 0) : get('id', 0));
$current = $id > 0 ? fetchOne('SELECT * FROM route_stops WHERE id = ?', [$id]) : null;

if (!$current) {
    setFlash('error', 'That stoppage does not exist any more.');
    redirect('admin/routes/index.php');
}

$route = fetchOne('SELECT id, route_name, route_code FROM routes WHERE id = ?', [(int) $current['route_id']]);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    requireCsrf('admin/stops/edit.php?id=' . $id);

    $input = [
        'stop_name'        => post('stop_name'),
        'stop_description' => post('stop_description'),
        'arrival_time'     => post('arrival_time'),
    ];

    $errors = validateStopInput($input);

    /* The position is a whole number inside the current list. */
    $stopCount = (int) fetchValue('SELECT COUNT(*) FROM route_stops WHERE route_id = ?', [(int) $current['route_id']]);
    $position  = post('stop_order', $current['stop_order']);

    if (!isIntBetween($position, 1, max(1, $stopCount))) {
        $errors[] = 'The position must be a number between 1 and ' . max(1, $stopCount) . '.';
    }

    if ($errors) {
        keepErrors($errors);
        keepOld($_POST);
        setFlash('error', 'The stoppage could not be updated.');
        redirect('admin/stops/edit.php?id=' . $id);
    }

    try {
        q(
            'UPDATE route_stops
                SET stop_name = ?, stop_description = ?, arrival_time = ?, stop_order = ?
              WHERE id = ?',
            [
                $input['stop_name'],
                $input['stop_description'] !== '' ? $input['stop_description'] : null,
                $input['arrival_time'] !== ''     ? $input['arrival_time']     : null,
                (int) $position,
                $id,
            ]
        );

        logActivity('Stop updated', 'Updated stoppage "' . $input['stop_name'] . '" on route "' . $route['route_name'] . '".');
        setFlash('success', 'Stoppage "' . $input['stop_name'] . '" was updated.');
        redirect('admin/routes/view.php?id=' . (int) $current['route_id']);

    } catch (PDOException $ex) {
        error_log('[STOP EDIT] ' . $ex->getMessage());
        keepErrors(['The stoppage could not be updated because of a database error.']);
        keepOld($_POST);
        redirect('admin/stops/edit.php?id=' . $id);
    }
}

$errors    = takeErrors();
$hasOld    = !empty($_SESSION['old']);
$stopCount = (int) fetchValue('SELECT COUNT(*) FROM route_stops WHERE route_id = ?', [(int) $current['route_id']]);

$stop = [
    'stop_name'        => $hasOld ? old('stop_name')        : $current['stop_name'],
    'stop_description' => $hasOld ? old('stop_description') : (string) $current['stop_description'],
    'arrival_time'     => $hasOld ? old('arrival_time')     : substr((string) $current['arrival_time'], 0, 5),
    'stop_order'       => $hasOld ? old('stop_order')       : $current['stop_order'],
];

require_once __DIR__ . '/../../includes/admin_header.php';
?>

<div class="admin-head">
    <div>
        <h1>Edit stoppage</h1>
        <p>On route <span class="code-chip"><?= e($route['route_code']) ?></span> <?= e($route['route_name']) ?></p>
    </div>
    <div class="admin-head__actions">
        <a class="btn btn--outline" href="<?= e(url('admin/routes/view.php?id=' . (int) $current['route_id'])) ?>">
            Back to the route
        </a>
    </div>
</div>

<?= errorSummary($errors) ?>

<form class="form-card form js-validate" method="post"
      action="<?= e(url('admin/stops/edit.php?id=' . $id)) ?>" style="max-width:680px;">
    <?= csrfField() ?>
    <input type="hidden" name="id" value="<?= (int) $id ?>">

    <div class="form-section">
        <p class="form-section__title">Stoppage details</p>

        <div class="field">
            <label class="field__label" for="stop_name">
                Stop name <span class="req" aria-hidden="true">*</span>
            </label>
            <input class="input" type="text" id="stop_name" name="stop_name"
                   value="<?= e($stop['stop_name']) ?>"
                   data-label="Stop name" data-rule-required
                   data-rule-min="2" data-rule-max="120" required>
            <p class="field__error"></p>
        </div>

        <div class="form-row">
            <div class="field">
                <label class="field__label" for="arrival_time">Estimated arrival time</label>
                <input class="input" type="time" id="arrival_time" name="arrival_time"
                       value="<?= e($stop['arrival_time']) ?>"
                       data-label="Arrival time" data-rule-time>
                <p class="field__error"></p>
            </div>

            <div class="field">
                <label class="field__label" for="stop_order">
                    Position in the route <span class="req" aria-hidden="true">*</span>
                </label>
                <input class="input" type="number" id="stop_order" name="stop_order"
                       value="<?= e($stop['stop_order']) ?>" min="1" max="<?= e(max(1, $stopCount)) ?>" step="1"
                       data-label="Position" data-rule-required
                       data-rule-number="1,<?= e(max(1, $stopCount)) ?>" required>
                <p class="field__hint">1 is the starting point of the route.</p>
                <p class="field__error"></p>
            </div>
        </div>

        <div class="field">
            <label class="field__label" for="stop_description">Short description</label>
            <input class="input" type="text" id="stop_description" name="stop_description"
                   value="<?= e($stop['stop_description']) ?>"
                   placeholder="e.g. Beside the local market"
                   data-label="Stop description" data-rule-max="255">
            <p class="field__error"></p>
        </div>
    </div>

    <div class="form-actions">
        <button class="btn btn--primary" type="submit"><?= icon('check') ?> Update stoppage</button>
        <a class="btn btn--outline" href="<?= e(url('admin/routes/view.php?id=' . (int) $current['route_id'])) ?>">Cancel</a>
    </div>
</form>

<?php
clearOld();
require_once __DIR__ . '/../../includes/admin_footer.php';
?>
