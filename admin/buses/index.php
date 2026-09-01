<?php
/**
 * ADMIN - BUSES : READ (list, search and filter)
 */
require_once __DIR__ . '/../../includes/init.php';
requireAdmin();

$pageTitle    = 'Buses';
$pageSubtitle = 'Manage the bus fleet';
$adminSection = 'buses';

$search = get('q');
$type   = get('type');
$status = get('status');

$typeOptions   = ['Regular', 'Student Bus', 'Faculty Bus (AC)', 'Faculty Bus (Non AC)'];
$statusOptions = ['Active', 'Maintenance', 'Inactive'];

if (!inList($type, $typeOptions))     { $type = ''; }
if (!inList($status, $statusOptions)) { $status = ''; }

$where  = [];
$params = [];

if ($search !== '') {
    $where[] = '(b.bus_name LIKE ? OR b.reg_number LIKE ? OR b.model LIKE ? OR b.driver_name LIKE ?)';
    $like    = '%' . $search . '%';
    $params  = array_merge($params, [$like, $like, $like, $like]);
}
if ($type !== '') {
    $where[]  = 'b.bus_type = ?';
    $params[] = $type;
}
if ($status !== '') {
    $where[]  = 'b.status = ?';
    $params[] = $status;
}

$whereSql = $where ? ' WHERE ' . implode(' AND ', $where) : '';

$buses = fetchAll(
    'SELECT b.*, (SELECT COUNT(*) FROM schedules s WHERE s.bus_id = b.id) AS trip_count
       FROM buses b' . $whereSql . '
      ORDER BY b.bus_name ASC',
    $params
);

require_once __DIR__ . '/../../includes/admin_header.php';
?>

<div class="admin-head">
    <div>
        <h1>Buses</h1>
        <p><?= e(count($buses)) ?> <?= count($buses) === 1 ? 'bus' : 'buses' ?> listed.</p>
    </div>
    <div class="admin-head__actions">
        <a class="btn btn--primary" href="<?= e(url('admin/buses/create.php')) ?>">
            <?= icon('plus') ?> Add new bus
        </a>
    </div>
</div>

<!-- ---------------- Search & filters ---------------- -->
<div class="admin-toolbar">
    <form method="get" action="<?= e(url('admin/buses/index.php')) ?>" role="search">
        <div class="field search-field">
            <label class="field__label sr-only" for="q">Search buses</label>
            <?= icon('search') ?>
            <input class="input" type="search" id="q" name="q" value="<?= e($search) ?>"
                   placeholder="Search by bus number, registration, model or driver..."
                   data-table-filter="#busTable">
        </div>

        <div class="field">
            <label class="field__label sr-only" for="type">Type</label>
            <select class="select" id="type" name="type" data-auto-submit>
                <option value="">All types</option>
                <?php foreach ($typeOptions as $opt): ?>
                    <option value="<?= e($opt) ?>" <?= $type === $opt ? 'selected' : '' ?>><?= e($opt) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="field">
            <label class="field__label sr-only" for="status">Status</label>
            <select class="select" id="status" name="status" data-auto-submit>
                <option value="">Any status</option>
                <?php foreach ($statusOptions as $opt): ?>
                    <option value="<?= e($opt) ?>" <?= $status === $opt ? 'selected' : '' ?>><?= e($opt) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="filter-actions">
            <button class="btn btn--primary" type="submit"><?= icon('search') ?> Search</button>
            <?php if ($search !== '' || $type !== '' || $status !== ''): ?>
                <a class="btn btn--outline" href="<?= e(url('admin/buses/index.php')) ?>">Reset</a>
            <?php endif; ?>
        </div>
    </form>
</div>

