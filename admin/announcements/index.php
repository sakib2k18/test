<?php
/**
 * ADMIN - ANNOUNCEMENTS : READ (list, search, publish / unpublish)
 */
require_once __DIR__ . '/../../includes/init.php';
requireAdmin();

$pageTitle    = 'Announcements';
$pageSubtitle = 'Manage the notice board';
$adminSection = 'announcements';

$search   = get('q');
$status   = get('status');
$priority = get('priority');

if (!inList($status, ['Published', 'Draft']))               { $status = ''; }
if (!inList($priority, ['Normal', 'Important', 'Urgent']))  { $priority = ''; }

$where  = [];
$params = [];

if ($search !== '') {
    $where[] = '(title LIKE ? OR short_description LIKE ? OR content LIKE ?)';
    $like    = '%' . $search . '%';
    $params  = array_merge($params, [$like, $like, $like]);
}
if ($status !== '')   { $where[] = 'status = ?';   $params[] = $status; }
if ($priority !== '') { $where[] = 'priority = ?'; $params[] = $priority; }

$whereSql = $where ? ' WHERE ' . implode(' AND ', $where) : '';

$notices = fetchAll(
    'SELECT * FROM announcements' . $whereSql . ' ORDER BY published_on DESC, id DESC',
    $params
);

$hasFilter = $search !== '' || $status !== '' || $priority !== '';

require_once __DIR__ . '/../../includes/admin_header.php';
?>

<div class="admin-head">
    <div>
        <h1>Announcements</h1>
        <p><?= e(count($notices)) ?> <?= count($notices) === 1 ? 'notice' : 'notices' ?> listed.</p>
    </div>
    <div class="admin-head__actions">
        <a class="btn btn--primary" href="<?= e(url('admin/announcements/create.php')) ?>">
            <?= icon('plus') ?> Add announcement
        </a>
    </div>
</div>

<div class="admin-toolbar">
    <form method="get" action="<?= e(url('admin/announcements/index.php')) ?>" role="search">
        <div class="field search-field">
            <label class="field__label sr-only" for="q">Search announcements</label>
            <?= icon('search') ?>
            <input class="input" type="search" id="q" name="q" value="<?= e($search) ?>"
                   placeholder="Search by title or content..."
                   data-table-filter="#noticeTable">
        </div>

        <div class="field">
            <label class="field__label sr-only" for="status">Status</label>
            <select class="select" id="status" name="status" data-auto-submit>
                <option value="">Any status</option>
                <?php foreach (['Published', 'Draft'] as $opt): ?>
                    <option value="<?= e($opt) ?>" <?= $status === $opt ? 'selected' : '' ?>><?= e($opt) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="field">
            <label class="field__label sr-only" for="priority">Priority</label>
            <select class="select" id="priority" name="priority" data-auto-submit>
                <option value="">Any priority</option>
                <?php foreach (['Normal', 'Important', 'Urgent'] as $opt): ?>
                    <option value="<?= e($opt) ?>" <?= $priority === $opt ? 'selected' : '' ?>><?= e($opt) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="filter-actions">
            <button class="btn btn--primary" type="submit"><?= icon('search') ?> Search</button>
            <?php if ($hasFilter): ?>
                <a class="btn btn--outline" href="<?= e(url('admin/announcements/index.php')) ?>">Reset</a>
            <?php endif; ?>
        </div>
    </form>
</div>

<?php if ($notices): ?>
    <div class="table-wrap">
        <div class="table-scroll">
            <table class="table table--stack" id="noticeTable">
                <caption class="sr-only">All announcements</caption>
                <thead>
                    <tr>
                        <th scope="col">Title</th>
                        <th scope="col">Priority</th>
                        <th scope="col">Published on</th>
                        <th scope="col">Status</th>
                        <th scope="col"><span class="sr-only">Actions</span></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($notices as $n): ?>
                        <tr>
                            <td data-label="Title">
                                <span class="cell-main">
                                    <strong><?= e($n['title']) ?></strong>
                                    <span><?= e(excerpt($n['short_description'], 80)) ?></span>
                                </span>
                            </td>
                            <td data-label="Priority">
                                <span class="badge <?= e(statusClass($n['priority'])) ?>"><?= e($n['priority']) ?></span>
                            </td>
                            <td data-label="Published on"><?= e(dateText($n['published_on'])) ?></td>
                            <td data-label="Status">
                                <span class="badge <?= e(statusClass($n['status'])) ?>"><?= e($n['status']) ?></span>
                            </td>
                            <td data-label="Actions">
                                <span class="actions">
                                    <!-- Publish / unpublish toggle -->
                                    <form method="post" action="<?= e(url('admin/announcements/toggle.php')) ?>"
                                          style="display:inline;">
                                        <?= csrfField() ?>
                                        <input type="hidden" name="id" value="<?= (int) $n['id'] ?>">
                                        <button class="icon-btn" type="submit"
                                                title="<?= $n['status'] === 'Published' ? 'Unpublish' : 'Publish' ?>">
                                            <?= icon($n['status'] === 'Published' ? 'close' : 'check') ?>
                                            <span class="sr-only">
                                                <?= $n['status'] === 'Published' ? 'Unpublish' : 'Publish' ?> <?= e($n['title']) ?>
                                            </span>
                                        </button>
                                    </form>

                                    <a class="icon-btn" title="Edit"
                                       href="<?= e(url('admin/announcements/edit.php?id=' . (int) $n['id'])) ?>">
                                        <?= icon('edit') ?><span class="sr-only">Edit <?= e($n['title']) ?></span>
                                    </a>

                                    <form method="post" action="<?= e(url('admin/announcements/delete.php')) ?>"
                                          style="display:inline;"
                                          data-confirm="Delete the notice &quot;<?= e($n['title']) ?>&quot; permanently?">
                                        <?= csrfField() ?>
                                        <input type="hidden" name="id" value="<?= (int) $n['id'] ?>">
                                        <button class="icon-btn icon-btn--danger" type="submit" title="Delete">
                                            <?= icon('trash') ?><span class="sr-only">Delete <?= e($n['title']) ?></span>
                                        </button>
                                    </form>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <tr data-filter-empty hidden>
                        <td colspan="5" class="text-center text-muted" style="padding:26px;">
                            No announcement matches what you typed.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
<?php else: ?>
    <div class="empty">
        <span class="empty__icon"><?= icon('megaphone') ?></span>
        <h3><?= $hasFilter ? 'No announcements match your filters' : 'No announcements have been added yet' ?></h3>
        <p>
            <?= $hasFilter
                ? 'Try a different keyword or clear the filters.'
                : 'Publish the first notice so passengers see schedule changes on the homepage.' ?>
        </p>
        <?php if ($hasFilter): ?>
            <a class="btn btn--outline" href="<?= e(url('admin/announcements/index.php')) ?>">Clear filters</a>
        <?php else: ?>
            <a class="btn btn--primary" href="<?= e(url('admin/announcements/create.php')) ?>">
                <?= icon('plus') ?> Add announcement
            </a>
        <?php endif; ?>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../../includes/admin_footer.php'; ?>
