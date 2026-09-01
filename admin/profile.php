<?php
/**
 * ADMIN - MY ACCOUNT
 * The administrator can update their own details and change the password.
 * The role field is never editable, so the account can never stop being the
 * single administrator of the site.
 */
require_once __DIR__ . '/../includes/init.php';
requireAdmin();

$pageTitle    = 'My Account';
$pageSubtitle = 'Administrator details and password';
$adminSection = 'profile';

$adminId = (int) $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    requireCsrf('admin/profile.php');

    $formName = post('form');

    /* ---------------------------------------------------------------
       1. Update the profile details
       --------------------------------------------------------------- */
    if ($formName === 'details') {

        $fullName   = post('full_name');
        $email      = post('email');
        $phone      = post('phone');
        $department = post('department');

        $errors = [];

        if (isBlank($fullName)) {
            $errors[] = 'Your full name is required.';
        } elseif (mb_strlen($fullName) < 3 || mb_strlen($fullName) > 100) {
            $errors[] = 'Your full name must be between 3 and 100 characters.';
        }

        if (isBlank($email)) {
            $errors[] = 'An email address is required.';
        } elseif (!isValidEmail($email)) {
            $errors[] = 'Please enter a valid email address.';
        } elseif (existsInTable('users', 'email', $email, $adminId)) {
            $errors[] = 'Another account already uses this email address.';
        }

        if (!isBlank($phone) && !isValidPhone($phone)) {
            $errors[] = 'Please enter a valid mobile number, for example 01712345678.';
        }

        if (!isBlank($department) && mb_strlen($department) > 100) {
            $errors[] = 'The department name is too long.';
        }

        if ($errors) {
            keepErrors($errors);
            keepOld($_POST);
            setFlash('error', 'Your details could not be updated.');
            redirect('admin/profile.php');
        }

        try {
            q(
                'UPDATE users SET full_name = ?, email = ?, phone = ?, department = ? WHERE id = ?',
                [$fullName, $email, $phone !== '' ? $phone : null, $department !== '' ? $department : null, $adminId]
            );

            $_SESSION['user_name'] = $fullName;
            logActivity('Account updated', 'The administrator updated their account details.');
            setFlash('success', 'Your details were updated successfully.');

        } catch (PDOException $ex) {
            error_log('[PROFILE] ' . $ex->getMessage());
            keepErrors(['Your details could not be saved because of a database error.']);
            setFlash('error', 'Update failed.');
        }

        redirect('admin/profile.php');
    }

    /* ---------------------------------------------------------------
       2. Change the password
       --------------------------------------------------------------- */
    if ($formName === 'password') {

        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword     = $_POST['new_password']     ?? '';
        $confirm         = $_POST['confirm_password'] ?? '';

        $errors = [];
        $row    = fetchOne('SELECT password FROM users WHERE id = ?', [$adminId]);

        if ($currentPassword === '') {
            $errors[] = 'Please enter your current password.';
        } elseif (!$row || !password_verify($currentPassword, $row['password'])) {
            $errors[] = 'Your current password is not correct.';
        }

        if ($newPassword === '') {
            $errors[] = 'Please enter a new password.';
        } elseif (!isStrongPassword($newPassword)) {
            $errors[] = 'The new password must be at least 6 characters long and contain a letter and a number.';
        } elseif ($newPassword === $currentPassword) {
            $errors[] = 'The new password must be different from the current one.';
        }

        if ($confirm === '') {
            $errors[] = 'Please confirm the new password.';
        } elseif ($newPassword !== $confirm) {
            $errors[] = 'The two new passwords do not match.';
        }

        if ($errors) {
            keepErrors($errors);
            setFlash('error', 'Your password could not be changed.');
            redirect('admin/profile.php');
        }

        try {
            q(
                'UPDATE users SET password = ? WHERE id = ?',
                [password_hash($newPassword, PASSWORD_DEFAULT), $adminId]
            );

            logActivity('Account password changed', 'The administrator changed their password.');
            setFlash('success', 'Your password was changed successfully.');

        } catch (PDOException $ex) {
            error_log('[PASSWORD] ' . $ex->getMessage());
            setFlash('error', 'The password could not be changed.');
        }

        redirect('admin/profile.php');
    }

    setFlash('error', 'Unknown request.');
    redirect('admin/profile.php');
}

$errors  = takeErrors();
$account = fetchOne('SELECT * FROM users WHERE id = ?', [$adminId]);
$hasOld  = !empty($_SESSION['old']);

$loginCount = (int) fetchValue(
    'SELECT COUNT(*) FROM activity_logs WHERE user_id = ? AND action = ?',
    [$adminId, 'Login']
);

require_once __DIR__ . '/../includes/admin_header.php';
?>

<div class="admin-head">
    <div>
        <h1>My Account</h1>
        <p>You are the single administrator of the <?= e(SITE_NAME) ?> portal.</p>
    </div>
</div>

<?= errorSummary($errors) ?>

