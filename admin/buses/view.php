<?php
/**
 * ADMIN - BUSES : READ ONE
 */
require_once __DIR__ . '/../../includes/init.php';
requireAdmin();

$id  = (int) get('id', 0);
$bus = $id > 0 ? fetchOne('SELECT * FROM buses WHERE id = ?', [$id]) : null;

if (!$bus) {
    setFlash('error', 'That bus does not exist any more.');
    redirect('admin/buses/index.php');
}

$pageTitle    = $bus['bus_name'];
$pageSubtitle = 'Bus details';
$adminSection = 'buses';

$facilities = fetchAll(
    'SELECT f.name, f.icon_key
       FROM facilities f
       JOIN bus_facilities bf ON bf.facility_id = f.id
      WHERE bf.bus_id = ?
      ORDER BY f.name',
    [$id]
);

$schedules = fetchAll(
    'SELECT s.id, s.departure_time, s.arrival_time, s.operating_days, s.status,
            r.route_name, r.route_code
       FROM schedules s
       JOIN routes r ON r.id = s.route_id
      WHERE s.bus_id = ?
      ORDER BY s.departure_time',
    [$id]
);

require_once __DIR__ . '/../../includes/admin_header.php';
?>

<div class="admin-head">
    <div>
        <h1><?= e($bus['bus_name']) ?></h1>
        <p><?= e($bus['reg_number']) ?> &middot; <?= e($bus['bus_type']) ?></p>
    </div>
    <div class="admin-head__actions">
        <a class="btn btn--outline" href="<?= e(url('bus-details.php?id=' . $id)) ?>" target="_blank" rel="noopener">
            <?= icon('eye') ?> Public page
        </a>
        <a class="btn btn--primary" href="<?= e(url('admin/buses/edit.php?id=' . $id)) ?>">
            <?= icon('edit') ?> Edit bus
        </a>
    </div>
</div>

