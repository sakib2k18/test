<?php
/**
 * PUBLIC PAGE - ABOUT THE BUS SERVICE
 * The narrative text is static, but the figures come from the database.
 */
require_once __DIR__ . '/includes/init.php';

$pageTitle       = 'About the Bus Service';
$pageDescription = 'Mission, vision and safety standards of the ' . UNIVERSITY_SHORT . ' Bus Service.';

$stats = [
    'buses'     => (int) fetchValue('SELECT COUNT(*) FROM buses'),
    'routes'    => (int) fetchValue('SELECT COUNT(*) FROM routes WHERE status = ?', ['Active']),
    'stops'     => (int) fetchValue('SELECT COUNT(*) FROM route_stops'),
    'trips'     => (int) fetchValue('SELECT COUNT(*) FROM schedules WHERE status = ?', ['Active']),
    'seats'     => (int) fetchValue('SELECT COALESCE(SUM(capacity),0) FROM buses WHERE status = ?', ['Active']),
    'distance'  => (float) fetchValue('SELECT COALESCE(SUM(distance_km),0) FROM routes WHERE status = ?', ['Active']),
];

$facilities = fetchAll('SELECT name, icon_key FROM facilities ORDER BY name');

require_once __DIR__ . '/includes/header.php';
?>

<section class="page-header">
    <div class="container">
        <ul class="breadcrumb">
            <li><a href="<?= e(url('index.php')) ?>">Home</a></li>
            <li><?= icon('chevron') ?></li>
            <li aria-current="page">About</li>
        </ul>
        <h1>About the <?= e(UNIVERSITY_SHORT) ?> Bus Service</h1>
        <p>
            The transport wing of <?= e(UNIVERSITY_NAME) ?>, connecting the campus with
            the city for students, teachers and officers every working day.
        </p>
    </div>
</section>

<!-- ---------------- Introduction ---------------- -->
<section class="section">
    <div class="container">
        <div class="grid grid--2" style="gap:44px;align-items:center;">
            <div class="reveal">
                <span class="eyebrow">Who we are</span>
                <h2>A campus that never stops moving</h2>
                <p class="lead">
                    The <?= e(UNIVERSITY_SHORT) ?> Bus Service is operated by the Transport Office of
                    <?= e(UNIVERSITY_NAME) ?>. It exists for one simple reason: nobody should
                    miss a class, a laboratory session or an office hour because of transport.
                </p>
                <p class="text-muted">
                    Our fleet leaves the main campus early in the morning, reaches every
                    corner of Khulna city, and brings the campus community back home in
                    the evening. The whole timetable, along with the stoppages of each
                    route, is published on this portal so that planning a journey takes
                    only a few seconds.
                </p>
                <div class="cluster mt-3">
                    <a class="btn btn--primary" href="<?= e(url('routes.php')) ?>"><?= icon('route') ?> Browse routes</a>
                    <a class="btn btn--outline" href="<?= e(url('contact.php')) ?>"><?= icon('mail') ?> Contact us</a>
                </div>
            </div>

            <div class="reveal">
                <div class="hero__panel" style="background:var(--primary-soft);border-color:var(--primary-line);">
                    <img src="<?= e(url('assets/images/about-road.jpg')) ?>"
                         alt="A university bus driving down the road towards campus" loading="lazy">
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ---------------- Live figures ---------------- -->
<section class="section section--tight section--alt">
    <div class="container">
        <div class="grid grid--stats">
            <div class="stat-card reveal">
                <span class="stat-card__icon"><?= icon('bus') ?></span>
                <b><?= e($stats['buses']) ?></b>
                <span>Buses in the fleet</span>
            </div>
            <div class="stat-card stat-card--accent reveal">
                <span class="stat-card__icon"><?= icon('route') ?></span>
                <b><?= e($stats['routes']) ?></b>
                <span>Active routes</span>
            </div>
            <div class="stat-card stat-card--info reveal">
                <span class="stat-card__icon"><?= icon('pin') ?></span>
                <b><?= e($stats['stops']) ?></b>
                <span>Official stoppages</span>
            </div>
            <div class="stat-card stat-card--success reveal">
                <span class="stat-card__icon"><?= icon('seat') ?></span>
                <b><?= e($stats['seats']) ?></b>
                <span>Seats every day</span>
            </div>
            <div class="stat-card reveal">
                <span class="stat-card__icon"><?= icon('clock') ?></span>
                <b><?= e($stats['trips']) ?></b>
                <span>Scheduled trips</span>
            </div>
            <div class="stat-card stat-card--accent reveal">
                <span class="stat-card__icon"><?= icon('road') ?></span>
                <b><?= e(number_format($stats['distance'], 0)) ?></b>
                <span>Kilometres covered</span>
            </div>
        </div>
    </div>
</section>

