<?php
/**
 * SIGN UP
 * Creates a normal user account. The "admin" role is NOT offered anywhere on
 * this page and is never read from the request - the role is hard-set to
 * 'user' in the INSERT below, so the single admin account cannot be copied.
 */
require_once __DIR__ . '/includes/init.php';

redirectIfLoggedIn();

$pageTitle       = 'Create an account';
$pageDescription = 'Sign up for the ' . UNIVERSITY_SHORT . ' Bus Service portal.';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    requireCsrf('register.php');

    $fullName   = post('full_name');
    $email      = post('email');
    $studentId  = post('student_id');
    $department = post('department');
    $phone      = post('phone');
    $password   = $_POST['password']         ?? '';
    $confirm    = $_POST['confirm_password'] ?? '';
    $terms      = isset($_POST['terms']);

    /* ---------------- SERVER-SIDE VALIDATION ---------------- */
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
    } elseif (mb_strlen($email) > 150) {
        $errors[] = 'The email address is too long.';
    } elseif (existsInTable('users', 'email', $email)) {
        // Duplicate email prevention (the column also has a UNIQUE index).
        $errors[] = 'An account with this email address already exists.';
    }

    if (!isBlank($studentId) && !preg_match('/^[A-Za-z0-9-]{4,30}$/', $studentId)) {
        $errors[] = 'The student / employee ID may only contain letters, numbers and dashes.';
    }

    if (!isBlank($department) && mb_strlen($department) > 100) {
        $errors[] = 'The department name is too long.';
    }

    if (!isBlank($phone) && !isValidPhone($phone)) {
        $errors[] = 'Please enter a valid mobile number, for example 01712345678.';
    }

    if ($password === '') {
        $errors[] = 'A password is required.';
    } elseif (!isStrongPassword($password)) {
        $errors[] = 'The password must be at least 6 characters long and contain a letter and a number.';
    }

    if ($confirm === '') {
        $errors[] = 'Please confirm your password.';
    } elseif ($password !== $confirm) {
        $errors[] = 'The two passwords do not match.';
    }

    if (!$terms) {
        $errors[] = 'You must accept the terms of use to create an account.';
    }

    if ($errors) {
        keepErrors($errors);
        keepOld($_POST);
        setFlash('error', 'Your account could not be created. Please check the form.');
        redirect('register.php');
    }

    /* ---------------- CREATE THE ACCOUNT ---------------- */
    try {
        q(
            'INSERT INTO users (full_name, email, password, student_id, department, phone, role)
             VALUES (?, ?, ?, ?, ?, ?, ?)',
            [
                $fullName,
                $email,
                password_hash($password, PASSWORD_DEFAULT),   // never stored in plain text
                $studentId !== ''  ? $studentId  : null,
                $department !== '' ? $department : null,
                $phone !== ''      ? $phone      : null,
                'user',                                        // role is fixed - never from the form
            ]
        );

        setFlash('success', 'Your account has been created. Please log in.');
        redirect('login.php');

    } catch (PDOException $ex) {
        // 23000 = integrity constraint violation (the UNIQUE email index)
        if ($ex->getCode() === '23000') {
            keepErrors(['An account with this email address already exists.']);
        } else {
            error_log('[REGISTER] ' . $ex->getMessage());
            keepErrors(['The account could not be created because of a server error.']);
        }
        keepOld($_POST);
        setFlash('error', 'Registration failed.');
        redirect('register.php');
    }
}

$errors = takeErrors();

require_once __DIR__ . '/includes/header.php';
?>

