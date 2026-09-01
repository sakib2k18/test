<?php
/**
 * ADMIN - ROUTES : READ (list, search and filter)
 */
require_once __DIR__ . '/../../includes/init.php';
requireAdmin();

$pageTitle    = 'Routes & Stops';
$pageSubtitle = 'Manage routes and their stoppages';
$adminSection = 'routes';

$search = get('q');
$status = get('status');

if (!inList($status, ['Active', 'Inactive'])) { $status = ''; }

$where  = [];
$params = [];

if ($search !== '') {
    $where[] = '(r.route_name LIKE ? OR r.route_code LIKE ? OR r.start_point LIKE ? OR r.destination LIKE ?)';
    $like    = '%' . $search . '%';
    $params  = array_merge($params, [$like, $like, $like, $like]);
}
if ($status !== '') {
    $where[]  = 'r.status = ?';
    $params[] = $status;
}

$whereSql = $where ? ' WHERE ' . implode(' AND ', $where) : '';

$routes = fetchAll(
    'SELECT r.*,
            (SELECT COUNT(*) FROM route_stops s WHERE s.route_id = r.id) AS stop_count,
            (SELECT COUNT(*) FROM schedules  c WHERE c.route_id = r.id) AS trip_count
       FROM routes r' . $whereSql . '
      ORDER BY r.route_code ASC',
    $params
);

require_once __DIR__ . '/../../includes/admin_header.php';
?>

<div class="admin-head">
    <div>
        <h1>Routes</h1>
        <p><?= e(count($routes)) ?> <?= count($routes) === 1 ? 'route' : 'routes' ?> listed. Open a route to manage its stoppages.</p>
    </div>
    <div class="admin-head__actions">
        <a class="btn btn--primary" href="<?= e(url('admin/routes/create.php')) ?>">
            <?= icon('plus') ?> Add new route
        </a>
    </div>
</div>

<div class="admin-toolbar">
    <form method="get" action="<?= e(url('admin/routes/index.php')) ?>" role="search">
        <div class="field search-field">
            <label class="field__label sr-only" for="q">Search routes</label>
            <?= icon('search') ?>
            <input class="input" type="search" id="q" name="q" value="<?= e($search) ?>"
                   placeholder="Search by name, code, starting point or destination..."
                   data-table-filter="#routeTable">
        </div>

        <div class="field">
            <label class="field__label sr-only" for="status">Status</label>
            <select class="select" id="status" name="status" data-auto-submit>
                <option value="">Any status</option>
                <?php foreach (['Active', 'Inactive'] as $opt): ?>
                    <option value="<?= e($opt) ?>" <?= $status === $opt ? 'selected' : '' ?>><?= e($opt) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="filter-actions">
            <button class="btn btn--primary" type="submit"><?= icon('search') ?> Search</button>
            <?php if ($search !== '' || $status !== ''): ?>
                <a class="btn btn--outline" href="<?= e(url('admin/routes/index.php')) ?>">Reset</a>
            <?php endif; ?>
        </div>
    </form>
</div>

<?php if ($routes): ?>
    <div class="table-wrap">
        <div class="table-scroll">
            <table class="table table--stack" id="routeTable">
                <caption class="sr-only">All bus routes</caption>
                <thead>
                    <tr>
                        <th scope="col">Code</th>
                        <th scope="col">Route</th>
                        <th scope="col">Distance</th>
                        <th scope="col">Duration</th>
                        <th scope="col">Stops</th>
                        <th scope="col">Trips</th>
                        <th scope="col">Status</th>
                        <th scope="col"><span class="sr-only">Actions</span></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($routes as $r): ?>
                        <tr>
                            <td data-label="Code"><span class="code-chip"><?= e($r['route_code']) ?></span></td>
                            <td data-label="Route">
                                <span class="cell-main">
                                    <strong><?= e($r['route_name']) ?></strong>
                                    <span><?= e($r['start_point']) ?> &rarr; <?= e($r['destination']) ?></span>
                                </span>
                            </td>
                            <td data-label="Distance" class="num"><?= e(number_format((float) $r['distance_km'], 1)) ?> km</td>
                            <td data-label="Duration" class="num"><?= e(durationText($r['duration_min'])) ?></td>
                            <td data-label="Stops" class="num"><?= e($r['stop_count']) ?></td>
                            <td data-label="Trips" class="num"><?= e($r['trip_count']) ?></td>
                            <td data-label="Status">
                                <span class="badge <?= e(statusClass($r['status'])) ?>"><?= e($r['status']) ?></span>
                            </td>
                            <td data-label="Actions">
                                <span class="actions">
                                    <a class="icon-btn" title="Manage stoppages"
                                       href="<?= e(url('admin/routes/view.php?id=' . (int) $r['id'])) ?>">
                                        <?= icon('pin') ?><span class="sr-only">Stoppages of <?= e($r['route_name']) ?></span>
                                    </a>
                                    <a class="icon-btn" title="Edit route"
                                       href="<?= e(url('admin/routes/edit.php?id=' . (int) $r['id'])) ?>">
                                        <?= icon('edit') ?><span class="sr-only">Edit <?= e($r['route_name']) ?></span>
                                    </a>
                                    <form method="post" action="<?= e(url('admin/routes/delete.php')) ?>"
                                          style="display:inline;"
                                          data-confirm="Delete route &quot;<?= e($r['route_name']) ?>&quot; and all of its stoppages?">
                                        <?= csrfField() ?>
                                        <input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
                                        <button class="icon-btn icon-btn--danger" type="submit" title="Delete route">
                                            <?= icon('trash') ?><span class="sr-only">Delete <?= e($r['route_name']) ?></span>
                                        </button>
                                    </form>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <tr data-filter-empty hidden>
                        <td colspan="8" class="text-center text-muted" style="padding:26px;">
                            No route matches what you typed.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
<?php else: ?>
    <div class="empty">
        <span class="empty__icon"><?= icon('route') ?></span>
        <h3><?= ($search !== '' || $status !== '') ? 'No routes match your filters' : 'No routes have been added yet' ?></h3>
        <p>
            <?= ($search !== '' || $status !== '')
                ? 'Try a different keyword or clear the filters.'
                : 'Create the first route, then add its stoppages one by one.' ?>
        </p>
        <?php if ($search !== '' || $status !== ''): ?>
            <a class="btn btn--outline" href="<?= e(url('admin/routes/index.php')) ?>">Clear filters</a>
        <?php else: ?>
            <a class="btn btn--primary" href="<?= e(url('admin/routes/create.php')) ?>"><?= icon('plus') ?> Add route</a>
        <?php endif; ?>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../../includes/admin_footer.php'; ?>
