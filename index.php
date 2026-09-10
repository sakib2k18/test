<?php
/**
 * HOMEPAGE
 * Every number, bus, route, schedule and notice on this page is read live
 * from MySQL - nothing is hard-coded.
 */
require_once __DIR__ . '/includes/init.php';

$pageTitle       = 'Home';
$pageDescription = SITE_DESCRIPTION;

/* --- Statistics (calculated by the database, never hard-coded) ---------- */
$stats = [
    'buses'         => (int) fetchValue('SELECT COUNT(*) FROM buses'),
    'active_buses'  => (int) fetchValue('SELECT COUNT(*) FROM buses WHERE status = ?', ['Active']),
    'routes'        => (int) fetchValue('SELECT COUNT(*) FROM routes WHERE status = ?', ['Active']),
    'stops'         => (int) fetchValue('SELECT COUNT(*) FROM route_stops'),
    'schedules'     => (int) fetchValue('SELECT COUNT(*) FROM schedules WHERE status = ?', ['Active']),
    'seats'         => (int) fetchValue('SELECT COALESCE(SUM(capacity),0) FROM buses WHERE status = ?', ['Active']),
];

/* --- Featured buses ----------------------------------------------------- */
$featuredBuses = fetchAll(
    'SELECT id, bus_name, reg_number, model, capacity, bus_type, status, description, image
       FROM buses
      WHERE status = ?
      ORDER BY capacity DESC, bus_name ASC
      LIMIT 3',
    ['Active']
);

/* --- Popular routes (the ones with the most scheduled trips) ------------ */
$popularRoutes = fetchAll(
    'SELECT r.id, r.route_name, r.route_code, r.start_point, r.destination,
            r.distance_km, r.duration_min,
            (SELECT COUNT(*) FROM route_stops s WHERE s.route_id = r.id)  AS stop_count,
            (SELECT COUNT(*) FROM schedules  c WHERE c.route_id = r.id)   AS trip_count
       FROM routes r
      WHERE r.status = ?
      ORDER BY trip_count DESC, r.route_code ASC
      LIMIT 3',
    ['Active']
);

/* --- Today's running schedules ------------------------------------------ */
$today          = date('l');   // e.g. "Tuesday"
$todaySchedules = fetchAll(
    'SELECT s.id, s.departure_time, s.arrival_time, s.notes,
            b.bus_name, b.id AS bus_id,
            r.route_name, r.route_code, r.id AS route_id
       FROM schedules s
       JOIN buses  b ON b.id = s.bus_id
       JOIN routes r ON r.id = s.route_id
      WHERE s.status = ?
        AND FIND_IN_SET(?, s.operating_days)
      ORDER BY s.departure_time ASC
      LIMIT 6',
    ['Active', $today]
);

/* --- Latest published notices ------------------------------------------- */
$latestNotices = fetchAll(
    'SELECT id, title, short_description, priority, published_on
       FROM announcements
      WHERE status = ?
      ORDER BY published_on DESC, id DESC
      LIMIT 3',
    ['Published']
);

require_once __DIR__ . '/includes/header.php';
?>

<!-- ==================================================================
     HERO
     ================================================================== -->
