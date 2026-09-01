<?php
/**
 * ADMIN - USERS : READ + DELETE
 * The administrator can see the registered accounts and remove a user, but
 * the single admin account itself is protected (see delete.php).
 */
require_once __DIR__ . '/../../includes/init.php';
requireAdmin();

$pageTitle    = 'Users';
$pageSubtitle = 'Registered accounts';
$adminSection = 'users';

$search = get('q');
$role   = get('role');

if (!inList($role, ['user', 'admin'])) { $role = ''; }

$where  = [];
$params = [];

if ($search !== '') {
    $where[] = '(full_name LIKE ? OR email LIKE ? OR student_id LIKE ? OR department LIKE ?)';
    $like    = '%' . $search . '%';
    $params  = array_merge($params, [$like, $like, $like, $like]);
}
if ($role !== '') {
    $where[]  = 'role = ?';
    $params[] = $role;
}

$whereSql = $where ? ' WHERE ' . implode(' AND ', $where) : '';

$users = fetchAll(
    'SELECT id, full_name, email, student_id, department, phone, role, created_at
       FROM users' . $whereSql . '
      ORDER BY role ASC, created_at DESC',
    $params
);

$totalUsers = (int) fetchValue('SELECT COUNT(*) FROM users WHERE role = ?', ['user']);
$hasFilter  = $search !== '' || $role !== '';

require_once __DIR__ . '/../../includes/admin_header.php';
?>

<div class="admin-head">
    <div>
        <h1>Users</h1>
        <p><?= e($totalUsers) ?> registered <?= $totalUsers === 1 ? 'student / staff account' : 'student and staff accounts' ?>, plus the single administrator.</p>
    </div>
</div>

<div class="alert alert--info">
    <?= icon('info') ?>
    <div>
        New accounts can only be created through the public sign-up form, and they are
        always created with the <strong>user</strong> role. There is exactly one administrator
        account and the website offers no way to create a second one.
    </div>
</div>

<div class="admin-toolbar">
    <form method="get" action="<?= e(url('admin/users/index.php')) ?>" role="search">
        <div class="field search-field">
            <label class="field__label sr-only" for="q">Search users</label>
            <?= icon('search') ?>
            <input class="input" type="search" id="q" name="q" value="<?= e($search) ?>"
                   placeholder="Search by name, email, ID or department..."
                   data-table-filter="#userTable">
        </div>

        <div class="field">
            <label class="field__label sr-only" for="role">Role</label>
            <select class="select" id="role" name="role" data-auto-submit>
                <option value="">All roles</option>
                <option value="user"  <?= $role === 'user'  ? 'selected' : '' ?>>User</option>
                <option value="admin" <?= $role === 'admin' ? 'selected' : '' ?>>Administrator</option>
            </select>
        </div>

        <div class="filter-actions">
            <button class="btn btn--primary" type="submit"><?= icon('search') ?> Search</button>
            <?php if ($hasFilter): ?>
                <a class="btn btn--outline" href="<?= e(url('admin/users/index.php')) ?>">Reset</a>
            <?php endif; ?>
        </div>
    </form>
</div>

<?php if ($users): ?>
    <div class="table-wrap">
        <div class="table-scroll">
            <table class="table table--stack" id="userTable">
                <caption class="sr-only">Registered accounts</caption>
                <thead>
                    <tr>
                        <th scope="col">Name</th>
                        <th scope="col">Student / Employee ID</th>
                        <th scope="col">Department</th>
                        <th scope="col">Phone</th>
                        <th scope="col">Joined</th>
                        <th scope="col">Role</th>
                        <th scope="col"><span class="sr-only">Actions</span></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $u): ?>
                        <tr>
                            <td data-label="Name">
                                <span class="cell-thumb">
                                    <span class="nav-user__avatar" aria-hidden="true">
                                        <?= e(strtoupper(mb_substr($u['full_name'], 0, 1))) ?>
                                    </span>
                                    <span class="cell-main">
                                        <strong><?= e($u['full_name']) ?></strong>
                                        <span><?= e($u['email']) ?></span>
                                    </span>
                                </span>
                            </td>
                            <td data-label="Student ID"><?= e($u['student_id'] ?: '--') ?></td>
                            <td data-label="Department"><?= e($u['department'] ?: '--') ?></td>
                            <td data-label="Phone"><?= e($u['phone'] ?: '--') ?></td>
                            <td data-label="Joined"><?= e(dateText($u['created_at'])) ?></td>
                            <td data-label="Role">
                                <span class="badge <?= $u['role'] === 'admin' ? 'badge--warning' : 'badge--info' ?>">
                                    <?= $u['role'] === 'admin' ? 'Administrator' : 'User' ?>
                                </span>
                            </td>
                            <td data-label="Actions">
                                <span class="actions">
                                    <?php if ($u['role'] === 'admin'): ?>
                                        <span class="chip chip--outline" title="The administrator account is protected">
                                            <?= icon('lock') ?> Protected
                                        </span>
                                    <?php else: ?>
                                        <form method="post" action="<?= e(url('admin/users/delete.php')) ?>"
                                              style="display:inline;"
                                              data-confirm="Delete the account of <?= e($u['full_name']) ?>? This cannot be undone.">
                                            <?= csrfField() ?>
                                            <input type="hidden" name="id" value="<?= (int) $u['id'] ?>">
                                            <button class="icon-btn icon-btn--danger" type="submit" title="Delete account">
                                                <?= icon('trash') ?><span class="sr-only">Delete <?= e($u['full_name']) ?></span>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <tr data-filter-empty hidden>
                        <td colspan="7" class="text-center text-muted" style="padding:26px;">
                            No account matches what you typed.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
<?php else: ?>
    <div class="empty">
        <span class="empty__icon"><?= icon('users') ?></span>
        <h3><?= $hasFilter ? 'No accounts match your filters' : 'No accounts have registered yet' ?></h3>
        <p>
            <?= $hasFilter
                ? 'Try a different keyword or clear the filters.'
                : 'Students and staff can create an account from the public sign-up page.' ?>
        </p>
        <?php if ($hasFilter): ?>
            <a class="btn btn--outline" href="<?= e(url('admin/users/index.php')) ?>">Clear filters</a>
        <?php endif; ?>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../../includes/admin_footer.php'; ?>
