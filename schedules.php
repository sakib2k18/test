<?php
/**
 * PUBLIC PAGE - TIMETABLE
 * Filter the schedule by route, bus, operating day and departure window.
 * Every filter is applied by MySQL through a prepared statement.
 */
require_once __DIR__ . '/includes/init.php';

$pageTitle       = 'Schedules';
$pageDescription = 'Daily bus timetable of the ' . UNIVERSITY_SHORT . ' Bus Service.';

$days = explode(',', OPERATING_DAYS);

/* ---------------- Filters ---------------- */
$routeId = (int) get('route', 0);
$busId   = (int) get('bus', 0);
$day     = get('day');
$slot    = get('slot');   // morning / afternoon / evening

if (!inList($day, $days)) { $day = ''; }
if (!inList($slot, ['morning', 'afternoon', 'evening'])) { $slot = ''; }

/* Dropdown sources */
$allRoutes = fetchAll('SELECT id, route_name, route_code FROM routes ORDER BY route_code');
$allBuses  = fetchAll('SELECT id, bus_name FROM buses ORDER BY bus_name');

/* ---------------- Query ---------------- */
$where  = ['s.status = ?'];
$params = ['Active'];

if ($routeId > 0) {
    $where[]  = 's.route_id = ?';
    $params[] = $routeId;
}
if ($busId > 0) {
    $where[]  = 's.bus_id = ?';
    $params[] = $busId;
}
if ($day !== '') {
    $where[]  = 'FIND_IN_SET(?, s.operating_days)';
    $params[] = $day;
}
if ($slot === 'morning') {
    $where[] = 's.departure_time < ?';
    $params[] = '12:00:00';
} elseif ($slot === 'afternoon') {
    $where[]  = 's.departure_time >= ? AND s.departure_time < ?';
    $params[] = '12:00:00';
    $params[] = '17:00:00';
} elseif ($slot === 'evening') {
    $where[]  = 's.departure_time >= ?';
    $params[] = '17:00:00';
}

$whereSql = ' WHERE ' . implode(' AND ', $where);

$schedules = fetchAll(
    'SELECT s.id, s.departure_time, s.arrival_time, s.operating_days, s.notes,
            b.id AS bus_id, b.bus_name, b.bus_type, b.capacity,
            r.id AS route_id, r.route_name, r.route_code, r.start_point, r.destination
       FROM schedules s
       JOIN buses  b ON b.id = s.bus_id
       JOIN routes r ON r.id = s.route_id' . $whereSql . '
      ORDER BY s.departure_time ASC, r.route_code ASC',
    $params
);

$hasFilter = $routeId > 0 || $busId > 0 || $day !== '' || $slot !== '';

require_once __DIR__ . '/includes/header.php';
?>

<section class="page-header">
    <div class="container">
        <ul class="breadcrumb">
            <li><a href="<?= e(url('index.php')) ?>">Home</a></li>
            <li><?= icon('chevron') ?></li>
            <li aria-current="page">Schedules</li>
        </ul>
        <h1>Bus Timetable</h1>
        <p>
            Departure and arrival times of every trip. Use the filters to find the
            bus that runs on your route, on the day you need it.
        </p>
    </div>
</section>