<section class="hero">
    <div class="container hero__inner">

        <div class="hero__content">
            <span class="hero__badge">
                <span class="dot"><?= icon('check') ?></span>
                Official transport portal of <?= e(UNIVERSITY_SHORT) ?>
            </span>

            <h1>
                <?= e(UNIVERSITY_SHORT) ?> Bus Service
                <em><?= e(SITE_TAGLINE) ?></em>
            </h1>

            <p class="hero__lead">
                Find the right bus, follow every stoppage on your route and check the
                exact departure time before you leave home. The full timetable of the
                university fleet, updated by the Transport Office.
            </p>

            <div class="hero__cta">
                <a class="btn btn--accent btn--lg" href="<?= e(url('buses.php')) ?>">
                    <?= icon('bus') ?> Explore Buses
                </a>
                <a class="btn btn--light btn--lg" href="<?= e(url('routes.php')) ?>">
                    <?= icon('route') ?> View Routes
                </a>
            </div>

            <div class="hero__facts">
                <div class="hero__fact">
                    <b><?= e($stats['buses']) ?></b>
                    <span>Buses in fleet</span>
                </div>
                <div class="hero__fact">
                    <b><?= e($stats['routes']) ?></b>
                    <span>Active routes</span>
                </div>
                <div class="hero__fact">
                    <b><?= e($stats['schedules']) ?></b>
                    <span>Daily trips</span>
                </div>
                <div class="hero__fact">
                    <b><?= e($stats['seats']) ?></b>
                    <span>Seats available</span>
                </div>
            </div>
        </div>

        <div class="hero__visual">
            <div class="hero__panel">
                <img src="<?= e(url('assets/images/hero-bus.jpg')) ?>"
                     alt="KUET shuttle bus waiting in front of the campus" width="720" height="540">
            </div>

            <div class="hero__float hero__float--a">
                <span class="icon-circle"><?= icon('shield') ?></span>
                <span>
                    <b><?= e($stats['active_buses']) ?> buses</b>
                    <span>ready to run today</span>
                </span>
            </div>

            <div class="hero__float hero__float--b">
                <span class="icon-circle"><?= icon('pin') ?></span>
                <span>
                    <b><?= e($stats['stops']) ?> stoppages</b>
                    <span>across the city</span>
                </span>
            </div>
        </div>

    </div>
</section>

<!-- ==================================================================
     STATISTICS
     ================================================================== -->
<section class="section section--tight section--alt">
    <div class="container">
        <div class="grid grid--stats">
            <div class="stat-card reveal">
                <span class="stat-card__icon"><?= icon('bus') ?></span>
                <b><?= e($stats['buses']) ?></b>
                <span>Buses in the fleet</span>
            </div>
            <div class="stat-card stat-card--success reveal">
                <span class="stat-card__icon"><?= icon('check') ?></span>
                <b><?= e($stats['active_buses']) ?></b>
                <span>Currently in service</span>
            </div>
            <div class="stat-card stat-card--accent reveal">
                <span class="stat-card__icon"><?= icon('route') ?></span>
                <b><?= e($stats['routes']) ?></b>
                <span>Active routes</span>
            </div>
            <div class="stat-card stat-card--info reveal">
                <span class="stat-card__icon"><?= icon('pin') ?></span>
                <b><?= e($stats['stops']) ?></b>
                <span>Registered stoppages</span>
            </div>
        </div>
    </div>
</section>

<!-- ==================================================================
     ABOUT THE SERVICE (short introduction)
     ================================================================== -->
<section class="section">
    <div class="container">
        <div class="grid grid--2" style="align-items:center;gap:44px;">
            <div class="reveal">
                <span class="eyebrow">About the service</span>
                <h2>Transport that keeps the campus moving</h2>
                <p class="lead">
                    The <?= e(UNIVERSITY_SHORT) ?> Bus Service carries students, teachers and
                    officers between the main campus and every corner of Khulna city on
                    each working day. Buses are maintained in the university workshop,
                    driven by trained drivers and monitored by the Transport Office.
                </p>
                <p class="text-muted">
                    This portal keeps the fleet list, the stoppages of every route and the
                    daily timetable in one place, so you never have to guess when the next
                    bus leaves.
                </p>
                <a class="btn btn--outline mt-3" href="<?= e(url('about.php')) ?>">
                    Learn more about us <?= icon('arrow') ?>
                </a>
            </div>

            <div class="grid" style="gap:16px;">
                <div class="feature reveal">
                    <span class="feature__icon"><?= icon('clock') ?></span>
                    <div>
                        <h3>Punctual timetable</h3>
                        <p>Fixed departure and arrival times for every route, published and kept up to date.</p>
                    </div>
                </div>
                <div class="feature reveal">
                    <span class="feature__icon"><?= icon('shield') ?></span>
                    <div>
                        <h3>Safety first</h3>
                        <p>First-aid box, fire extinguisher and a marked emergency exit on every bus.</p>
                    </div>
                </div>
                <div class="feature reveal">
                    <span class="feature__icon"><?= icon('users') ?></span>
                    <div>
                        <h3>Made for students</h3>
                        <p>Dedicated student buses on the busiest corridors, plus separate coaches for the faculty.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ==================================================================
     FEATURED BUSES
     ================================================================== -->
