<?php
/**
 * PUBLIC PAGE - SINGLE BUS
 * Shows one bus with its facilities, the routes it serves and its timetable.
 */
require_once __DIR__ . '/includes/init.php';

$id = (int) get('id', 0);

$bus = $id > 0
    ? fetchOne('SELECT * FROM buses WHERE id = ?', [$id])
    : null;

/* Unknown id -> the friendly 404 page, not a blank screen. */
if (!$bus) {
    http_response_code(404);
    require __DIR__ . '/404.php';
    exit;
}

$pageTitle       = $bus['bus_name'];
$pageDescription = excerpt($bus['description'], 150);

/* Facilities of this bus (many-to-many table) */
$facilities = fetchAll(
    'SELECT f.name, f.icon_key
       FROM facilities f
       JOIN bus_facilities bf ON bf.facility_id = f.id
      WHERE bf.bus_id = ?
      ORDER BY f.name',
    [$id]
);

/* Routes this bus is assigned to, through its schedules */
$routes = fetchAll(
    'SELECT DISTINCT r.id, r.route_name, r.route_code, r.start_point, r.destination,
            r.distance_km, r.duration_min
       FROM routes r
       JOIN schedules s ON s.route_id = r.id
      WHERE s.bus_id = ?
      ORDER BY r.route_code',
    [$id]
);

/* Full timetable of this bus */
$schedules = fetchAll(
    'SELECT s.id, s.departure_time, s.arrival_time, s.operating_days, s.status, s.notes,
            r.route_name, r.route_code, r.id AS route_id
       FROM schedules s
       JOIN routes r ON r.id = s.route_id
      WHERE s.bus_id = ?
      ORDER BY s.departure_time',
    [$id]
);

require_once __DIR__ . '/includes/header.php';
?>

<section class="page-header">
    <div class="container">
        <ul class="breadcrumb">
            <li><a href="<?= e(url('index.php')) ?>">Home</a></li>
            <li><?= icon('chevron') ?></li>
            <li><a href="<?= e(url('buses.php')) ?>">Buses</a></li>
            <li><?= icon('chevron') ?></li>
            <li aria-current="page"><?= e($bus['bus_name']) ?></li>
        </ul>
        <h1><?= e($bus['bus_name']) ?></h1>
        <p><?= e($bus['reg_number']) ?> &middot; <?= e($bus['bus_type']) ?></p>
    </div>
</section>