<!-- ---------------- Table ---------------- -->
<?php if ($buses): ?>
    <div class="table-wrap">
        <div class="table-scroll">
            <table class="table table--stack" id="busTable">
                <caption class="sr-only">All buses in the fleet</caption>
                <thead>
                    <tr>
                        <th scope="col">Bus</th>
                        <th scope="col">Type</th>
                        <th scope="col">Capacity</th>
                        <th scope="col">Driver</th>
                        <th scope="col">Trips</th>
                        <th scope="col">Status</th>
                        <th scope="col"><span class="sr-only">Actions</span></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($buses as $bus): ?>
                        <tr>
                            <td data-label="Bus">
                                <span class="cell-thumb">
                                    <img src="<?= e(busImage($bus['image'])) ?>" alt="" loading="lazy">
                                    <span class="cell-main">
                                        <strong><?= e($bus['bus_name']) ?></strong>
                                        <span>
                                            <?= e($bus['reg_number']) ?>
                                            <?= !isBlank($bus['model']) ? ' &middot; ' . e($bus['model']) : '' ?>
                                        </span>
                                    </span>
                                </span>
                            </td>
                            <td data-label="Type"><?= e($bus['bus_type']) ?></td>
                            <td data-label="Capacity" class="num"><?= e($bus['capacity']) ?> seats</td>
                            <td data-label="Driver">
                                <span class="cell-main">
                                    <strong><?= e($bus['driver_name'] ?: 'Not assigned') ?></strong>
                                    <span><?= e($bus['driver_contact'] ?: '--') ?></span>
                                </span>
                            </td>
                            <td data-label="Trips" class="num"><?= e($bus['trip_count']) ?></td>
                            <td data-label="Status">
                                <span class="badge <?= e(statusClass($bus['status'])) ?>"><?= e($bus['status']) ?></span>
                            </td>
                            <td data-label="Actions">
                                <span class="actions">
                                    <a class="icon-btn" title="View details"
                                       href="<?= e(url('admin/buses/view.php?id=' . (int) $bus['id'])) ?>">
                                        <?= icon('eye') ?><span class="sr-only">View <?= e($bus['bus_name']) ?></span>
                                    </a>
                                    <a class="icon-btn" title="Edit"
                                       href="<?= e(url('admin/buses/edit.php?id=' . (int) $bus['id'])) ?>">
                                        <?= icon('edit') ?><span class="sr-only">Edit <?= e($bus['bus_name']) ?></span>
                                    </a>
                                    <form method="post" action="<?= e(url('admin/buses/delete.php')) ?>"
                                          style="display:inline;"
                                          data-confirm="Delete &quot;<?= e($bus['bus_name']) ?>&quot; permanently? This cannot be undone.">
                                        <?= csrfField() ?>
                                        <input type="hidden" name="id" value="<?= (int) $bus['id'] ?>">
                                        <button class="icon-btn icon-btn--danger" type="submit" title="Delete">
                                            <?= icon('trash') ?><span class="sr-only">Delete <?= e($bus['bus_name']) ?></span>
                                        </button>
                                    </form>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <tr data-filter-empty hidden>
                        <td colspan="7" class="text-center text-muted" style="padding:26px;">
                            No bus matches what you typed.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
<?php else: ?>
    <div class="empty">
        <span class="empty__icon"><?= icon('bus') ?></span>
        <h3><?= ($search !== '' || $type !== '' || $status !== '') ? 'No buses match your filters' : 'No buses have been added yet' ?></h3>
        <p>
            <?= ($search !== '' || $type !== '' || $status !== '')
                ? 'Try a different keyword or clear the filters.'
                : 'Add the first bus of the fleet to get started.' ?>
        </p>
        <?php if ($search !== '' || $type !== '' || $status !== ''): ?>
            <a class="btn btn--outline" href="<?= e(url('admin/buses/index.php')) ?>">Clear filters</a>
        <?php else: ?>
            <a class="btn btn--primary" href="<?= e(url('admin/buses/create.php')) ?>"><?= icon('plus') ?> Add bus</a>
        <?php endif; ?>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../../includes/admin_footer.php'; ?>