<!-- ---------------- Mission & vision ---------------- -->
<section class="section">
    <div class="container">
        <div class="section-head section-head--center">
            <span class="eyebrow">What drives us</span>
            <h2>Mission &amp; vision</h2>
            <p>Two promises that shape every decision the Transport Office makes.</p>
        </div>

        <div class="grid grid--2">
            <div class="card reveal">
                <div class="card__body">
                    <span class="feature__icon" style="margin-bottom:16px;"><?= icon('star') ?></span>
                    <h3>Our mission</h3>
                    <p class="text-muted">
                        To provide safe, punctual and affordable transport to every student,
                        teacher and officer of the university - so that distance is never a
                        reason for missing academic or official work. We keep the fleet in
                        good repair, publish honest timings and respond quickly when
                        something goes wrong.
                    </p>
                </div>
            </div>

            <div class="card reveal">
                <div class="card__body">
                    <span class="feature__icon" style="margin-bottom:16px;background:var(--accent-soft);color:var(--accent-dark);"><?= icon('gps') ?></span>
                    <h3>Our vision</h3>
                    <p class="text-muted">
                        A fully digital transport service where every passenger can see the
                        route, the stoppage and the exact departure time from their phone
                        before leaving home - supported by a fleet that is modern,
                        well maintained and comfortable for a long daily journey.
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ---------------- Safety ---------------- -->
<section class="section section--alt">
    <div class="container">
        <div class="section-head section-head--center">
            <span class="eyebrow">Safety</span>
            <h2>How we keep every journey safe</h2>
            <p>Safety is checked before the trip, during the trip and after the trip.</p>
        </div>

        <div class="grid grid--cards">
            <div class="feature reveal">
                <span class="feature__icon"><?= icon('wrench') ?></span>
                <div>
                    <h3>Workshop inspection</h3>
                    <p>Every bus is inspected in the university workshop and is withdrawn from service the moment a fault is found.</p>
                </div>
            </div>
            <div class="feature reveal">
                <span class="feature__icon"><?= icon('shield') ?></span>
                <div>
                    <h3>Emergency equipment</h3>
                    <p>A first-aid box, a fire extinguisher and a clearly marked emergency exit are kept on board.</p>
                </div>
            </div>
            <div class="feature reveal">
                <span class="feature__icon"><?= icon('user') ?></span>
                <div>
                    <h3>Licensed drivers</h3>
                    <p>All drivers hold a valid heavy-vehicle licence and their contact number is listed on the bus page.</p>
                </div>
            </div>
            <div class="feature reveal">
                <span class="feature__icon"><?= icon('clock') ?></span>
                <div>
                    <h3>Fixed stoppages</h3>
                    <p>Buses pick up and drop passengers only at official stoppages, which keeps boarding orderly and safe.</p>
                </div>
            </div>
            <div class="feature reveal">
                <span class="feature__icon"><?= icon('megaphone') ?></span>
                <div>
                    <h3>Fast communication</h3>
                    <p>Route suspensions and emergency changes are posted on the notice board of this portal immediately.</p>
                </div>
            </div>
            <div class="feature reveal">
                <span class="feature__icon"><?= icon('users') ?></span>
                <div>
                    <h3>Student priority</h3>
                    <p>Dedicated student buses run on the busiest corridors, with separate coaches for faculty and officers.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ---------------- Facilities ---------------- -->
<section class="section">
    <div class="container">
        <div class="section-head section-head--center">
            <span class="eyebrow">On board</span>
            <h2>Facilities available on our buses</h2>
            <p>Exact facilities differ between buses - check the details page of each bus.</p>
        </div>

        <?php if ($facilities): ?>
            <div class="cluster" style="justify-content:center;gap:10px;">
                <?php foreach ($facilities as $f): ?>
                    <span class="chip" style="padding:9px 16px;font-size:.88rem;">
                        <?= icon($f['icon_key']) ?> <?= e($f['name']) ?>
                    </span>
                <?php endforeach; ?>
            </div>

            <div class="grid grid--cards" style="margin-top:32px;">
                <figure class="card__media reveal" style="border-radius:var(--radius-lg);margin:0;">
                    <img src="<?= e(url('assets/images/interior-1.jpg')) ?>"
                         alt="Passenger cabin of a university bus" loading="lazy">
                    <span class="card__media-tag">Passenger cabin</span>
                </figure>
                <figure class="card__media reveal" style="border-radius:var(--radius-lg);margin:0;">
                    <img src="<?= e(url('assets/images/interior-2.jpg')) ?>"
                         alt="Rows of seats inside a university bus" loading="lazy">
                    <span class="card__media-tag">Comfortable seating</span>
                </figure>
                <figure class="card__media reveal" style="border-radius:var(--radius-lg);margin:0;">
                    <img src="<?= e(url('assets/images/interior-3.jpg')) ?>"
                         alt="Front of the bus cabin seen from the entrance" loading="lazy">
                    <span class="card__media-tag">On the road</span>
                </figure>
            </div>
        <?php else: ?>
            <div class="empty">
                <span class="empty__icon"><?= icon('info') ?></span>
                <h3>Facility list not published</h3>
                <p>The facility list has not been added to the portal yet.</p>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- ---------------- CTA ---------------- -->
<section class="section section--tight">
    <div class="container">
        <div class="cta reveal">
            <h2>Have a question about the service?</h2>
            <p>The Transport Office answers route requests, lost-item reports and schedule queries during office hours.</p>
            <div class="cta__actions">
                <a class="btn btn--accent btn--lg" href="<?= e(url('contact.php')) ?>"><?= icon('mail') ?> Write to us</a>
                <a class="btn btn--light btn--lg" href="<?= e(url('announcements.php')) ?>"><?= icon('megaphone') ?> Read notices</a>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