<section class="section section--tight">
    <div class="container">

        <div class="grid detail-layout" style="grid-template-columns:1.25fr .75fr;gap:32px;align-items:start;">

            <!-- ---------------- Left column ---------------- -->
            <div class="stack" style="gap:26px;">

                <div class="card">
                    <div class="card__media" style="aspect-ratio:16/9;">
                        <img src="<?= e(busImage($bus['image'])) ?>"
                             alt="Photo of <?= e($bus['bus_name']) ?>">
                        <span class="badge <?= e(statusClass($bus['status'])) ?> card__media-badge">
                            <?= e($bus['status']) ?>
                        </span>
                    </div>
                </div>

                <div class="card">
                    <div class="card__body">
                        <h2 style="font-size:1.3rem;">About this bus</h2>
                        <p class="text-muted" style="margin-top:10px;">
                            <?= nl2br(e($bus['description'] ?: 'No description has been added for this bus yet.')) ?>
                        </p>

                        <h3 style="margin:26px 0 14px;font-size:1.05rem;">Specifications</h3>
                        <dl class="meta-grid">
                            <div>
                                <dt>Registration</dt>
                                <dd><?= e($bus['reg_number']) ?></dd>
                            </div>
                            <div>
                                <dt>Model</dt>
                                <dd><?= e($bus['model'] ?: 'Not recorded') ?></dd>
                            </div>
                            <div>
                                <dt>Seating capacity</dt>
                                <dd><?= e($bus['capacity']) ?> seats</dd>
                            </div>
                            <div>
                                <dt>Bus type</dt>
                                <dd><?= e($bus['bus_type']) ?></dd>
                            </div>
                            <div>
                                <dt>Current status</dt>
                                <dd><span class="badge <?= e(statusClass($bus['status'])) ?>"><?= e($bus['status']) ?></span></dd>
                            </div>
                            <div>
                                <dt>Added on</dt>
                                <dd><?= e(dateText($bus['created_at'])) ?></dd>
                            </div>
                        </dl>

                        <?php if ($facilities): ?>
                            <h3 style="margin:26px 0 14px;font-size:1.05rem;">On-board facilities</h3>
                            <div class="cluster">
                                <?php foreach ($facilities as $f): ?>
                                    <span class="chip"><?= icon($f['icon_key']) ?> <?= e($f['name']) ?></span>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- ---------------- Timetable of this bus ---------------- -->
                <div>
                    <h2 style="font-size:1.3rem;margin-bottom:16px;">Timetable</h2>

                    <?php if ($schedules): ?>
                        <div class="table-wrap">
                            <div class="table-scroll">
                                <table class="table table--stack">
                                    <caption class="sr-only">Scheduled trips of <?= e($bus['bus_name']) ?></caption>
                                    <thead>
                                        <tr>
                                            <th scope="col">Route</th>
                                            <th scope="col">Departure</th>
                                            <th scope="col">Arrival</th>
                                            <th scope="col">Operating days</th>
                                            <th scope="col">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($schedules as $s): ?>
                                            <tr>
                                                <td data-label="Route">
                                                    <span class="cell-main">
                                                        <a href="<?= e(url('route-details.php?id=' . (int) $s['route_id'])) ?>">
                                                            <strong><?= e($s['route_name']) ?></strong>
                                                        </a>
                                                        <span><?= e($s['route_code']) ?></span>
                                                    </span>
                                                </td>
                                                <td data-label="Departure" class="num"><?= e(timeText($s['departure_time'])) ?></td>
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
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="empty">
                            <span class="empty__icon"><?= icon('clock') ?></span>
                            <h3>No trips assigned yet</h3>
                            <p>This bus has not been placed on any route schedule so far.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- ---------------- Right column ---------------- -->
            <aside class="stack" style="gap:22px;">

                <div class="card">
                    <div class="card__body">
                        <h2 style="font-size:1.05rem;">Driver on duty</h2>
                        <?php if (!isBlank($bus['driver_name'])): ?>
                            <div class="cluster" style="margin-top:14px;gap:13px;flex-wrap:nowrap;">
                                <span class="nav-user__avatar" style="width:44px;height:44px;font-size:1rem;">
                                    <?= e(strtoupper(mb_substr($bus['driver_name'], 0, 1))) ?>
                                </span>
                                <span>
                                    <b style="display:block;"><?= e($bus['driver_name']) ?></b>
                                    <span class="text-muted" style="font-size:.85rem;">Assigned driver</span>
                                </span>
                            </div>
                            <?php if (!isBlank($bus['driver_contact'])): ?>
                                <a class="btn btn--outline btn--block mt-3"
                                   href="tel:<?= e(preg_replace('/[^0-9+]/', '', $bus['driver_contact'])) ?>">
                                    <?= icon('phone') ?> <?= e($bus['driver_contact']) ?>
                                </a>
                            <?php endif; ?>
                        <?php else: ?>
                            <p class="text-muted" style="margin-top:10px;font-size:.9rem;">
                                No driver has been assigned to this bus yet.
                            </p>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="card">
                    <div class="card__body">
                        <h2 style="font-size:1.05rem;">Assigned routes</h2>

                        <?php if ($routes): ?>
                            <div class="stack mt-3" style="gap:12px;">
                                <?php foreach ($routes as $r): ?>
                                    <a class="route-line" style="text-decoration:none;color:inherit;"
                                       href="<?= e(url('route-details.php?id=' . (int) $r['id'])) ?>">
                                        <span class="route-line__point">
                                            <span><?= e($r['route_code']) ?></span>
                                            <b><?= e($r['start_point']) ?></b>
                                        </span>
                                        <span class="route-line__arrow"><?= icon('arrow') ?></span>
                                        <span class="route-line__point">
                                            <span><?= e(durationText($r['duration_min'])) ?></span>
                                            <b><?= e($r['destination']) ?></b>
                                        </span>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <p class="text-muted" style="margin-top:10px;font-size:.9rem;">
                                This bus is not linked to any route at the moment.
                            </p>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="card">
                    <div class="card__body">
                        <h2 style="font-size:1.05rem;">Need help?</h2>
                        <p class="text-muted" style="margin-top:8px;font-size:.9rem;">
                            Contact the Transport Office for lost items, seat requests or
                            any problem you noticed on this bus.
                        </p>
                        <a class="btn btn--primary btn--block mt-3" href="<?= e(url('contact.php')) ?>">
                            <?= icon('mail') ?> Contact the office
                        </a>
                    </div>
                </div>

                <a class="btn btn--outline btn--block" href="<?= e(url('buses.php')) ?>">
                    Back to all buses
                </a>
            </aside>

        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