<section class="section section--alt">
    <div class="container">
        <div class="section-head-row">
            <div class="section-head">
                <span class="eyebrow">Our fleet</span>
                <h2>Featured buses</h2>
                <p>The highest-capacity coaches currently running on the university routes.</p>
            </div>
            <a class="btn btn--outline" href="<?= e(url('buses.php')) ?>">
                View all buses <?= icon('arrow') ?>
            </a>
        </div>

        <?php if ($featuredBuses): ?>
            <div class="grid grid--cards">
                <?php foreach ($featuredBuses as $bus): ?>
                    <article class="card card--hover reveal">
                        <div class="card__media">
                            <img src="<?= e(busImage($bus['image'])) ?>"
                                 alt="Photo of <?= e($bus['bus_name']) ?>" loading="lazy">
                            <span class="badge <?= e(statusClass($bus['status'])) ?> card__media-badge">
                                <?= e($bus['status']) ?>
                            </span>
                            <span class="card__media-tag"><?= e($bus['bus_type']) ?></span>
                        </div>
                        <div class="card__body">
                            <h3 class="card__title">
                                <a href="<?= e(url('bus-details.php?id=' . (int) $bus['id'])) ?>">
                                    <?= e($bus['bus_name']) ?>
                                </a>
                            </h3>
                            <p class="text-muted" style="font-size:.82rem;"><?= e($bus['reg_number']) ?></p>
                            <p class="card__text"><?= e(excerpt($bus['description'], 95)) ?></p>
                            <ul class="meta-list">
                                <li><?= icon('seat') ?> <strong><?= e($bus['capacity']) ?></strong> seats</li>
                                <li><?= icon('bus') ?> <?= e($bus['model'] ?: 'Model not recorded') ?></li>
                            </ul>
                        </div>
                        <div class="card__foot">
                            <span class="chip chip--outline"><?= icon('shield') ?> Maintained</span>
                            <a class="btn btn--primary btn--sm"
                               href="<?= e(url('bus-details.php?id=' . (int) $bus['id'])) ?>">
                                View details <?= icon('chevron') ?>
                            </a>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty">
                <span class="empty__icon"><?= icon('bus') ?></span>
                <h3>No buses are currently available</h3>
                <p>The fleet list has not been published yet. Please check back soon.</p>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- ==================================================================
     POPULAR ROUTES
     ================================================================== -->
