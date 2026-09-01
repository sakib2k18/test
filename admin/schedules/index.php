<?php
/**
 * ADMIN - SCHEDULES : READ (list with route / bus / day filters)
 */
require_once __DIR__ . '/../../includes/init.php';
requireAdmin();

$pageTitle    = 'Schedules';
$pageSubtitle = 'Manage the bus timetable';
$adminSection = 'schedules';

$days = explode(',', OPERATING_DAYS);

$routeId = (int) get('route', 0);
$busId   = (int) get('bus', 0);
$day     = get('day');
$status  = get('status');

if (!inList($day, $days))                          { $day = ''; }
if (!inList($status, ['Active', 'Suspended']))     { $status = ''; }

$where  = [];
$params = [];

if ($routeId > 0) { $where[] = 's.route_id = ?';                 $params[] = $routeId; }
if ($busId > 0)   { $where[] = 's.bus_id = ?';                   $params[] = $busId; }
if ($day !== '')  { $where[] = 'FIND_IN_SET(?, s.operating_days)'; $params[] = $day; }
if ($status !== ''){ $where[] = 's.status = ?';                  $params[] = $status; }

$whereSql = $where ? ' WHERE ' . implode(' AND ', $where) : '';

$schedules = fetchAll(
    'SELECT s.*, b.bus_name, b.status AS bus_status, r.route_name, r.route_code
       FROM schedules s
       JOIN buses  b ON b.id = s.bus_id
       JOIN routes r ON r.id = s.route_id' . $whereSql . '
      ORDER BY s.departure_time ASC, r.route_code ASC',
    $params
);

$allRoutes = fetchAll('SELECT id, route_name, route_code FROM routes ORDER BY route_code');
$allBuses  = fetchAll('SELECT id, bus_name FROM buses ORDER BY bus_name');
$hasFilter = $routeId > 0 || $busId > 0 || $day !== '' || $status !== '';

require_once __DIR__ . '/../../includes/admin_header.php';
?>

<div class="admin-head">
    <div>
        <h1>Schedules</h1>
        <p><?= e(count($schedules)) ?> <?= count($schedules) === 1 ? 'trip' : 'trips' ?> listed.</p>
    </div>
    <div class="admin-head__actions">
        <a class="btn btn--primary" href="<?= e(url('admin/schedules/create.php')) ?>">
            <?= icon('plus') ?> Add new schedule
        </a>
    </div>
</div>

