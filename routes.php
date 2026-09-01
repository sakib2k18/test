<?php
/**
 * PUBLIC PAGE - ROUTES
 * Search by name / code / start / destination, plus a status filter.
 */
require_once __DIR__ . '/includes/init.php';

$pageTitle       = 'Routes';
$pageDescription = 'All bus routes operated by the ' . UNIVERSITY_SHORT . ' Bus Service.';

$search = get('q');
$status = get('status');
$page   = max(1, (int) get('page', 1));

$statusOptions = ['Active', 'Inactive'];
if (!inList($status, $statusOptions)) { $status = ''; }

$where  = [];
$params = [];

if ($search !== '') {
    $where[]  = '(r.route_name LIKE ? OR r.route_code LIKE ? OR r.start_point LIKE ? OR r.destination LIKE ?)';
    $like     = '%' . $search . '%';
    $params   = array_merge($params, [$like, $like, $like, $like]);
}
if ($status !== '') {
    $where[]  = 'r.status = ?';
    $params[] = $status;
}

$whereSql = $where ? ' WHERE ' . implode(' AND ', $where) : '';

$total  = (int) fetchValue('SELECT COUNT(*) FROM routes r' . $whereSql, $params);
$pages  = max(1, (int) ceil($total / ITEMS_PER_PAGE));
$page   = min($page, $pages);
$offset = ($page - 1) * ITEMS_PER_PAGE;

$routes = fetchAll(
    'SELECT r.id, r.route_name, r.route_code, r.start_point, r.destination,
            r.description, r.distance_km, r.duration_min, r.status,
            (SELECT COUNT(*) FROM route_stops s WHERE s.route_id = r.id) AS stop_count,
            (SELECT COUNT(*) FROM schedules  c WHERE c.route_id = r.id) AS trip_count
       FROM routes r' . $whereSql . '
      ORDER BY r.route_code ASC
      LIMIT ' . (int) ITEMS_PER_PAGE . ' OFFSET ' . (int) $offset,
    $params
);

require_once __DIR__ . '/includes/header.php';
?>

<section class="page-header">
    <div class="container">
        <ul class="breadcrumb">
            <li><a href="<?= e(url('index.php')) ?>">Home</a></li>
            <li><?= icon('chevron') ?></li>
            <li aria-current="page">Routes</li>
        </ul>
        <h1>Bus Routes</h1>
        <p>
            Every corridor served by the university fleet, with its distance, journey
            time and the number of official stoppages along the way.
        </p>
    </div>
</section>

<section class="section section--tight">
    <div class="container">

        <div class="filter-bar">
            <form method="get" action="<?= e(url('routes.php')) ?>" role="search">
                <div class="field search-field">
                    <label class="field__label" for="q">Search routes</label>
                    <?= icon('search') ?>
                    <input class="input" type="search" id="q" name="q"
                           value="<?= e($search) ?>"
                           placeholder="Route name, code, starting point or destination...">
                </div>

                <div class="field">
                    <label class="field__label" for="status">Status</label>
                    <select class="select" id="status" name="status" data-auto-submit>
                        <option value="">Any status</option>
                        <?php foreach ($statusOptions as $opt): ?>
                            <option value="<?= e($opt) ?>" <?= $status === $opt ? 'selected' : '' ?>>
                                <?= e($opt) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="filter-actions">
                    <button class="btn btn--primary" type="submit"><?= icon('search') ?> Search</button>
                    <?php if ($search !== '' || $status !== ''): ?>
                        <a class="btn btn--outline" href="<?= e(url('routes.php')) ?>">Reset</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <p class="result-count">
            <strong><?= e($total) ?></strong>
            <?= $total === 1 ? 'route' : 'routes' ?> found<?= $search !== '' ? ' for "' . e($search) . '"' : '' ?>.
        </p>

        <?php if ($routes): ?>
            <div class="grid grid--cards">
                <?php foreach ($routes as $route): ?>
                    <article class="card card--hover reveal">
                        <div class="card__body">
                            <div class="cluster" style="justify-content:space-between;margin-bottom:14px;">
                                <span class="code-chip"><?= e($route['route_code']) ?></span>
                                <span class="badge <?= e(statusClass($route['status'])) ?>"><?= e($route['status']) ?></span>
                            </div>

                            <h2 class="card__title">
                                <a href="<?= e(url('route-details.php?id=' . (int) $route['id'])) ?>">
                                    <?= e($route['route_name']) ?>
                                </a>
                            </h2>

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
                                <li><?= icon('road') ?> <strong><?= e(number_format((float) $route['distance_km'], 1)) ?> km</strong></li>
                                <li><?= icon('clock') ?> <strong><?= e(durationText($route['duration_min'])) ?></strong> journey time</li>
                                <li><?= icon('pin') ?> <strong><?= e($route['stop_count']) ?></strong> stoppages</li>
                                <li><?= icon('bus') ?> <strong><?= e($route['trip_count']) ?></strong> scheduled trips</li>
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

            <?php if ($pages > 1): ?>
                <nav aria-label="Route list pages">
                    <ul class="pagination">
                        <li>
                            <a class="<?= $page <= 1 ? 'is-disabled' : '' ?>"
                               href="<?= e(url('routes.php') . withQuery(['page' => $page - 1])) ?>">Prev</a>
                        </li>
                        <?php for ($i = 1; $i <= $pages; $i++): ?>
                            <li>
                                <?php if ($i === $page): ?>
                                    <span class="is-current" aria-current="page"><?= e($i) ?></span>
                                <?php else: ?>
                                    <a href="<?= e(url('routes.php') . withQuery(['page' => $i])) ?>"><?= e($i) ?></a>
                                <?php endif; ?>
                            </li>
                        <?php endfor; ?>
                        <li>
                            <a class="<?= $page >= $pages ? 'is-disabled' : '' ?>"
                               href="<?= e(url('routes.php') . withQuery(['page' => $page + 1])) ?>">Next</a>
                        </li>
                    </ul>
                </nav>
            <?php endif; ?>

        <?php else: ?>
            <div class="empty">
                <span class="empty__icon"><?= icon('route') ?></span>
                <h3>No routes match your search</h3>
                <p>
                    <?php if ($search !== '' || $status !== ''): ?>
                        Try another keyword, or clear the filters to see every route.
                    <?php else: ?>
                        No routes have been published on the portal yet.
                    <?php endif; ?>
                </p>
                <?php if ($search !== '' || $status !== ''): ?>
                    <a class="btn btn--primary" href="<?= e(url('routes.php')) ?>">Show all routes</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>

    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