<section class="section section--tight">
    <div class="container">

        <!-- ---------------- Filters ---------------- -->
        <div class="filter-bar">
            <form method="get" action="<?= e(url('schedules.php')) ?>">
                <div class="field">
                    <label class="field__label" for="route">Route</label>
                    <select class="select" id="route" name="route" data-auto-submit>
                        <option value="">All routes</option>
                        <?php foreach ($allRoutes as $r): ?>
                            <option value="<?= (int) $r['id'] ?>" <?= $routeId === (int) $r['id'] ? 'selected' : '' ?>>
                                <?= e($r['route_code']) ?> &middot; <?= e($r['route_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="field">
                    <label class="field__label" for="bus">Bus</label>
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
                    <label class="field__label" for="day">Day</label>
                    <select class="select" id="day" name="day" data-auto-submit>
                        <option value="">Every day</option>
                        <?php foreach ($days as $d): ?>
                            <option value="<?= e($d) ?>" <?= $day === $d ? 'selected' : '' ?>><?= e($d) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="field">
                    <label class="field__label" for="slot">Time of day</label>
                    <select class="select" id="slot" name="slot" data-auto-submit>
                        <option value="">Any time</option>
                        <option value="morning"   <?= $slot === 'morning'   ? 'selected' : '' ?>>Morning (before 12 PM)</option>
                        <option value="afternoon" <?= $slot === 'afternoon' ? 'selected' : '' ?>>Afternoon (12 - 5 PM)</option>
                        <option value="evening"   <?= $slot === 'evening'   ? 'selected' : '' ?>>Evening (after 5 PM)</option>
                    </select>
                </div>

                <div class="filter-actions">
                    <button class="btn btn--primary" type="submit"><?= icon('filter') ?> Apply</button>
                    <?php if ($hasFilter): ?>
                        <a class="btn btn--outline" href="<?= e(url('schedules.php')) ?>">Reset</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <!-- Quick day shortcuts -->
        <div class="cluster mb-3" style="gap:8px;">
            <span class="text-muted" style="font-size:.82rem;font-weight:600;">Quick day filter:</span>
            <a class="chip <?= $day === '' ? 'chip--accent' : 'chip--outline' ?>"
               href="<?= e(url('schedules.php') . withQuery(['day' => null, 'page' => null])) ?>">All</a>
            <?php foreach ($days as $d): ?>
                <a class="chip <?= $day === $d ? 'chip--accent' : 'chip--outline' ?>"
                   href="<?= e(url('schedules.php') . withQuery(['day' => $d, 'page' => null])) ?>">
                    <?= e(mb_substr($d, 0, 3)) ?>
                </a>
            <?php endforeach; ?>
        </div>

        <p class="result-count">
            <strong><?= e(count($schedules)) ?></strong>
            <?= count($schedules) === 1 ? 'trip' : 'trips' ?> found<?= $day !== '' ? ' on ' . e($day) : '' ?>.
        </p>

        <!-- ---------------- Timetable ---------------- -->
        <?php if ($schedules): ?>
            <div class="table-wrap">
                <div class="table-scroll">
                    <table class="table table--stack">
                        <caption class="sr-only">Bus timetable</caption>
                        <thead>
                            <tr>
                                <th scope="col">Departure</th>
                                <th scope="col">Route</th>
                                <th scope="col">Bus</th>
                                <th scope="col">Arrival</th>
                                <th scope="col">Operating days</th>
                                <th scope="col">Note</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($schedules as $s): ?>
                                <tr>
                                    <td data-label="Departure" class="num">
                                        <span class="badge badge--success badge--plain" style="font-size:.82rem;">
                                            <?= e(timeText($s['departure_time'])) ?>
                                        </span>
                                    </td>
                                    <td data-label="Route">
                                        <span class="cell-main">
                                            <a href="<?= e(url('route-details.php?id=' . (int) $s['route_id'])) ?>">
                                                <strong><?= e($s['route_name']) ?></strong>
                                            </a>
                                            <span><?= e($s['route_code']) ?> &middot; <?= e($s['start_point']) ?> &rarr; <?= e($s['destination']) ?></span>
                                        </span>
                                    </td>
                                    <td data-label="Bus">
                                        <span class="cell-main">
                                            <a href="<?= e(url('bus-details.php?id=' . (int) $s['bus_id'])) ?>">
                                                <strong><?= e($s['bus_name']) ?></strong>
                                            </a>
                                            <span><?= e($s['bus_type']) ?> &middot; <?= e($s['capacity']) ?> seats</span>
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
                                    <td data-label="Note" class="text-muted"><?= e($s['notes'] ?: '--') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php else: ?>
            <div class="empty">
                <span class="empty__icon"><?= icon('clock') ?></span>
                <h3>No trips match these filters</h3>
                <p>
                    <?php if ($hasFilter): ?>
                        Try a different route, bus or day - or clear the filters to see the whole timetable.
                    <?php else: ?>
                        The timetable has not been published yet.
                    <?php endif; ?>
                </p>
                <?php if ($hasFilter): ?>
                    <a class="btn btn--primary" href="<?= e(url('schedules.php')) ?>">Show the full timetable</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>

    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
