<?php
/**
 * ADMIN - ANNOUNCEMENTS : CREATE
 */
require_once __DIR__ . '/../../includes/init.php';
requireAdmin();
require_once __DIR__ . '/_validate.php';

$pageTitle    = 'Add announcement';
$pageSubtitle = 'Publish a new notice';
$adminSection = 'announcements';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    requireCsrf('admin/announcements/create.php');

    $input = [
        'title'             => post('title'),
        'short_description' => post('short_description'),
        'content'           => post('content'),
        'priority'          => post('priority'),
        'status'            => post('status'),
        'published_on'      => post('published_on'),
    ];

    $errors = validateAnnouncementInput($input);

    if ($errors) {
        keepErrors($errors);
        keepOld($_POST);
        setFlash('error', 'The announcement could not be saved. Please check the form.');
        redirect('admin/announcements/create.php');
    }

    try {
        q(
            'INSERT INTO announcements
               (title, short_description, content, priority, status, published_on)
             VALUES (?, ?, ?, ?, ?, ?)',
            [
                $input['title'],
                $input['short_description'],
                $input['content'],
                $input['priority'],
                $input['status'],
                $input['published_on'],
            ]
        );

        logActivity(
            $input['status'] === 'Published' ? 'Announcement published' : 'Announcement drafted',
            ($input['status'] === 'Published' ? 'Published' : 'Saved as draft') . ' the notice "' . $input['title'] . '".'
        );

        setFlash(
            'success',
            $input['status'] === 'Published'
                ? 'The announcement was published successfully.'
                : 'The announcement was saved as a draft.'
        );
        redirect('admin/announcements/index.php');

    } catch (PDOException $ex) {
        error_log('[ANNOUNCEMENT CREATE] ' . $ex->getMessage());
        keepErrors(['The announcement could not be saved because of a database error.']);
        keepOld($_POST);
        setFlash('error', 'The announcement could not be saved.');
        redirect('admin/announcements/create.php');
    }
}

$errors = takeErrors();

$notice = [
    'id'                => 0,
    'title'             => old('title'),
    'short_description' => old('short_description'),
    'content'           => old('content'),
    'priority'          => old('priority', 'Normal'),
    'status'            => old('status', 'Published'),
    'published_on'      => old('published_on', date('Y-m-d')),
];

$formAction  = url('admin/announcements/create.php');
$submitLabel = 'Save announcement';
$isEdit      = false;

require_once __DIR__ . '/../../includes/admin_header.php';
?>

<div class="admin-head">
    <div>
        <h1>Add an announcement</h1>
        <p>Published notices appear on the homepage and on the public notice board.</p>
    </div>
    <div class="admin-head__actions">
        <a class="btn btn--outline" href="<?= e(url('admin/announcements/index.php')) ?>">Back to announcements</a>
    </div>
</div>

<?php
require __DIR__ . '/_form.php';
clearOld();
require_once __DIR__ . '/../../includes/admin_footer.php';
?>