<section class="section">
    <div class="container">
        <div class="section-head-row">
            <div class="section-head">
                <span class="eyebrow">Where we go</span>
                <h2>Popular routes</h2>
                <p>The busiest corridors of the network, ranked by the number of daily trips.</p>
            </div>
            <a class="btn btn--outline" href="<?= e(url('routes.php')) ?>">
                All routes <?= icon('arrow') ?>
            </a>
        </div>

        <?php if ($popularRoutes): ?>
            <div class="grid grid--cards">
                <?php foreach ($popularRoutes as $route): ?>
                    <article class="card card--hover reveal">
                        <div class="card__body">
                            <div class="cluster" style="justify-content:space-between;margin-bottom:14px;">
                                <span class="code-chip"><?= e($route['route_code']) ?></span>
                                <span class="chip chip--accent">
                                    <?= icon('clock') ?> <?= e($route['trip_count']) ?> trips/day
                                </span>
                            </div>

                            <h3 class="card__title">
                                <a href="<?= e(url('route-details.php?id=' . (int) $route['id'])) ?>">
                                    <?= e($route['route_name']) ?>
                                </a>
                            </h3>

                            <div class="route-line mt-3">
                                <span class="route-line__point">
                                    <span>From</span>
                                    <b><?= e($route['start_point']) ?></b>
                                </span>
                                <span class="route-line__arrow"><?= icon('arrow') ?></span>
                                <span class="route-line__point">
                                    <span>To</span>
                                    <b><?= e($route['destination']) ?></b>
                                </span>
                            </div>

                            <ul class="meta-list">
                                <li><?= icon('road') ?> <strong><?= e(number_format((float) $route['distance_km'], 1)) ?> km</strong> total distance</li>
                                <li><?= icon('clock') ?> <strong><?= e(durationText($route['duration_min'])) ?></strong> journey time</li>
                                <li><?= icon('pin') ?> <strong><?= e($route['stop_count']) ?></strong> stoppages</li>
                            </ul>
                        </div>
                        <div class="card__foot">
                            <a class="btn btn--primary btn--sm w-100"
                               href="<?= e(url('route-details.php?id=' . (int) $route['id'])) ?>">
                                View route <?= icon('chevron') ?>
                            </a>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty">
                <span class="empty__icon"><?= icon('route') ?></span>
                <h3>No routes have been published yet</h3>
                <p>Route information will appear here as soon as the Transport Office adds it.</p>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- ==================================================================
     TODAY'S SCHEDULE
     ================================================================== -->
<section class="section section--alt">
    <div class="container">
        <div class="section-head-row">
            <div class="section-head">
                <span class="eyebrow">Timetable</span>
                <h2>Buses running today</h2>
                <p>Trips scheduled for <strong><?= e($today) ?>, <?= e(date('d M Y')) ?></strong>.</p>
            </div>
            <a class="btn btn--outline" href="<?= e(url('schedules.php')) ?>">
                Full timetable <?= icon('arrow') ?>
            </a>
        </div>

        <?php if ($todaySchedules): ?>
            <div class="table-wrap reveal">
                <div class="table-scroll">
                    <table class="table table--stack">
                        <caption class="sr-only">Bus trips scheduled for today</caption>
                        <thead>
                            <tr>
                                <th scope="col">Route</th>
                                <th scope="col">Bus</th>
                                <th scope="col">Departure</th>
                                <th scope="col">Arrival</th>
                                <th scope="col">Note</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($todaySchedules as $s): ?>
                                <tr>
                                    <td data-label="Route">
                                        <span class="cell-main">
                                            <a href="<?= e(url('route-details.php?id=' . (int) $s['route_id'])) ?>">
                                                <strong><?= e($s['route_name']) ?></strong>
                                            </a>
                                            <span><?= e($s['route_code']) ?></span>
                                        </span>
                                    </td>
                                    <td data-label="Bus">
                                        <a href="<?= e(url('bus-details.php?id=' . (int) $s['bus_id'])) ?>">
                                            <?= e($s['bus_name']) ?>
                                        </a>
                                    </td>
                                    <td data-label="Departure" class="num">
                                        <span class="badge badge--success badge--plain"><?= e(timeText($s['departure_time'])) ?></span>
                                    </td>
                                    <td data-label="Arrival" class="num"><?= e(timeText($s['arrival_time'])) ?></td>
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
                <h3>No trips are scheduled for <?= e($today) ?></h3>
                <p>There is no bus service today. Please look at the full timetable for the other days of the week.</p>
                <a class="btn btn--primary" href="<?= e(url('schedules.php')) ?>">Open the timetable</a>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- ==================================================================
     LATEST NOTICES
     ================================================================== -->
