<?php
/**
 * ADMIN DASHBOARD
 * Every figure on this page is calculated by MySQL when the page loads.
 */
require_once __DIR__ . '/../includes/init.php';
requireAdmin();

$pageTitle    = 'Dashboard';
$pageSubtitle = 'Overview of the bus service';
$adminSection = 'dashboard';

/* ---------------- Counters ---------------- */
$stats = [
    'buses'       => (int) fetchValue('SELECT COUNT(*) FROM buses'),
    'busActive'   => (int) fetchValue('SELECT COUNT(*) FROM buses WHERE status = ?', ['Active']),
    'busRepair'   => (int) fetchValue('SELECT COUNT(*) FROM buses WHERE status = ?', ['Maintenance']),
    'routes'      => (int) fetchValue('SELECT COUNT(*) FROM routes'),
    'stops'       => (int) fetchValue('SELECT COUNT(*) FROM route_stops'),
    'schedules'   => (int) fetchValue('SELECT COUNT(*) FROM schedules'),
    'notices'     => (int) fetchValue('SELECT COUNT(*) FROM announcements'),
    'published'   => (int) fetchValue('SELECT COUNT(*) FROM announcements WHERE status = ?', ['Published']),
    'users'       => (int) fetchValue('SELECT COUNT(*) FROM users WHERE role = ?', ['user']),
    'unread'      => (int) fetchValue('SELECT COUNT(*) FROM contact_messages WHERE is_read = 0'),
    'seats'       => (int) fetchValue('SELECT COALESCE(SUM(capacity),0) FROM buses WHERE status = ?', ['Active']),
];

/* ---------------- Trips per weekday (bar chart) ---------------- */
$chart = [];
foreach (explode(',', OPERATING_DAYS) as $day) {
    $chart[] = [
        'day'   => $day,
        'short' => mb_substr($day, 0, 3),
        'value' => (int) fetchValue(
            'SELECT COUNT(*) FROM schedules WHERE status = ? AND FIND_IN_SET(?, operating_days)',
            ['Active', $day]
        ),
    ];
}

/* ---------------- Recent activity ---------------- */
$activity = fetchAll(
    'SELECT a.action, a.description, a.created_at, u.full_name
       FROM activity_logs a
       LEFT JOIN users u ON u.id = a.user_id
      ORDER BY a.created_at DESC, a.id DESC
      LIMIT 8'
);

/* ---------------- Buses needing attention ---------------- */
$attention = fetchAll(
    'SELECT id, bus_name, reg_number, status FROM buses WHERE status <> ? ORDER BY status, bus_name LIMIT 5',
    ['Active']
);

/* ---------------- Newest messages ---------------- */
$messages = fetchAll(
    'SELECT id, name, subject, created_at, is_read FROM contact_messages ORDER BY created_at DESC LIMIT 4'
);

/* Icons used in the activity feed, keyed by the action word. */
$actionIcons = [
    'Login' => 'login', 'Logout' => 'logout', 'System' => 'settings',
    'Bus' => 'bus', 'Route' => 'route', 'Stop' => 'pin',
    'Schedule' => 'clock', 'Announcement' => 'megaphone',
    'User' => 'users', 'Message' => 'inbox', 'Account' => 'lock',
];

require_once __DIR__ . '/../includes/admin_header.php';
?>

<div class="admin-head">
    <div>
        <h1>Welcome back, <?= e(explode(' ', $admin['full_name'])[0]) ?></h1>
        <p><?= e(date('l, d F Y')) ?> &middot; everything below is read live from the database.</p>
    </div>
    <div class="admin-head__actions">
        <a class="btn btn--outline btn--sm" href="<?= e(url('admin/buses/create.php')) ?>"><?= icon('plus') ?> Add bus</a>
        <a class="btn btn--outline btn--sm" href="<?= e(url('admin/routes/create.php')) ?>"><?= icon('plus') ?> Add route</a>
        <a class="btn btn--primary btn--sm" href="<?= e(url('admin/schedules/create.php')) ?>"><?= icon('plus') ?> Add schedule</a>
    </div>
