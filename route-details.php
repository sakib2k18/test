<?php
/**
 * PUBLIC PAGE - SINGLE ROUTE
 * Shows the stoppages as a vertical timeline plus the buses and trips
 * assigned to this route.
 */
require_once __DIR__ . '/includes/init.php';

$id = (int) get('id', 0);

$route = $id > 0
    ? fetchOne('SELECT * FROM routes WHERE id = ?', [$id])
    : null;

if (!$route) {
    http_response_code(404);
    require __DIR__ . '/404.php';
    exit;
}

$pageTitle       = $route['route_name'];
$pageDescription = excerpt($route['description'], 150);

/* Stoppages, in travel order */
$stops = fetchAll(
    'SELECT stop_name, stop_description, stop_order, arrival_time
       FROM route_stops
      WHERE route_id = ?
      ORDER BY stop_order ASC, id ASC',
    [$id]
);

/* Buses that serve this route */
$buses = fetchAll(
    'SELECT DISTINCT b.id, b.bus_name, b.reg_number, b.capacity, b.bus_type, b.status, b.image
       FROM buses b
       JOIN schedules s ON s.bus_id = b.id
      WHERE s.route_id = ?
      ORDER BY b.bus_name',
    [$id]
);

/* Timetable of this route */
$schedules = fetchAll(
    'SELECT s.departure_time, s.arrival_time, s.operating_days, s.status, s.notes,
            b.bus_name, b.id AS bus_id
       FROM schedules s
       JOIN buses b ON b.id = s.bus_id
      WHERE s.route_id = ?
      ORDER BY s.departure_time ASC',
    [$id]
);

require_once __DIR__ . '/includes/header.php';
?>

<section class="page-header">
    <div class="container">
        <ul class="breadcrumb">
            <li><a href="<?= e(url('index.php')) ?>">Home</a></li>
            <li><?= icon('chevron') ?></li>
            <li><a href="<?= e(url('routes.php')) ?>">Routes</a></li>
            <li><?= icon('chevron') ?></li>
            <li aria-current="page"><?= e($route['route_code']) ?></li>
        </ul>
        <h1><?= e($route['route_name']) ?></h1>
        <p>
            Route code <?= e($route['route_code']) ?> &middot;
            <?= e($route['start_point']) ?> &rarr; <?= e($route['destination']) ?>
        </p>
    </div>
</section>

