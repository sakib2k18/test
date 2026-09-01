<?php
/**
 * ADMIN - ROUTES : READ ONE + MANAGE STOPPAGES
 * This page is the CRUD screen for route_stops: add, edit, reorder and delete.
 */
require_once __DIR__ . '/../../includes/init.php';
requireAdmin();

$id    = (int) get('id', 0);
$route = $id > 0 ? fetchOne('SELECT * FROM routes WHERE id = ?', [$id]) : null;

if (!$route) {
    setFlash('error', 'That route does not exist any more.');
    redirect('admin/routes/index.php');
}

$pageTitle    = $route['route_name'];
$pageSubtitle = 'Route details and stoppages';
$adminSection = 'routes';

$stops = fetchAll(
    'SELECT * FROM route_stops WHERE route_id = ? ORDER BY stop_order ASC, id ASC',
    [$id]
);

$schedules = fetchAll(
    'SELECT s.id, s.departure_time, s.arrival_time, s.operating_days, s.status, b.bus_name
       FROM schedules s
       JOIN buses b ON b.id = s.bus_id
      WHERE s.route_id = ?
      ORDER BY s.departure_time',
    [$id]
);

$errors    = takeErrors();
$lastCount = count($stops);

require_once __DIR__ . '/../../includes/admin_header.php';
?>

<div class="admin-head">
    <div>
        <h1><?= e($route['route_name']) ?></h1>
        <p>
            <span class="code-chip"><?= e($route['route_code']) ?></span>
            <?= e($route['start_point']) ?> &rarr; <?= e($route['destination']) ?> &middot;
            <?= e(number_format((float) $route['distance_km'], 1)) ?> km &middot;
            <?= e(durationText($route['duration_min'])) ?>
        </p>
    </div>
    <div class="admin-head__actions">
        <a class="btn btn--outline" href="<?= e(url('route-details.php?id=' . $id)) ?>" target="_blank" rel="noopener">
            <?= icon('eye') ?> Public page
        </a>
        <a class="btn btn--primary" href="<?= e(url('admin/routes/edit.php?id=' . $id)) ?>">
            <?= icon('edit') ?> Edit route
        </a>
    </div>
</div>

<?= errorSummary($errors) ?>