<section class="section">
    <div class="container" style="max-width:760px;">

        <div class="section-head section-head--center">
            <span class="eyebrow">Sign up</span>
            <h1 style="font-size:clamp(1.7rem,1.3rem+1.6vw,2.3rem);">Create your account</h1>
            <p>
                An account lets you stay signed in while you browse the fleet, routes and
                timetable of the <?= e(UNIVERSITY_SHORT) ?> Bus Service.
            </p>
        </div>

        <div class="card">
            <div class="card__body" style="padding:30px;">

                <?= errorSummary($errors) ?>

                <form class="form js-validate" method="post" action="<?= e(url('register.php')) ?>">
                    <?= csrfField() ?>

                    <div class="form-row">
                        <div class="field">
                            <label class="field__label" for="full_name">
                                Full name <span class="req" aria-hidden="true">*</span>
                            </label>
                            <input class="input" type="text" id="full_name" name="full_name"
                                   value="<?= e(old('full_name')) ?>"
                                   data-label="Full name" data-rule-required
                                   data-rule-min="3" data-rule-max="100"
                                   autocomplete="name" required>
                            <p class="field__error"></p>
                        </div>

                        <div class="field">
                            <label class="field__label" for="email">
                                Email address <span class="req" aria-hidden="true">*</span>
                            </label>
                            <input class="input" type="email" id="email" name="email"
                                   value="<?= e(old('email')) ?>"
                                   placeholder="name@stud.kuet.ac.bd"
                                   data-label="Email address" data-rule-required data-rule-email
                                   autocomplete="email" required>
                            <p class="field__error"></p>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="field">
                            <label class="field__label" for="student_id">Student / Employee ID</label>
                            <input class="input" type="text" id="student_id" name="student_id"
                                   value="<?= e(old('student_id')) ?>" placeholder="e.g. 1907045"
                                   data-label="Student ID" data-rule-min="4" data-rule-max="30">
                            <p class="field__hint">Optional - helpful if you travel on a student bus.</p>
                            <p class="field__error"></p>
                        </div>

                        <div class="field">
                            <label class="field__label" for="department">Department</label>
                            <input class="input" type="text" id="department" name="department"
                                   value="<?= e(old('department')) ?>"
                                   placeholder="e.g. Computer Science and Engineering"
                                   data-label="Department" data-rule-max="100">
                            <p class="field__error"></p>
                        </div>
                    </div>

                    <div class="field">
                        <label class="field__label" for="phone">Mobile number</label>
                        <input class="input" type="tel" id="phone" name="phone"
                               value="<?= e(old('phone')) ?>" placeholder="01712345678"
                               data-label="Mobile number" data-rule-phone
                               autocomplete="tel">
                        <p class="field__hint">Optional - used only if the office needs to reach you.</p>
                        <p class="field__error"></p>
                    </div>

                    <div class="form-row">
                        <div class="field">
                            <label class="field__label" for="password">
                                Password <span class="req" aria-hidden="true">*</span>
                            </label>
                            <div class="input-group">
                                <input class="input" type="password" id="password" name="password"
                                       data-label="Password" data-rule-required data-rule-password
                                       autocomplete="new-password" required>
                                <button class="input-toggle" type="button"
                                        data-toggle-password="password" aria-label="Show password">
                                    <?= icon('eye') ?>
                                </button>
                            </div>
                            <p class="field__hint">At least 6 characters, with one letter and one number.</p>
                            <p class="field__error"></p>
                        </div>

                        <div class="field">
                            <label class="field__label" for="confirm_password">
                                Confirm password <span class="req" aria-hidden="true">*</span>
                            </label>
                            <div class="input-group">
                                <input class="input" type="password" id="confirm_password" name="confirm_password"
                                       data-label="Confirm password" data-rule-required
                                       data-rule-match="password"
                                       autocomplete="new-password" required>
                                <button class="input-toggle" type="button"
                                        data-toggle-password="confirm_password" aria-label="Show password">
                                    <?= icon('eye') ?>
                                </button>
                            </div>
                            <p class="field__error"></p>
                        </div>
                    </div>

                    <div class="field">
                        <label class="check">
                            <input type="checkbox" name="terms" value="1" id="terms"
                                   data-label="the terms of use" data-rule-checked
                                   <?= old('terms') ? 'checked' : '' ?>>
                            <span>
                                I accept the terms of use of the <?= e(UNIVERSITY_SHORT) ?> Bus Service portal
                                and agree that my details will be used only for transport related communication.
                            </span>
                        </label>
                        <p class="field__error"></p>
                    </div>

                    <div class="form-actions">
                        <button class="btn btn--primary btn--lg" type="submit">
                            <?= icon('user') ?> Create account
                        </button>
                    </div>

                    <p class="text-muted text-center" style="font-size:.9rem;">
                        Already registered? <a href="<?= e(url('login.php')) ?>">Log in instead</a>
                    </p>
                </form>
            </div>
        </div>

    </div>
</section>

<?php
clearOld();
require_once __DIR__ . '/includes/footer.php';
?>
