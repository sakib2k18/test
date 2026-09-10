<?php
/**
 * LOG IN
 * One form for both roles. The role stored in the database decides where the
 * visitor lands: the administrator goes to the admin panel, everybody else
 * goes back to the public site.
 */
require_once __DIR__ . '/includes/init.php';

redirectIfLoggedIn();

$pageTitle       = 'Log in';
$pageDescription = 'Log in to the ' . UNIVERSITY_SHORT . ' Bus Service portal.';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    requireCsrf('login.php');

    $email    = post('email');
    $password = $_POST['password'] ?? '';

    /* ---------------- SERVER-SIDE VALIDATION ---------------- */
    $errors = [];

    if (isBlank($email)) {
        $errors[] = 'Please enter your email address.';
    } elseif (!isValidEmail($email)) {
        $errors[] = 'Please enter a valid email address.';
    }
    if ($password === '') {
        $errors[] = 'Please enter your password.';
    }

    if ($errors) {
        keepErrors($errors);
        keepOld(['email' => $email]);
        redirect('login.php');
    }

    /* ---------------- CHECK THE CREDENTIALS ---------------- */
    $user = fetchOne('SELECT * FROM users WHERE email = ?', [$email]);

    /* The same message is shown for a wrong email and a wrong password, so
       the form cannot be used to find out which addresses are registered. */
    if (!$user || !password_verify($password, $user['password'])) {
        keepErrors(['The email address or password is incorrect.']);
        keepOld(['email' => $email]);
        setFlash('error', 'Login failed.');
        redirect('login.php');
    }

    loginUser($user);
    logActivity('Login', $user['full_name'] . ' logged in (' . $user['role'] . ').');
    setFlash('success', 'Welcome back, ' . explode(' ', $user['full_name'])[0] . '!');

    /* Return the visitor to the page they originally asked for. */
    $target = $_SESSION['redirect_after_login'] ?? null;
    unset($_SESSION['redirect_after_login']);

    if ($target && is_string($target)) {
        header('Location: ' . $target);
        exit;
    }

    redirect($user['role'] === 'admin' ? 'admin/index.php' : 'index.php');
}

$errors = takeErrors();

require_once __DIR__ . '/includes/header.php';
?>

<section class="section">
    <div class="container" style="max-width:520px;">

        <div class="section-head section-head--center">
            <span class="eyebrow">Welcome back</span>
            <h1 style="font-size:clamp(1.7rem,1.3rem+1.6vw,2.3rem);">Log in to your account</h1>
            <p>Use the email address and password you registered with.</p>
        </div>

        <div class="card">
            <div class="card__body" style="padding:30px;">

                <?= errorSummary($errors) ?>

                <form class="form js-validate" method="post" action="<?= e(url('login.php')) ?>">
                    <?= csrfField() ?>

                    <div class="field">
                        <label class="field__label" for="email">
                            Email address <span class="req" aria-hidden="true">*</span>
                        </label>
                        <input class="input" type="email" id="email" name="email"
                               value="<?= e(old('email')) ?>"
                               placeholder="name@kuet.ac.bd"
                               data-label="Email address" data-rule-required data-rule-email
                               autocomplete="off" data-no-autofill required>
                        <p class="field__error"></p>
                    </div>

                    <div class="field">
                        <label class="field__label" for="password">
                            Password <span class="req" aria-hidden="true">*</span>
                        </label>
                        <div class="input-group">
                            <input class="input" type="password" id="password" name="password"
                                   data-label="Password" data-rule-required
                                   autocomplete="new-password" data-no-autofill required>
                            <button class="input-toggle" type="button"
                                    data-toggle-password="password" aria-label="Show password">
                                <?= icon('eye') ?>
                            </button>
                        </div>
                        <p class="field__error"></p>
                    </div>

                    <div class="form-actions">
                        <button class="btn btn--primary btn--lg btn--block" type="submit">
                            <?= icon('login') ?> Log in
                        </button>
                    </div>

                    <p class="text-muted text-center" style="font-size:.9rem;">
                        New here? <a href="<?= e(url('register.php')) ?>">Create an account</a>
                    </p>
                </form>
            </div>
        </div>

        <div class="alert alert--info" style="margin-top:22px;">
            <?= icon('info') ?>
            <div>
                <strong>Demonstration accounts</strong>
                <ul>
                    <li>Administrator: <code>admin@kuet.ac.bd</code> / <code>admin@123</code></li>
                    <li>Student: <code>rakib@stud.kuet.ac.bd</code> / <code>user@123</code></li>
                </ul>
            </div>
        </div>

    </div>
</section>

<?php
clearOld();
require_once __DIR__ . '/includes/footer.php';
?>