<div class="admin-toolbar">
    <form method="get" action="<?= e(url('admin/schedules/index.php')) ?>">
        <div class="field search-field">
            <label class="field__label sr-only" for="quick">Filter the list</label>
            <?= icon('search') ?>
            <input class="input" type="search" id="quick"
                   placeholder="Type to narrow the rows below (bus, route, note)..."
                   data-table-filter="#scheduleTable">
        </div>

        <div class="field">
            <label class="field__label sr-only" for="route">Route</label>
            <select class="select" id="route" name="route" data-auto-submit>
                <option value="">All routes</option>
                <?php foreach ($allRoutes as $r): ?>
                    <option value="<?= (int) $r['id'] ?>" <?= $routeId === (int) $r['id'] ? 'selected' : '' ?>>
                        <?= e($r['route_code']) ?> - <?= e($r['route_name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="field">
            <label class="field__label sr-only" for="bus">Bus</label>
            <select class="select" id="bus" name="bus" data-auto-submit>
                <option value="">All buses</option>
                <?php foreach ($allBuses as $b): ?>
                    <option value="<?= (int) $b['id'] ?>" <?= $busId === (int) $b['id'] ? 'selected' : '' ?>>
                        <?= e($b['bus_name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="field">
            <label class="field__label sr-only" for="day">Day</label>
            <select class="select" id="day" name="day" data-auto-submit>
                <option value="">Every day</option>
                <?php foreach ($days as $d): ?>
                    <option value="<?= e($d) ?>" <?= $day === $d ? 'selected' : '' ?>><?= e($d) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="field">
            <label class="field__label sr-only" for="status">Status</label>
            <select class="select" id="status" name="status" data-auto-submit>
                <option value="">Any status</option>
                <?php foreach (['Active', 'Suspended'] as $opt): ?>
                    <option value="<?= e($opt) ?>" <?= $status === $opt ? 'selected' : '' ?>><?= e($opt) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="filter-actions">
            <button class="btn btn--primary" type="submit"><?= icon('filter') ?> Apply</button>
            <?php if ($hasFilter): ?>
                <a class="btn btn--outline" href="<?= e(url('admin/schedules/index.php')) ?>">Reset</a>
            <?php endif; ?>
        </div>
    </form>
</div>

<?php if ($schedules): ?>
    <div class="table-wrap">
        <div class="table-scroll">
            <table class="table table--stack" id="scheduleTable">
                <caption class="sr-only">All schedule entries</caption>
                <thead>
                    <tr>
                        <th scope="col">Departure</th>
                        <th scope="col">Route</th>
                        <th scope="col">Bus</th>
                        <th scope="col">Arrival</th>
                        <th scope="col">Operating days</th>
                        <th scope="col">Status</th>
                        <th scope="col"><span class="sr-only">Actions</span></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($schedules as $s): ?>
                        <tr>
                            <td data-label="Departure" class="num">
                                <span class="badge badge--success badge--plain"><?= e(timeText($s['departure_time'])) ?></span>
                            </td>
                            <td data-label="Route">
                                <span class="cell-main">
                                    <strong><?= e($s['route_name']) ?></strong>
                                    <span><?= e($s['route_code']) ?></span>
                                </span>
                            </td>
                            <td data-label="Bus">
                                <span class="cell-main">
                                    <strong><?= e($s['bus_name']) ?></strong>
                                    <?php if ($s['bus_status'] !== 'Active'): ?>
                                        <span>Bus is <?= e(strtolower($s['bus_status'])) ?></span>
                                    <?php else: ?>
                                        <span><?= e($s['notes'] ?: '--') ?></span>
                                    <?php endif; ?>
                                </span>
                            </td>
                            <td data-label="Arrival" class="num"><?= e(timeText($s['arrival_time'])) ?></td>
                            <td data-label="Operating days">
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
                                    <a class="icon-btn" title="Edit schedule"
                                       href="<?= e(url('admin/schedules/edit.php?id=' . (int) $s['id'])) ?>">
                                        <?= icon('edit') ?><span class="sr-only">Edit trip</span>
                                    </a>
                                    <form method="post" action="<?= e(url('admin/schedules/delete.php')) ?>"
                                          style="display:inline;"
                                          data-confirm="Delete the <?= e(timeText($s['departure_time'])) ?> trip of <?= e($s['bus_name']) ?> on <?= e($s['route_code']) ?>?">
                                        <?= csrfField() ?>
                                        <input type="hidden" name="id" value="<?= (int) $s['id'] ?>">
                                        <button class="icon-btn icon-btn--danger" type="submit" title="Delete schedule">
                                            <?= icon('trash') ?><span class="sr-only">Delete trip</span>
                                        </button>
                                    </form>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <tr data-filter-empty hidden>
                        <td colspan="7" class="text-center text-muted" style="padding:26px;">
                            No trip matches what you typed.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
<?php else: ?>
    <div class="empty">
        <span class="empty__icon"><?= icon('clock') ?></span>
        <h3><?= $hasFilter ? 'No trips match your filters' : 'No schedules have been added yet' ?></h3>
        <p>
            <?= $hasFilter
                ? 'Try a different route, bus or day, or clear the filters.'
                : 'Create the first trip by assigning a bus to a route.' ?>
        </p>
        <?php if ($hasFilter): ?>
            <a class="btn btn--outline" href="<?= e(url('admin/schedules/index.php')) ?>">Clear filters</a>
        <?php else: ?>
            <a class="btn btn--primary" href="<?= e(url('admin/schedules/create.php')) ?>"><?= icon('plus') ?> Add schedule</a>
        <?php endif; ?>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../../includes/admin_footer.php'; ?>
