<?php
/**
 * PUBLIC PAGE - BUS FLEET
 * Search + filter + pagination, all executed by MySQL with prepared statements.
 */
require_once __DIR__ . '/includes/init.php';

$pageTitle       = 'Our Buses';
$pageDescription = 'Browse the complete bus fleet of the ' . UNIVERSITY_SHORT . ' Bus Service.';

/* ---------------- Read and sanitise the filter values ------------------- */
$search = get('q');
$type   = get('type');
$status = get('status');
$page   = max(1, (int) get('page', 1));

$typeOptions   = ['Regular', 'Student Bus', 'Faculty Bus (AC)', 'Faculty Bus (Non AC)'];
$statusOptions = ['Active', 'Maintenance', 'Inactive'];

/* A filter value that is not in the allowed list is simply ignored. */
if (!inList($type, $typeOptions))     { $type = ''; }
if (!inList($status, $statusOptions)) { $status = ''; }

/* ---------------- Build the query -------------------------------------- */
$where  = [];
$params = [];

if ($search !== '') {
    // Search across bus number, registration plate and model.
    $where[]  = '(bus_name LIKE ? OR reg_number LIKE ? OR model LIKE ?)';
    $like     = '%' . $search . '%';
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}
if ($type !== '') {
    $where[]  = 'bus_type = ?';
    $params[] = $type;
}
if ($status !== '') {
    $where[]  = 'status = ?';
    $params[] = $status;
}

$whereSql = $where ? ' WHERE ' . implode(' AND ', $where) : '';

$total  = (int) fetchValue('SELECT COUNT(*) FROM buses' . $whereSql, $params);
$pages  = max(1, (int) ceil($total / ITEMS_PER_PAGE));
$page   = min($page, $pages);
$offset = ($page - 1) * ITEMS_PER_PAGE;

/* LIMIT/OFFSET are cast to integers, so they can never carry SQL. */
$buses = fetchAll(
    'SELECT id, bus_name, reg_number, model, capacity, bus_type, status, description, image
       FROM buses' . $whereSql . '
      ORDER BY FIELD(status, ?, ?, ?), bus_name ASC
      LIMIT ' . (int) ITEMS_PER_PAGE . ' OFFSET ' . (int) $offset,
    array_merge($params, ['Active', 'Maintenance', 'Inactive'])
);

require_once __DIR__ . '/includes/header.php';
?>

<section class="page-header">
    <div class="container">
        <ul class="breadcrumb">
            <li><a href="<?= e(url('index.php')) ?>">Home</a></li>
            <li><?= icon('chevron') ?></li>
            <li aria-current="page">Buses</li>
        </ul>
        <h1>Our Bus Fleet</h1>
        <p>
            Every bus operated by the <?= e(UNIVERSITY_SHORT) ?> Transport Office, with its
            capacity, current condition and the driver on duty.
        </p>
    </div>
</section>

<section class="section section--tight">
    <div class="container">

        <!-- ---------------- Search & filters ---------------- -->
        <div class="filter-bar">
            <form method="get" action="<?= e(url('buses.php')) ?>" role="search">
                <div class="field search-field">
                    <label class="field__label" for="q">Search the fleet</label>
                    <?= icon('search') ?>
                    <input class="input" type="search" id="q" name="q"
                           value="<?= e($search) ?>"
                           placeholder="Bus number, registration or model...">
                </div>

                <div class="field">
                    <label class="field__label" for="type">Bus type</label>
                    <select class="select" id="type" name="type" data-auto-submit>
                        <option value="">All types</option>
                        <?php foreach ($typeOptions as $opt): ?>
                            <option value="<?= e($opt) ?>" <?= $type === $opt ? 'selected' : '' ?>>
                                <?= e($opt) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
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
                    <?php if ($search !== '' || $type !== '' || $status !== ''): ?>
                        <a class="btn btn--outline" href="<?= e(url('buses.php')) ?>">Reset</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <p class="result-count">
            <strong><?= e($total) ?></strong>
            <?= $total === 1 ? 'bus' : 'buses' ?> found<?= $search !== '' ? ' for "' . e($search) . '"' : '' ?>.
        </p>

        <!-- ---------------- Results ---------------- -->
        <?php if ($buses): ?>
            <div class="grid grid--cards">
                <?php foreach ($buses as $bus): ?>
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
                            <h2 class="card__title">
                                <a href="<?= e(url('bus-details.php?id=' . (int) $bus['id'])) ?>">
                                    <?= e($bus['bus_name']) ?>
                                </a>
                            </h2>
                            <p class="text-muted" style="font-size:.82rem;"><?= e($bus['reg_number']) ?></p>

                            <p class="card__text">
                                <?= e(excerpt($bus['description'], 100) ?: 'No description has been added for this bus yet.') ?>
                            </p>

                            <ul class="meta-list">
                                <li><?= icon('seat') ?> <strong><?= e($bus['capacity']) ?></strong> seats</li>
                                <li><?= icon('bus') ?> <?= e($bus['model'] ?: 'Model not recorded') ?></li>
                            </ul>
                        </div>

                        <div class="card__foot">
                            <span class="chip chip--outline"><?= icon('bus') ?> <?= e($bus['bus_type']) ?></span>
                            <a class="btn btn--primary btn--sm"
                               href="<?= e(url('bus-details.php?id=' . (int) $bus['id'])) ?>">
                                View details <?= icon('chevron') ?>
                            </a>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>

            <!-- ---------------- Pagination ---------------- -->
            <?php if ($pages > 1): ?>
                <nav aria-label="Bus list pages">
                    <ul class="pagination">
                        <li>
                            <a class="<?= $page <= 1 ? 'is-disabled' : '' ?>"
                               href="<?= e(url('buses.php') . withQuery(['page' => $page - 1])) ?>"
                               aria-label="Previous page">Prev</a>
                        </li>
                        <?php for ($i = 1; $i <= $pages; $i++): ?>
                            <li>
                                <?php if ($i === $page): ?>
                                    <span class="is-current" aria-current="page"><?= e($i) ?></span>
                                <?php else: ?>
                                    <a href="<?= e(url('buses.php') . withQuery(['page' => $i])) ?>"><?= e($i) ?></a>
                                <?php endif; ?>
                            </li>
                        <?php endfor; ?>
                        <li>
                            <a class="<?= $page >= $pages ? 'is-disabled' : '' ?>"
                               href="<?= e(url('buses.php') . withQuery(['page' => $page + 1])) ?>"
                               aria-label="Next page">Next</a>
                        </li>
                    </ul>
                </nav>
            <?php endif; ?>

        <?php else: ?>
            <div class="empty">
                <span class="empty__icon"><?= icon('bus') ?></span>
                <h3>No buses match your search</h3>
                <p>
                    <?php if ($search !== '' || $type !== '' || $status !== ''): ?>
                        Try a different keyword, or clear the filters to see the whole fleet.
                    <?php else: ?>
                        No buses are currently available on the portal.
                    <?php endif; ?>
                </p>
                <?php if ($search !== '' || $type !== '' || $status !== ''): ?>
                    <a class="btn btn--primary" href="<?= e(url('buses.php')) ?>">Show all buses</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>

    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