<section class="section section--tight">
    <div class="container">

        <!-- ---------------- Key figures ---------------- -->
        <dl class="meta-grid mb-3">
            <div>
                <dt>Route code</dt>
                <dd><?= e($route['route_code']) ?></dd>
            </div>
            <div>
                <dt>Distance</dt>
                <dd><?= e(number_format((float) $route['distance_km'], 1)) ?> km</dd>
            </div>
            <div>
                <dt>Journey time</dt>
                <dd><?= e(durationText($route['duration_min'])) ?></dd>
            </div>
            <div>
                <dt>Stoppages</dt>
                <dd><?= e(count($stops)) ?></dd>
            </div>
            <div>
                <dt>Status</dt>
                <dd><span class="badge <?= e(statusClass($route['status'])) ?>"><?= e($route['status']) ?></span></dd>
            </div>
        </dl>

        <div class="grid detail-layout" style="grid-template-columns:1fr 1fr;gap:32px;align-items:start;">

            <!-- ---------------- Stops timeline ---------------- -->
            <div class="card">
                <div class="card__body">
                    <h2 style="font-size:1.2rem;">Stoppages along the way</h2>
                    <p class="text-muted" style="font-size:.88rem;margin:6px 0 22px;">
                        Listed in travel order from <?= e($route['start_point']) ?>.
                    </p>

                    <?php if ($stops): ?>
                        <ol class="timeline">
                            <?php foreach ($stops as $stop): ?>
                                <li>
                                    <div class="timeline__name">
                                        <?= e($stop['stop_name']) ?>
                                        <?php if (!isBlank($stop['arrival_time'])): ?>
                                            <span class="timeline__time"><?= e(timeText($stop['arrival_time'])) ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <?php if (!isBlank($stop['stop_description'])): ?>
                                        <p class="timeline__desc"><?= e($stop['stop_description']) ?></p>
                                    <?php endif; ?>
                                </li>
                            <?php endforeach; ?>
                        </ol>
                    <?php else: ?>
                        <div class="empty" style="padding:34px 18px;">
                            <span class="empty__icon"><?= icon('pin') ?></span>
                            <h3 style="font-size:1.05rem;">No stoppages added yet</h3>
                            <p>The stop list for this route has not been published.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- ---------------- Description + buses ---------------- -->
            <div class="stack" style="gap:24px;">

                <div class="card">
                    <div class="card__body">
                        <h2 style="font-size:1.2rem;">About this route</h2>
                        <p class="text-muted" style="margin-top:10px;">
                            <?= nl2br(e($route['description'] ?: 'No description has been added for this route yet.')) ?>
                        </p>

                        <div class="route-line mt-3">
                            <span class="route-line__point">
                                <span>Starting point</span>
                                <b><?= e($route['start_point']) ?></b>
                            </span>
                            <span class="route-line__arrow"><?= icon('arrow') ?></span>
                            <span class="route-line__point">
                                <span>Destination</span>
                                <b><?= e($route['destination']) ?></b>
                            </span>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card__body">
                        <h2 style="font-size:1.2rem;">Buses on this route</h2>

                        <?php if ($buses): ?>
                            <div class="stack mt-3" style="gap:12px;">
                                <?php foreach ($buses as $b): ?>
                                    <a class="route-line" style="text-decoration:none;color:inherit;gap:14px;"
                                       href="<?= e(url('bus-details.php?id=' . (int) $b['id'])) ?>">
                                        <img src="<?= e(busImage($b['image'])) ?>" alt=""
                                             width="56" height="42" loading="lazy"
                                             style="width:56px;height:42px;object-fit:cover;border-radius:8px;flex:none;">
                                        <span class="route-line__point spacer">
                                            <span><?= e($b['bus_type']) ?> &middot; <?= e($b['capacity']) ?> seats</span>
                                            <b><?= e($b['bus_name']) ?></b>
                                        </span>
                                        <span class="badge <?= e(statusClass($b['status'])) ?>"><?= e($b['status']) ?></span>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <p class="text-muted" style="margin-top:10px;font-size:.9rem;">
                                No bus has been assigned to this route yet.
                            </p>
                        <?php endif; ?>
                    </div>
                </div>

            </div>
        </div>

        <!-- ---------------- Timetable ---------------- -->
        <div class="mt-3" style="margin-top:34px;">
            <h2 style="font-size:1.3rem;margin-bottom:16px;">Timetable for this route</h2>

            <?php if ($schedules): ?>
                <div class="table-wrap">
                    <div class="table-scroll">
                        <table class="table table--stack">
                            <caption class="sr-only">Scheduled trips on route <?= e($route['route_code']) ?></caption>
                            <thead>
                                <tr>
                                    <th scope="col">Bus</th>
                                    <th scope="col">Departure</th>
                                    <th scope="col">Arrival</th>
                                    <th scope="col">Operating days</th>
                                    <th scope="col">Status</th>
                                    <th scope="col">Note</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($schedules as $s): ?>
                                    <tr>
                                        <td data-label="Bus">
                                            <a href="<?= e(url('bus-details.php?id=' . (int) $s['bus_id'])) ?>">
                                                <strong><?= e($s['bus_name']) ?></strong>
                                            </a>
                                        </td>
                                        <td data-label="Departure" class="num">
                                            <span class="badge badge--success badge--plain"><?= e(timeText($s['departure_time'])) ?></span>
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
                    <h3>No trips scheduled on this route</h3>
                    <p>The Transport Office has not published a timetable for this route yet.</p>
                </div>
            <?php endif; ?>
        </div>

        <div class="cluster mt-3" style="margin-top:28px;">
            <a class="btn btn--outline" href="<?= e(url('routes.php')) ?>">Back to all routes</a>
            <a class="btn btn--primary" href="<?= e(url('schedules.php?route=' . (int) $route['id'])) ?>">
                <?= icon('calendar') ?> See this route in the full timetable
            </a>
        </div>

    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