<div class="admin-layout">
    <div class="stack" style="gap:22px;">

        <section class="panel">
            <div class="panel__head"><h2>Specifications</h2></div>
            <div class="panel__body">
                <dl class="meta-grid">
                    <div><dt>Bus name</dt><dd><?= e($bus['bus_name']) ?></dd></div>
                    <div><dt>Registration</dt><dd><?= e($bus['reg_number']) ?></dd></div>
                    <div><dt>Model</dt><dd><?= e($bus['model'] ?: 'Not recorded') ?></dd></div>
                    <div><dt>Capacity</dt><dd><?= e($bus['capacity']) ?> seats</dd></div>
                    <div><dt>Type</dt><dd><?= e($bus['bus_type']) ?></dd></div>
                    <div>
                        <dt>Status</dt>
                        <dd><span class="badge <?= e(statusClass($bus['status'])) ?>"><?= e($bus['status']) ?></span></dd>
                    </div>
                    <div><dt>Driver</dt><dd><?= e($bus['driver_name'] ?: 'Not assigned') ?></dd></div>
                    <div><dt>Driver contact</dt><dd><?= e($bus['driver_contact'] ?: '--') ?></dd></div>
                    <div><dt>Created</dt><dd><?= e(dateText($bus['created_at'])) ?></dd></div>
                    <div><dt>Last updated</dt><dd><?= e(dateText($bus['updated_at'])) ?></dd></div>
                </dl>

                <h3 style="font-size:1rem;margin:24px 0 10px;">Description</h3>
                <p class="text-muted" style="font-size:.92rem;">
                    <?= nl2br(e($bus['description'] ?: 'No description has been added for this bus.')) ?>
                </p>

                <?php if ($facilities): ?>
                    <h3 style="font-size:1rem;margin:24px 0 12px;">Facilities</h3>
                    <div class="cluster">
                        <?php foreach ($facilities as $f): ?>
                            <span class="chip"><?= icon($f['icon_key']) ?> <?= e($f['name']) ?></span>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </section>

        <section class="panel">
            <div class="panel__head">
                <div>
                    <h2>Assigned trips</h2>
                    <p><?= e(count($schedules)) ?> schedule <?= count($schedules) === 1 ? 'entry' : 'entries' ?>.</p>
                </div>
                <a class="btn btn--outline btn--sm" href="<?= e(url('admin/schedules/create.php?bus=' . $id)) ?>">
                    <?= icon('plus') ?> Add trip
                </a>
            </div>
            <div class="panel__body panel__body--flush">
                <?php if ($schedules): ?>
                    <div class="table-scroll">
                        <table class="table table--stack">
                            <thead>
                                <tr>
                                    <th scope="col">Route</th>
                                    <th scope="col">Departure</th>
                                    <th scope="col">Arrival</th>
                                    <th scope="col">Days</th>
                                    <th scope="col">Status</th>
                                    <th scope="col"><span class="sr-only">Actions</span></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($schedules as $s): ?>
                                    <tr>
                                        <td data-label="Route">
                                            <span class="cell-main">
                                                <strong><?= e($s['route_name']) ?></strong>
                                                <span><?= e($s['route_code']) ?></span>
                                            </span>
                                        </td>
                                        <td data-label="Departure" class="num"><?= e(timeText($s['departure_time'])) ?></td>
                                        <td data-label="Arrival" class="num"><?= e(timeText($s['arrival_time'])) ?></td>
                                        <td data-label="Days">
                                            <span class="cluster" style="gap:4px;">
                                                <?php foreach (dayBadges($s['operating_days']) as $d): ?>
                                                    <span class="day-chip"><?= e($d) ?></span>
                                                <?php endforeach; ?>
                                            </span>
                                        </td>
                                        <td data-label="Status">
                                            <span class="badge <?= e(statusClass($s['status'])) ?>"><?= e($s['status']) ?></span>
                                        </td>
                                        <td data-label="Actions">
                                            <span class="actions">
                                                <a class="icon-btn" title="Edit trip"
                                                   href="<?= e(url('admin/schedules/edit.php?id=' . (int) $s['id'])) ?>">
                                                    <?= icon('edit') ?><span class="sr-only">Edit trip</span>
                                                </a>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="empty" style="padding:34px 18px;border:0;">
                        <span class="empty__icon"><?= icon('clock') ?></span>
                        <h3 style="font-size:1.05rem;">No trips assigned</h3>
                        <p>This bus does not appear in the timetable yet.</p>
                        <a class="btn btn--primary btn--sm" href="<?= e(url('admin/schedules/create.php?bus=' . $id)) ?>">
                            <?= icon('plus') ?> Assign a trip
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    </div>

    <div class="stack" style="gap:22px;">
        <section class="panel">
            <div class="panel__head"><h2>Photo</h2></div>
            <div class="panel__body">
                <img src="<?= e(busImage($bus['image'])) ?>" alt="Photo of <?= e($bus['bus_name']) ?>"
                     style="width:100%;aspect-ratio:16/10;object-fit:cover;border-radius:12px;border:1px solid var(--border);">
                <p class="field__hint" style="margin-top:10px;">
                    <?= isBlank($bus['image'])
                        ? 'No photo uploaded - the placeholder is shown. Upload one from the edit page.'
                        : 'Stored in assets/uploads/buses/' . e($bus['image']) ?>
                </p>
            </div>
        </section>

        <section class="panel">
            <div class="panel__head"><h2>Danger zone</h2></div>
            <div class="panel__body">
                <?php if ($schedules): ?>
                    <div class="alert alert--warning" style="margin:0 0 14px;">
                        <?= icon('alert') ?>
                        <div>
                            This bus is used by <strong><?= e(count($schedules)) ?></strong>
                            schedule <?= count($schedules) === 1 ? 'entry' : 'entries' ?>.
                            Remove those trips first, or set the bus to <em>Inactive</em> instead of deleting it.
                        </div>
                    </div>
                <?php else: ?>
                    <p class="text-muted" style="font-size:.88rem;margin-bottom:14px;">
                        Deleting removes the bus record and its uploaded photo permanently.
                    </p>
                <?php endif; ?>

                <form method="post" action="<?= e(url('admin/buses/delete.php')) ?>"
                      data-confirm="Delete &quot;<?= e($bus['bus_name']) ?>&quot; permanently? This cannot be undone.">
                    <?= csrfField() ?>
                    <input type="hidden" name="id" value="<?= (int) $bus['id'] ?>">
                    <button class="btn btn--danger btn--block" type="submit">
                        <?= icon('trash') ?> Delete this bus
                    </button>
                </form>
            </div>
        </section>

        <a class="btn btn--outline btn--block" href="<?= e(url('admin/buses/index.php')) ?>">Back to all buses</a>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/admin_footer.php'; ?>