</div>

<!-- ---------------- Counters ---------------- -->
<div class="grid grid--stats mb-3">
    <a class="tile" href="<?= e(url('admin/buses/index.php')) ?>">
        <span class="tile__icon"><?= icon('bus') ?></span>
        <span>
            <b><?= e($stats['buses']) ?></b>
            <span>Total buses</span>
        </span>
    </a>
    <a class="tile tile--success" href="<?= e(url('admin/buses/index.php?status=Active')) ?>">
        <span class="tile__icon"><?= icon('check') ?></span>
        <span>
            <b><?= e($stats['busActive']) ?></b>
            <span>Active buses</span>
        </span>
    </a>
    <a class="tile tile--accent" href="<?= e(url('admin/routes/index.php')) ?>">
        <span class="tile__icon"><?= icon('route') ?></span>
        <span>
            <b><?= e($stats['routes']) ?></b>
            <span>Routes</span>
        </span>
    </a>
    <a class="tile tile--info" href="<?= e(url('admin/schedules/index.php')) ?>">
        <span class="tile__icon"><?= icon('clock') ?></span>
        <span>
            <b><?= e($stats['schedules']) ?></b>
            <span>Schedules</span>
        </span>
    </a>
    <a class="tile" href="<?= e(url('admin/users/index.php')) ?>">
        <span class="tile__icon"><?= icon('users') ?></span>
        <span>
            <b><?= e($stats['users']) ?></b>
            <span>Registered users</span>
        </span>
    </a>
    <a class="tile tile--accent" href="<?= e(url('admin/announcements/index.php')) ?>">
        <span class="tile__icon"><?= icon('megaphone') ?></span>
        <span>
            <b><?= e($stats['notices']) ?></b>
            <span>Announcements</span>
        </span>
    </a>
</div>

