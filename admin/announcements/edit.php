<?php
/**
 * ADMIN - ANNOUNCEMENTS : UPDATE
 */
require_once __DIR__ . '/../../includes/init.php';
requireAdmin();
require_once __DIR__ . '/_validate.php';

$pageTitle    = 'Edit announcement';
$pageSubtitle = 'Update an existing notice';
$adminSection = 'announcements';

$id      = (int) ($_SERVER['REQUEST_METHOD'] === 'POST' ? post('id', 0) : get('id', 0));
$current = $id > 0 ? fetchOne('SELECT * FROM announcements WHERE id = ?', [$id]) : null;

if (!$current) {
    setFlash('error', 'That announcement does not exist any more.');
    redirect('admin/announcements/index.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    requireCsrf('admin/announcements/edit.php?id=' . $id);

    $input = [
        'title'             => post('title'),
        'short_description' => post('short_description'),
        'content'           => post('content'),
        'priority'          => post('priority'),
        'status'            => post('status'),
        'published_on'      => post('published_on'),
    ];

    $errors = validateAnnouncementInput($input, $id);

    if ($errors) {
        keepErrors($errors);
        keepOld($_POST);
        setFlash('error', 'The announcement could not be updated. Please check the form.');
        redirect('admin/announcements/edit.php?id=' . $id);
    }

    try {
        q(
            'UPDATE announcements
                SET title = ?, short_description = ?, content = ?,
                    priority = ?, status = ?, published_on = ?
              WHERE id = ?',
            [
                $input['title'],
                $input['short_description'],
                $input['content'],
                $input['priority'],
                $input['status'],
                $input['published_on'],
                $id,
            ]
        );

        logActivity('Announcement updated', 'Updated the notice "' . $input['title'] . '".');
        setFlash('success', 'The announcement was updated successfully.');
        redirect('admin/announcements/index.php');

    } catch (PDOException $ex) {
        error_log('[ANNOUNCEMENT EDIT] ' . $ex->getMessage());
        keepErrors(['The announcement could not be updated because of a database error.']);
        keepOld($_POST);
        setFlash('error', 'The announcement could not be updated.');
        redirect('admin/announcements/edit.php?id=' . $id);
    }
}

$errors = takeErrors();
$hasOld = !empty($_SESSION['old']);

$notice = [
    'id'                => $id,
    'title'             => $hasOld ? old('title')             : $current['title'],
    'short_description' => $hasOld ? old('short_description') : $current['short_description'],
    'content'           => $hasOld ? old('content')           : $current['content'],
    'priority'          => $hasOld ? old('priority')          : $current['priority'],
    'status'            => $hasOld ? old('status')            : $current['status'],
    'published_on'      => $hasOld ? old('published_on')      : $current['published_on'],
];

$formAction  = url('admin/announcements/edit.php?id=' . $id);
$submitLabel = 'Update announcement';
$isEdit      = true;

require_once __DIR__ . '/../../includes/admin_header.php';
?>

<div class="admin-head">
    <div>
        <h1>Edit announcement</h1>
        <p>
            Currently
            <span class="badge <?= e(statusClass($current['status'])) ?>"><?= e($current['status']) ?></span>
            &middot; published <?= e(dateText($current['published_on'])) ?>.
        </p>
    </div>
    <div class="admin-head__actions">
        <?php if ($current['status'] === 'Published'): ?>
            <a class="btn btn--outline" href="<?= e(url('announcement-details.php?id=' . $id)) ?>"
               target="_blank" rel="noopener">
                <?= icon('eye') ?> Public page
            </a>
        <?php endif; ?>
        <a class="btn btn--outline" href="<?= e(url('admin/announcements/index.php')) ?>">Back to announcements</a>
    </div>
</div>

<?php
require __DIR__ . '/_form.php';
clearOld();
require_once __DIR__ . '/../../includes/admin_footer.php';
?>