<div class="admin-layout">

    <!-- ================= Stoppages ================= -->
    <div class="stack" style="gap:22px;">

        <section class="panel">
            <div class="panel__head">
                <div>
                    <h2>Stoppages</h2>
                    <p><?= e($lastCount) ?> stoppage<?= $lastCount === 1 ? '' : 's' ?> in travel order. Use the arrows to reorder.</p>
                </div>
            </div>
            <div class="panel__body">
                <?php if ($stops): ?>
                    <div class="stack" style="gap:10px;">
                        <?php foreach ($stops as $index => $stop): ?>
                            <div class="stop-row">
                                <span class="stop-row__order"><?= e($index + 1) ?></span>

                                <span class="stop-row__body">
                                    <b><?= e($stop['stop_name']) ?></b>
                                    <span>
                                        <?php if (!isBlank($stop['arrival_time'])): ?>
                                            <?= icon('clock', 'icon') ?> <?= e(timeText($stop['arrival_time'])) ?> &middot;
                                        <?php endif; ?>
                                        <?= e($stop['stop_description'] ?: 'No description') ?>
                                    </span>
                                </span>

                                <span class="stop-row__tools">
                                    <!-- Move up -->
                                    <form method="post" action="<?= e(url('admin/stops/move.php')) ?>">
                                        <?= csrfField() ?>
                                        <input type="hidden" name="id" value="<?= (int) $stop['id'] ?>">
                                        <input type="hidden" name="direction" value="up">
                                        <button class="icon-btn" type="submit" title="Move up"
                                                <?= $index === 0 ? 'disabled' : '' ?>>
                                            <?= icon('chevron', 'icon') ?>
                                            <span class="sr-only">Move <?= e($stop['stop_name']) ?> up</span>
                                        </button>
                                    </form>

                                    <!-- Move down -->
                                    <form method="post" action="<?= e(url('admin/stops/move.php')) ?>">
                                        <?= csrfField() ?>
                                        <input type="hidden" name="id" value="<?= (int) $stop['id'] ?>">
                                        <input type="hidden" name="direction" value="down">
                                        <button class="icon-btn" type="submit" title="Move down"
                                                <?= $index === $lastCount - 1 ? 'disabled' : '' ?>>
                                            <?= icon('down', 'icon') ?>
                                            <span class="sr-only">Move <?= e($stop['stop_name']) ?> down</span>
                                        </button>
                                    </form>

                                    <a class="icon-btn" title="Edit stoppage"
                                       href="<?= e(url('admin/stops/edit.php?id=' . (int) $stop['id'])) ?>">
                                        <?= icon('edit') ?><span class="sr-only">Edit <?= e($stop['stop_name']) ?></span>
                                    </a>

                                    <form method="post" action="<?= e(url('admin/stops/delete.php')) ?>"
                                          data-confirm="Delete the stoppage &quot;<?= e($stop['stop_name']) ?>&quot;?">
                                        <?= csrfField() ?>
                                        <input type="hidden" name="id" value="<?= (int) $stop['id'] ?>">
                                        <button class="icon-btn icon-btn--danger" type="submit" title="Delete stoppage">
                                            <?= icon('trash') ?><span class="sr-only">Delete <?= e($stop['stop_name']) ?></span>
                                        </button>
                                    </form>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="empty" style="padding:34px 18px;border:0;">
                        <span class="empty__icon"><?= icon('pin') ?></span>
                        <h3 style="font-size:1.05rem;">No stoppages added yet</h3>
                        <p>Add the first stoppage using the form on the right.</p>
                    </div>
                <?php endif; ?>
            </div>
        </section>

        <!-- ================= Trips on this route ================= -->
        <section class="panel">
            <div class="panel__head">
                <div>
                    <h2>Trips on this route</h2>
                    <p><?= e(count($schedules)) ?> schedule <?= count($schedules) === 1 ? 'entry' : 'entries' ?>.</p>
                </div>
                <a class="btn btn--outline btn--sm" href="<?= e(url('admin/schedules/create.php?route=' . $id)) ?>">
                    <?= icon('plus') ?> Add trip
                </a>
            </div>
            <div class="panel__body panel__body--flush">
                <?php if ($schedules): ?>
                    <div class="table-scroll">
                        <table class="table table--stack">
                            <thead>
                                <tr>
                                    <th scope="col">Bus</th>
                                    <th scope="col">Departure</th>
                                    <th scope="col">Arrival</th>
                                    <th scope="col">Days</th>
                                    <th scope="col">Status</th>
                                    <th scope="col"><span class="sr-only">Actions</span></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($schedules as $s): ?>
                                    <tr>
                                        <td data-label="Bus"><strong><?= e($s['bus_name']) ?></strong></td>
                                        <td data-label="Departure" class="num"><?= e(timeText($s['departure_time'])) ?></td>
                                        <td data-label="Arrival" class="num"><?= e(timeText($s['arrival_time'])) ?></td>
                                        <td data-label="Days">
                                            <span class="cluster" style="gap:4px;">
                                                <?php foreach (dayBadges($s['operating_days']) as $d): ?>
                                                    <span class="day-chip"><?= e($d) ?></span>
                                                <?php endforeach; ?>
                                            </span>
                                        </td>
                                        <td data-label="Status">
                                            <span class="badge <?= e(statusClass($s['status'])) ?>"><?= e($s['status']) ?></span>
                                        </td>
                                        <td data-label="Actions">
                                            <span class="actions">
                                                <a class="icon-btn" title="Edit trip"
                                                   href="<?= e(url('admin/schedules/edit.php?id=' . (int) $s['id'])) ?>">
                                                    <?= icon('edit') ?><span class="sr-only">Edit trip</span>
                                                </a>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="empty" style="padding:34px 18px;border:0;">
                        <span class="empty__icon"><?= icon('clock') ?></span>
                        <h3 style="font-size:1.05rem;">No trips on this route</h3>
                        <p>Assign a bus and a departure time to publish this route in the timetable.</p>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    </div>

    <!-- ================= Add stoppage + summary ================= -->
    <div class="stack" style="gap:22px;">

        <section class="panel">
            <div class="panel__head">
                <div>
                    <h2>Add a stoppage</h2>
                    <p>It is placed at the end of the list.</p>
                </div>
            </div>
            <div class="panel__body">
                <form class="form js-validate" method="post" action="<?= e(url('admin/stops/create.php')) ?>">
                    <?= csrfField() ?>
                    <input type="hidden" name="route_id" value="<?= (int) $id ?>">

                    <div class="field">
                        <label class="field__label" for="stop_name">
                            Stop name <span class="req" aria-hidden="true">*</span>
                        </label>
                        <input class="input" type="text" id="stop_name" name="stop_name"
                               value="<?= e(old('stop_name')) ?>" placeholder="e.g. Daulatpur Bus Stand"
                               data-label="Stop name" data-rule-required
                               data-rule-min="2" data-rule-max="120" required>
                        <p class="field__error"></p>
                    </div>

                    <div class="field">
                        <label class="field__label" for="arrival_time">Estimated arrival time</label>
                        <input class="input" type="time" id="arrival_time" name="arrival_time"
                               value="<?= e(old('arrival_time')) ?>"
                               data-label="Arrival time" data-rule-time>
                        <p class="field__hint">Optional - shown on the public timeline.</p>
                        <p class="field__error"></p>
                    </div>

                    <div class="field">
                        <label class="field__label" for="stop_description">Short description</label>
                        <input class="input" type="text" id="stop_description" name="stop_description"
                               value="<?= e(old('stop_description')) ?>"
                               placeholder="e.g. Beside the local market"
                               data-label="Stop description" data-rule-max="255">
                        <p class="field__error"></p>
                    </div>

                    <button class="btn btn--primary btn--block" type="submit">
                        <?= icon('plus') ?> Add stoppage
                    </button>
                </form>
            </div>
        </section>

        <section class="panel">
            <div class="panel__head"><h2>Route summary</h2></div>
            <div class="panel__body">
                <ul class="meta-list" style="margin:0;">
                    <li><?= icon('road') ?> <strong><?= e(number_format((float) $route['distance_km'], 1)) ?> km</strong> distance</li>
                    <li><?= icon('clock') ?> <strong><?= e(durationText($route['duration_min'])) ?></strong> journey</li>
                    <li><?= icon('pin') ?> <strong><?= e($lastCount) ?></strong> stoppages</li>
                    <li><?= icon('bus') ?> <strong><?= e(count($schedules)) ?></strong> trips</li>
                    <li><?= icon('calendar') ?> Created <?= e(dateText($route['created_at'])) ?></li>
                </ul>

                <h3 style="font-size:.95rem;margin:22px 0 8px;">Description</h3>
                <p class="text-muted" style="font-size:.88rem;">
                    <?= nl2br(e($route['description'] ?: 'No description has been added.')) ?>
                </p>
            </div>
        </section>

        <section class="panel">
            <div class="panel__head"><h2>Danger zone</h2></div>
            <div class="panel__body">
                <?php if ($schedules): ?>
                    <div class="alert alert--warning" style="margin:0 0 14px;">
                        <?= icon('alert') ?>
                        <div>
                            This route is used by <strong><?= e(count($schedules)) ?></strong>
                            schedule <?= count($schedules) === 1 ? 'entry' : 'entries' ?>.
                            Delete those trips first, or set the route to <em>Inactive</em>.
                        </div>
                    </div>
                <?php else: ?>
                    <p class="text-muted" style="font-size:.88rem;margin-bottom:14px;">
                        Deleting the route also removes all <?= e($lastCount) ?> of its stoppages.
                    </p>
                <?php endif; ?>

                <form method="post" action="<?= e(url('admin/routes/delete.php')) ?>"
                      data-confirm="Delete route &quot;<?= e($route['route_name']) ?>&quot; and all of its stoppages?">
                    <?= csrfField() ?>
                    <input type="hidden" name="id" value="<?= (int) $route['id'] ?>">
                    <button class="btn btn--danger btn--block" type="submit">
                        <?= icon('trash') ?> Delete this route
                    </button>
                </form>
            </div>
        </section>

        <a class="btn btn--outline btn--block" href="<?= e(url('admin/routes/index.php')) ?>">Back to all routes</a>
    </div>
</div>

<?php
clearOld();
require_once __DIR__ . '/../../includes/admin_footer.php';
?>