<section class="section">
    <div class="container">
        <div class="section-head-row">
            <div class="section-head">
                <span class="eyebrow">Notice board</span>
                <h2>Latest announcements</h2>
                <p>Schedule changes, holiday arrangements and emergency transport updates.</p>
            </div>
            <a class="btn btn--outline" href="<?= e(url('announcements.php')) ?>">
                All notices <?= icon('arrow') ?>
            </a>
        </div>

        <?php if ($latestNotices): ?>
            <div class="grid grid--wide">
                <?php foreach ($latestNotices as $n): ?>
                    <article class="notice reveal">
                        <div class="notice__date">
                            <b><?= e(date('d', strtotime($n['published_on']))) ?></b>
                            <span><?= e(date('M', strtotime($n['published_on']))) ?></span>
                        </div>
                        <div class="notice__body">
                            <span class="badge <?= e(statusClass($n['priority'])) ?>" style="margin-bottom:8px;">
                                <?= e($n['priority']) ?>
                            </span>
                            <h3>
                                <a href="<?= e(url('announcement-details.php?id=' . (int) $n['id'])) ?>">
                                    <?= e($n['title']) ?>
                                </a>
                            </h3>
                            <p><?= e(excerpt($n['short_description'], 110)) ?></p>
                            <a class="btn btn--ghost btn--sm"
                               href="<?= e(url('announcement-details.php?id=' . (int) $n['id'])) ?>">
                                Read notice <?= icon('chevron') ?>
                            </a>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty">
                <span class="empty__icon"><?= icon('megaphone') ?></span>
                <h3>There are no notices right now</h3>
                <p>Announcements from the Transport Office will be shown here.</p>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- ==================================================================
     WHY CHOOSE OUR SERVICE
     ================================================================== -->
<section class="section section--alt">
    <div class="container">
        <div class="section-head section-head--center">
            <span class="eyebrow">Why choose us</span>
            <h2>Built around the daily journey of the campus</h2>
            <p>Six reasons students and staff rely on the university bus every working day.</p>
        </div>

        <div class="grid grid--cards">
            <div class="feature reveal">
                <span class="feature__icon"><?= icon('clock') ?></span>
                <div>
                    <h3>Fixed timings</h3>
                    <p>Departure and arrival times are published for every trip and reviewed each semester.</p>
                </div>
            </div>
            <div class="feature reveal">
                <span class="feature__icon"><?= icon('pin') ?></span>
                <div>
                    <h3>Convenient stoppages</h3>
                    <p><?= e($stats['stops']) ?> official stoppages placed where students and staff actually live.</p>
                </div>
            </div>
            <div class="feature reveal">
                <span class="feature__icon"><?= icon('shield') ?></span>
                <div>
                    <h3>Safety equipment</h3>
                    <p>First-aid box, fire extinguisher and a clearly marked emergency exit on board.</p>
                </div>
            </div>
            <div class="feature reveal">
                <span class="feature__icon"><?= icon('wrench') ?></span>
                <div>
                    <h3>Regular maintenance</h3>
                    <p>Every bus is serviced in the university workshop and withdrawn from service when repairs are due.</p>
                </div>
            </div>
            <div class="feature reveal">
                <span class="feature__icon"><?= icon('user') ?></span>
                <div>
                    <h3>Trained drivers</h3>
                    <p>Experienced drivers with a valid licence, and their contact details listed on the bus page.</p>
                </div>
            </div>
            <div class="feature reveal">
                <span class="feature__icon"><?= icon('megaphone') ?></span>
                <div>
                    <h3>Clear communication</h3>
                    <p>Route changes and emergency updates are posted on the notice board of this portal.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ==================================================================
     CALL TO ACTION
     ================================================================== -->
<section class="section">
    <div class="container">
        <div class="cta reveal">
            <h2>Plan your next journey in seconds</h2>
            <p>
                Open the timetable, pick your route and see exactly when the next
                <?= e(UNIVERSITY_SHORT) ?> bus leaves your stoppage.
            </p>
            <div class="cta__actions">
                <a class="btn btn--accent btn--lg" href="<?= e(url('schedules.php')) ?>">
                    <?= icon('calendar') ?> Check the timetable
                </a>
                <?php if (!isLoggedIn()): ?>
                    <a class="btn btn--light btn--lg" href="<?= e(url('register.php')) ?>">
                        <?= icon('user') ?> Create an account
                    </a>
                <?php else: ?>
                    <a class="btn btn--light btn--lg" href="<?= e(url('contact.php')) ?>">
                        <?= icon('mail') ?> Contact the office
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