<div class="admin-layout">

    <!-- ---------------- Left column ---------------- -->
    <div class="stack" style="gap:22px;">

        <!-- Trips per weekday -->
        <section class="panel">
            <div class="panel__head">
                <div>
                    <h2>Active trips per weekday</h2>
                    <p>How many scheduled trips run on each day of the week.</p>
                </div>
                <a class="btn btn--outline btn--sm" href="<?= e(url('admin/schedules/index.php')) ?>">
                    Manage schedules
                </a>
            </div>
            <div class="panel__body">
                <?php if ($stats['schedules'] > 0): ?>
                    <div class="chart">
                        <?php foreach ($chart as $c): ?>
                            <div class="chart__col">
                                <span class="chart__value"><?= e($c['value']) ?></span>
                                <div class="chart__bar" data-value="<?= e($c['value']) ?>"
                                     role="img"
                                     aria-label="<?= e($c['value']) ?> trips on <?= e($c['day']) ?>"></div>
                                <span class="chart__label"><?= e($c['short']) ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="empty" style="padding:34px 18px;border:0;">
                        <span class="empty__icon"><?= icon('clock') ?></span>
                        <h3 style="font-size:1.05rem;">No schedules yet</h3>
                        <p>Add the first trip to see the weekly distribution here.</p>
                        <a class="btn btn--primary btn--sm" href="<?= e(url('admin/schedules/create.php')) ?>">
                            <?= icon('plus') ?> Add schedule
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </section>

        <!-- Recent activity -->
        <section class="panel">
            <div class="panel__head">
                <div>
                    <h2>Recent activity</h2>
                    <p>The last actions recorded in the activity log.</p>
                </div>
            </div>
            <div class="panel__body panel__body--flush">
                <?php if ($activity): ?>
                    <ul class="feed">
                        <?php foreach ($activity as $log): ?>
                            <?php
                            $word = strtok($log['action'], ' ');
                            $ic   = $actionIcons[$word] ?? 'activity';
                            ?>
                            <li>
                                <span class="feed__dot"><?= icon($ic) ?></span>
                                <span>
                                    <b><?= e($log['description']) ?></b>
                                    <span>
                                        <?= e($log['full_name'] ?? 'Removed account') ?> &middot;
                                        <?= e(date('d M Y, g:i A', strtotime($log['created_at']))) ?>
                                    </span>
                                </span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <div class="empty" style="padding:34px 18px;border:0;">
                        <span class="empty__icon"><?= icon('activity') ?></span>
                        <h3 style="font-size:1.05rem;">No activity recorded yet</h3>
                        <p>Actions you take in the admin panel will be listed here.</p>
                    </div>
                <?php endif; ?>
            </div>
        </section>

    </div>

    <!-- ---------------- Right column ---------------- -->
    <div class="stack" style="gap:22px;">

        <section class="panel">
            <div class="panel__head"><h2>At a glance</h2></div>
            <div class="panel__body">
                <ul class="meta-list" style="margin:0;">
                    <li><?= icon('seat') ?> <strong><?= e($stats['seats']) ?></strong> seats available daily</li>
                    <li><?= icon('pin') ?> <strong><?= e($stats['stops']) ?></strong> stoppages registered</li>
                    <li><?= icon('megaphone') ?> <strong><?= e($stats['published']) ?></strong> of <?= e($stats['notices']) ?> notices published</li>
                    <li><?= icon('wrench') ?> <strong><?= e($stats['busRepair']) ?></strong> buses under maintenance</li>
                    <li><?= icon('inbox') ?> <strong><?= e($stats['unread']) ?></strong> unread messages</li>
                </ul>
            </div>
        </section>

        <section class="panel">
            <div class="panel__head">
                <div>
                    <h2>Needs attention</h2>
                    <p>Buses that are not in service.</p>
                </div>
            </div>
            <div class="panel__body">
                <?php if ($attention): ?>
                    <div class="stack" style="gap:10px;">
                        <?php foreach ($attention as $bus): ?>
                            <a class="route-line" style="text-decoration:none;color:inherit;"
                               href="<?= e(url('admin/buses/edit.php?id=' . (int) $bus['id'])) ?>">
                                <span class="route-line__point spacer">
                                    <span><?= e($bus['reg_number']) ?></span>
                                    <b><?= e($bus['bus_name']) ?></b>
                                </span>
                                <span class="badge <?= e(statusClass($bus['status'])) ?>"><?= e($bus['status']) ?></span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="text-muted" style="font-size:.88rem;margin:0;">
                        Every bus in the fleet is currently active. Nothing needs attention.
                    </p>
                <?php endif; ?>
            </div>
        </section>

        <section class="panel">
            <div class="panel__head">
                <div>
                    <h2>Latest messages</h2>
                    <p><?= e($stats['unread']) ?> unread</p>
                </div>
                <a class="btn btn--outline btn--sm" href="<?= e(url('admin/messages/index.php')) ?>">Open</a>
            </div>
            <div class="panel__body">
                <?php if ($messages): ?>
                    <div class="stack" style="gap:10px;">
                        <?php foreach ($messages as $m): ?>
                            <a class="route-line" style="text-decoration:none;color:inherit;"
                               href="<?= e(url('admin/messages/index.php#msg-' . (int) $m['id'])) ?>">
                                <span class="route-line__point spacer">
                                    <span><?= e($m['name']) ?> &middot; <?= e(dateText($m['created_at'])) ?></span>
                                    <b><?= e(excerpt($m['subject'], 42)) ?></b>
                                </span>
                                <?php if (!(int) $m['is_read']): ?>
                                    <span class="badge badge--warning">New</span>
                                <?php endif; ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="text-muted" style="font-size:.88rem;margin:0;">No messages have been received yet.</p>
                <?php endif; ?>
            </div>
        </section>

    </div>
</div>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>
