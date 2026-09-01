<?php
/**
 * PUBLIC PAGE - NOTICE BOARD
 * Only announcements with status "Published" are visible to visitors.
 */
require_once __DIR__ . '/includes/init.php';

$pageTitle       = 'Notices';
$pageDescription = 'Announcements and notices from the ' . UNIVERSITY_SHORT . ' Transport Office.';

$search   = get('q');
$priority = get('priority');
$page     = max(1, (int) get('page', 1));

$priorityOptions = ['Normal', 'Important', 'Urgent'];
if (!inList($priority, $priorityOptions)) { $priority = ''; }

/* Drafts are never exposed on the public site. */
$where  = ['status = ?'];
$params = ['Published'];

if ($search !== '') {
    $where[]  = '(title LIKE ? OR short_description LIKE ? OR content LIKE ?)';
    $like     = '%' . $search . '%';
    $params   = array_merge($params, [$like, $like, $like]);
}
if ($priority !== '') {
    $where[]  = 'priority = ?';
    $params[] = $priority;
}

$whereSql = ' WHERE ' . implode(' AND ', $where);

$total  = (int) fetchValue('SELECT COUNT(*) FROM announcements' . $whereSql, $params);
$pages  = max(1, (int) ceil($total / ITEMS_PER_PAGE));
$page   = min($page, $pages);
$offset = ($page - 1) * ITEMS_PER_PAGE;

$notices = fetchAll(
    'SELECT id, title, short_description, priority, published_on
       FROM announcements' . $whereSql . '
      ORDER BY FIELD(priority, ?, ?, ?), published_on DESC, id DESC
      LIMIT ' . (int) ITEMS_PER_PAGE . ' OFFSET ' . (int) $offset,
    array_merge($params, ['Urgent', 'Important', 'Normal'])
);

require_once __DIR__ . '/includes/header.php';
?>

<section class="page-header">
    <div class="container">
        <ul class="breadcrumb">
            <li><a href="<?= e(url('index.php')) ?>">Home</a></li>
            <li><?= icon('chevron') ?></li>
            <li aria-current="page">Notices</li>
        </ul>
        <h1>Notice Board</h1>
        <p>
            Schedule changes, holiday arrangements, maintenance notices and emergency
            transport updates published by the Transport Office.
        </p>
    </div>
</section>

<section class="section section--tight">
    <div class="container container--narrow">

        <div class="filter-bar">
            <form method="get" action="<?= e(url('announcements.php')) ?>" role="search">
                <div class="field search-field">
                    <label class="field__label" for="q">Search notices</label>
                    <?= icon('search') ?>
                    <input class="input" type="search" id="q" name="q"
                           value="<?= e($search) ?>" placeholder="Search by title or content...">
                </div>

                <div class="field">
                    <label class="field__label" for="priority">Priority</label>
                    <select class="select" id="priority" name="priority" data-auto-submit>
                        <option value="">All priorities</option>
                        <?php foreach ($priorityOptions as $opt): ?>
                            <option value="<?= e($opt) ?>" <?= $priority === $opt ? 'selected' : '' ?>>
                                <?= e($opt) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="filter-actions">
                    <button class="btn btn--primary" type="submit"><?= icon('search') ?> Search</button>
                    <?php if ($search !== '' || $priority !== ''): ?>
                        <a class="btn btn--outline" href="<?= e(url('announcements.php')) ?>">Reset</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <p class="result-count">
            <strong><?= e($total) ?></strong>
            <?= $total === 1 ? 'notice' : 'notices' ?> published<?= $search !== '' ? ' matching "' . e($search) . '"' : '' ?>.
        </p>

        <?php if ($notices): ?>
            <div class="stack" style="gap:16px;">
                <?php foreach ($notices as $n): ?>
                    <article class="notice reveal">
                        <div class="notice__date">
                            <b><?= e(date('d', strtotime($n['published_on']))) ?></b>
                            <span><?= e(date('M Y', strtotime($n['published_on']))) ?></span>
                        </div>
                        <div class="notice__body">
                            <span class="badge <?= e(statusClass($n['priority'])) ?>" style="margin-bottom:8px;">
                                <?= e($n['priority']) ?>
                            </span>
                            <h2 style="font-size:1.12rem;margin-bottom:6px;">
                                <a href="<?= e(url('announcement-details.php?id=' . (int) $n['id'])) ?>">
                                    <?= e($n['title']) ?>
                                </a>
                            </h2>
                            <p><?= e($n['short_description']) ?></p>
                            <a class="btn btn--ghost btn--sm"
                               href="<?= e(url('announcement-details.php?id=' . (int) $n['id'])) ?>">
                                Read full notice <?= icon('chevron') ?>
                            </a>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>

            <?php if ($pages > 1): ?>
                <nav aria-label="Notice pages">
                    <ul class="pagination">
                        <li>
                            <a class="<?= $page <= 1 ? 'is-disabled' : '' ?>"
                               href="<?= e(url('announcements.php') . withQuery(['page' => $page - 1])) ?>">Prev</a>
                        </li>
                        <?php for ($i = 1; $i <= $pages; $i++): ?>
                            <li>
                                <?php if ($i === $page): ?>
                                    <span class="is-current" aria-current="page"><?= e($i) ?></span>
                                <?php else: ?>
                                    <a href="<?= e(url('announcements.php') . withQuery(['page' => $i])) ?>"><?= e($i) ?></a>
                                <?php endif; ?>
                            </li>
                        <?php endfor; ?>
                        <li>
                            <a class="<?= $page >= $pages ? 'is-disabled' : '' ?>"
                               href="<?= e(url('announcements.php') . withQuery(['page' => $page + 1])) ?>">Next</a>
                        </li>
                    </ul>
                </nav>
            <?php endif; ?>

        <?php else: ?>
            <div class="empty">
                <span class="empty__icon"><?= icon('megaphone') ?></span>
                <h3>No notices found</h3>
                <p>
                    <?php if ($search !== '' || $priority !== ''): ?>
                        Nothing matches your search. Try a different keyword.
                    <?php else: ?>
                        There are no published notices at the moment. Please check back later.
                    <?php endif; ?>
                </p>
                <?php if ($search !== '' || $priority !== ''): ?>
                    <a class="btn btn--primary" href="<?= e(url('announcements.php')) ?>">Show all notices</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>

    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
