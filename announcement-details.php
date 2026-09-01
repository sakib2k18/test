<?php
/**
 * PUBLIC PAGE - SINGLE NOTICE
 */
require_once __DIR__ . '/includes/init.php';

$id = (int) get('id', 0);

/* A draft must not be readable through a guessed URL either. */
$notice = $id > 0
    ? fetchOne('SELECT * FROM announcements WHERE id = ? AND status = ?', [$id, 'Published'])
    : null;

if (!$notice) {
    http_response_code(404);
    require __DIR__ . '/404.php';
    exit;
}

$pageTitle       = $notice['title'];
$pageDescription = excerpt($notice['short_description'], 150);

$related = fetchAll(
    'SELECT id, title, published_on, priority
       FROM announcements
      WHERE status = ? AND id <> ?
      ORDER BY published_on DESC
      LIMIT 4',
    ['Published', $id]
);

require_once __DIR__ . '/includes/header.php';
?>

<section class="page-header">
    <div class="container container--narrow">
        <ul class="breadcrumb">
            <li><a href="<?= e(url('index.php')) ?>">Home</a></li>
            <li><?= icon('chevron') ?></li>
            <li><a href="<?= e(url('announcements.php')) ?>">Notices</a></li>
            <li><?= icon('chevron') ?></li>
            <li aria-current="page"><?= e(excerpt($notice['title'], 40)) ?></li>
        </ul>
        <h1 style="font-size:clamp(1.6rem,1.2rem+1.6vw,2.4rem);"><?= e($notice['title']) ?></h1>
        <p>
            Published on <?= e(dateText($notice['published_on'])) ?>
            &middot; Priority: <?= e($notice['priority']) ?>
        </p>
    </div>
</section>

<section class="section section--tight">
    <div class="container container--narrow">

        <article class="article">
            <div class="cluster mb-3">
                <span class="badge <?= e(statusClass($notice['priority'])) ?>"><?= e($notice['priority']) ?></span>
                <span class="chip chip--outline"><?= icon('calendar') ?> <?= e(dateText($notice['published_on'])) ?></span>
            </div>

            <p class="lead" style="color:var(--text);font-weight:500;">
                <?= e($notice['short_description']) ?>
            </p>

            <hr>

            <?php
            /* The content is stored as plain text. Escape it, then turn the
               blank lines into paragraphs so long notices stay readable. */
            $paragraphs = preg_split('/\r\n\r\n|\n\n/', trim($notice['content']));
            foreach ($paragraphs as $paragraph):
                if (trim($paragraph) === '') { continue; }
                ?>
                <p><?= nl2br(e(trim($paragraph))) ?></p>
            <?php endforeach; ?>

            <hr>

            <div class="cluster">
                <a class="btn btn--outline" href="<?= e(url('announcements.php')) ?>">
                    Back to the notice board
                </a>
                <a class="btn btn--primary" href="<?= e(url('schedules.php')) ?>">
                    <?= icon('calendar') ?> Check the timetable
                </a>
            </div>
        </article>

        <?php if ($related): ?>
            <h2 style="font-size:1.2rem;margin:36px 0 16px;">Other notices</h2>
            <div class="stack" style="gap:12px;">
                <?php foreach ($related as $r): ?>
                    <a class="route-line" style="text-decoration:none;color:inherit;"
                       href="<?= e(url('announcement-details.php?id=' . (int) $r['id'])) ?>">
                        <span class="route-line__point spacer">
                            <span><?= e(dateText($r['published_on'])) ?></span>
                            <b><?= e($r['title']) ?></b>
                        </span>
                        <span class="badge <?= e(statusClass($r['priority'])) ?>"><?= e($r['priority']) ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