<div class="admin-layout">

    <div class="stack" style="gap:22px;">

        <!-- ---------------- Details ---------------- -->
        <section class="panel">
            <div class="panel__head">
                <div>
                    <h2>Account details</h2>
                    <p>These details are used for the admin panel only.</p>
                </div>
            </div>
            <div class="panel__body">
                <form class="form js-validate" method="post" action="<?= e(url('admin/profile.php')) ?>">
                    <?= csrfField() ?>
                    <input type="hidden" name="form" value="details">

                    <div class="form-row">
                        <div class="field">
                            <label class="field__label" for="full_name">
                                Full name <span class="req" aria-hidden="true">*</span>
                            </label>
                            <input class="input" type="text" id="full_name" name="full_name"
                                   value="<?= e($hasOld ? old('full_name') : $account['full_name']) ?>"
                                   data-label="Full name" data-rule-required
                                   data-rule-min="3" data-rule-max="100" required>
                            <p class="field__error"></p>
                        </div>

                        <div class="field">
                            <label class="field__label" for="email">
                                Email address <span class="req" aria-hidden="true">*</span>
                            </label>
                            <input class="input" type="email" id="email" name="email"
                                   value="<?= e($hasOld ? old('email') : $account['email']) ?>"
                                   data-label="Email address" data-rule-required data-rule-email required>
                            <p class="field__hint">This is the address you log in with.</p>
                            <p class="field__error"></p>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="field">
                            <label class="field__label" for="phone">Mobile number</label>
                            <input class="input" type="tel" id="phone" name="phone"
                                   value="<?= e($hasOld ? old('phone') : (string) $account['phone']) ?>"
                                   placeholder="01712345678"
                                   data-label="Mobile number" data-rule-phone>
                            <p class="field__error"></p>
                        </div>

                        <div class="field">
                            <label class="field__label" for="department">Office / Department</label>
                            <input class="input" type="text" id="department" name="department"
                                   value="<?= e($hasOld ? old('department') : (string) $account['department']) ?>"
                                   data-label="Department" data-rule-max="100">
                            <p class="field__error"></p>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button class="btn btn--primary" type="submit"><?= icon('check') ?> Save details</button>
                    </div>
                </form>
            </div>
        </section>

        <!-- ---------------- Password ---------------- -->
        <section class="panel">
            <div class="panel__head">
                <div>
                    <h2>Change password</h2>
                    <p>Passwords are stored as a secure hash, never as plain text.</p>
                </div>
            </div>
            <div class="panel__body">
                <form class="form js-validate" method="post" action="<?= e(url('admin/profile.php')) ?>">
                    <?= csrfField() ?>
                    <input type="hidden" name="form" value="password">

                    <div class="field">
                        <label class="field__label" for="current_password">
                            Current password <span class="req" aria-hidden="true">*</span>
                        </label>
                        <div class="input-group">
                            <input class="input" type="password" id="current_password" name="current_password"
                                   data-label="Current password" data-rule-required
                                   autocomplete="current-password" required>
                            <button class="input-toggle" type="button"
                                    data-toggle-password="current_password" aria-label="Show password">
                                <?= icon('eye') ?>
                            </button>
                        </div>
                        <p class="field__error"></p>
                    </div>

                    <div class="form-row">
                        <div class="field">
                            <label class="field__label" for="new_password">
                                New password <span class="req" aria-hidden="true">*</span>
                            </label>
                            <div class="input-group">
                                <input class="input" type="password" id="new_password" name="new_password"
                                       data-label="New password" data-rule-required data-rule-password
                                       autocomplete="new-password" required>
                                <button class="input-toggle" type="button"
                                        data-toggle-password="new_password" aria-label="Show password">
                                    <?= icon('eye') ?>
                                </button>
                            </div>
                            <p class="field__hint">At least 6 characters, with one letter and one number.</p>
                            <p class="field__error"></p>
                        </div>

                        <div class="field">
                            <label class="field__label" for="confirm_password">
                                Confirm new password <span class="req" aria-hidden="true">*</span>
                            </label>
                            <div class="input-group">
                                <input class="input" type="password" id="confirm_password" name="confirm_password"
                                       data-label="Confirm password" data-rule-required
                                       data-rule-match="new_password"
                                       autocomplete="new-password" required>
                                <button class="input-toggle" type="button"
                                        data-toggle-password="confirm_password" aria-label="Show password">
                                    <?= icon('eye') ?>
                                </button>
                            </div>
                            <p class="field__error"></p>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button class="btn btn--primary" type="submit"><?= icon('lock') ?> Change password</button>
                    </div>
                </form>
            </div>
        </section>
    </div>

    <div class="stack" style="gap:22px;">
        <section class="panel">
            <div class="panel__head"><h2>Account summary</h2></div>
            <div class="panel__body">
                <div class="cluster mb-3" style="gap:13px;flex-wrap:nowrap;">
                    <span class="nav-user__avatar" style="width:52px;height:52px;font-size:1.2rem;">
                        <?= e(strtoupper(mb_substr($account['full_name'], 0, 1))) ?>
                    </span>
                    <span>
                        <b style="display:block;"><?= e($account['full_name']) ?></b>
                        <span class="text-muted" style="font-size:.85rem;"><?= e($account['email']) ?></span>
                    </span>
                </div>

                <ul class="meta-list" style="margin:0;">
                    <li><?= icon('shield') ?> Role: <strong>Administrator</strong></li>
                    <li><?= icon('calendar') ?> Joined <?= e(dateText($account['created_at'])) ?></li>
                    <li><?= icon('login') ?> <strong><?= e($loginCount) ?></strong> recorded logins</li>
                </ul>

                <div class="alert alert--info" style="margin:20px 0 0;">
                    <?= icon('info') ?>
                    <div>
                        The role of this account cannot be changed from the website, and the
                        sign-up form can only create normal users - so there will always be
                        exactly one administrator.
                    </div>
                </div>
            </div>
        </section>

        <a class="btn btn--outline btn--block" href="<?= e(url('admin/index.php')) ?>">Back to dashboard</a>
    </div>
</div>

<?php
clearOld();
require_once __DIR__ . '/../includes/admin_footer.php';
?>
