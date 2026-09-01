<?php
/**
 * PUBLIC PAGE - CONTACT
 * The form really works: valid messages are stored in the contact_messages
 * table and the administrator reads them in the admin panel.
 */
require_once __DIR__ . '/includes/init.php';

$pageTitle       = 'Contact';
$pageDescription = 'Contact the ' . UNIVERSITY_SHORT . ' Transport Office.';

/* ---------------------------------------------------------------------
   Handle the submitted form (Post / Redirect / Get pattern)
   --------------------------------------------------------------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    requireCsrf('contact.php');

    $name    = post('name');
    $email   = post('email');
    $subject = post('subject');
    $message = post('message');

    /* ---------------- SERVER-SIDE VALIDATION ---------------- */
    $errors = [];

    if (isBlank($name)) {
        $errors[] = 'Your name is required.';
    } elseif (mb_strlen($name) < 3 || mb_strlen($name) > 100) {
        $errors[] = 'Your name must be between 3 and 100 characters.';
    }

    if (isBlank($email)) {
        $errors[] = 'Your email address is required.';
    } elseif (!isValidEmail($email)) {
        $errors[] = 'Please enter a valid email address.';
    } elseif (mb_strlen($email) > 150) {
        $errors[] = 'The email address is too long.';
    }

    if (isBlank($subject)) {
        $errors[] = 'A subject is required.';
    } elseif (mb_strlen($subject) < 4 || mb_strlen($subject) > 150) {
        $errors[] = 'The subject must be between 4 and 150 characters.';
    }

    if (isBlank($message)) {
        $errors[] = 'Please write your message.';
    } elseif (mb_strlen($message) < 10) {
        $errors[] = 'Your message must be at least 10 characters long.';
    } elseif (mb_strlen($message) > 2000) {
        $errors[] = 'Your message must not be longer than 2000 characters.';
    }

    if ($errors) {
        keepErrors($errors);
        keepOld($_POST);
        setFlash('error', 'Your message could not be sent. Please check the form.');
        redirect('contact.php');
    }

    /* ---------------- STORE IT ---------------- */
    try {
        q(
            'INSERT INTO contact_messages (name, email, subject, message) VALUES (?, ?, ?, ?)',
            [$name, $email, $subject, $message]
        );
        setFlash('success', 'Thank you! Your message has been sent to the Transport Office.');
    } catch (Throwable $ex) {
        error_log('[CONTACT] ' . $ex->getMessage());
        keepOld($_POST);
        setFlash('error', 'Sorry, your message could not be saved. Please try again later.');
    }

    redirect('contact.php');
}

$errors = takeErrors();

require_once __DIR__ . '/includes/header.php';
?>

<section class="page-header">
    <div class="container">
        <ul class="breadcrumb">
            <li><a href="<?= e(url('index.php')) ?>">Home</a></li>
            <li><?= icon('chevron') ?></li>
            <li aria-current="page">Contact</li>
        </ul>
        <h1>Contact the Transport Office</h1>
        <p>
            Questions about a route, a lost item or a request for an extra trip?
            Write to us and we will get back to you.
        </p>
    </div>
</section>

<section class="section section--tight">
    <div class="container">
        <div class="grid detail-layout" style="grid-template-columns:1fr 1fr;gap:32px;align-items:start;">

            <!-- ---------------- Office information ---------------- -->
            <div class="stack" style="gap:16px;">
                <h2 style="font-size:1.25rem;margin-bottom:2px;">Transport Office</h2>
                <p class="text-muted" style="margin-bottom:8px;">
                    You can also visit us in person during office hours.
                </p>

                <div class="info-card">
                    <span class="info-card__icon"><?= icon('pin') ?></span>
                    <span>
                        <b>Office address</b>
                        <span><?= e(OFFICE_ADDRESS) ?></span>
                    </span>
                </div>

                <div class="info-card">
                    <span class="info-card__icon"><?= icon('phone') ?></span>
                    <span>
                        <b>Phone</b>
                        <span><a href="tel:<?= e(preg_replace('/[^0-9+]/', '', OFFICE_PHONE)) ?>"><?= e(OFFICE_PHONE) ?></a></span>
                    </span>
                </div>

                <div class="info-card">
                    <span class="info-card__icon"><?= icon('mail') ?></span>
                    <span>
                        <b>Email</b>
                        <span><a href="mailto:<?= e(OFFICE_EMAIL) ?>"><?= e(OFFICE_EMAIL) ?></a></span>
                    </span>
                </div>

                <div class="info-card">
                    <span class="info-card__icon"><?= icon('clock') ?></span>
                    <span>
                        <b>Office hours</b>
                        <span><?= e(OFFICE_HOURS) ?></span>
                    </span>
                </div>

                <div class="card" style="margin-top:8px;">
                    <div class="card__body">
                        <h3 style="font-size:1rem;">Before you write</h3>
                        <ul class="meta-list" style="margin-top:12px;">
                            <li><?= icon('check') ?> Check the <a href="<?= e(url('schedules.php')) ?>">timetable</a> for departure times.</li>
                            <li><?= icon('check') ?> Read the <a href="<?= e(url('announcements.php')) ?>">notice board</a> for recent changes.</li>
                            <li><?= icon('check') ?> Find the driver's number on the <a href="<?= e(url('buses.php')) ?>">bus page</a>.</li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- ---------------- Contact form ---------------- -->
            <div class="card">
                <div class="card__body" style="padding:28px;">
                    <h2 style="font-size:1.25rem;">Send a message</h2>
                    <p class="text-muted" style="font-size:.9rem;margin:6px 0 22px;">
                        All fields are required. Your message is delivered to the Transport Office.
                    </p>

                    <?= errorSummary($errors) ?>

                    <form class="form js-validate" method="post" action="<?= e(url('contact.php')) ?>">
                        <?= csrfField() ?>

                        <div class="form-row">
                            <div class="field">
                                <label class="field__label" for="name">
                                    Full name <span class="req" aria-hidden="true">*</span>
                                </label>
                                <input class="input" type="text" id="name" name="name"
                                       value="<?= e(old('name')) ?>"
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
                                       data-label="Email address" data-rule-required data-rule-email
                                       autocomplete="email" required>
                                <p class="field__error"></p>
                            </div>
                        </div>

                        <div class="field">
                            <label class="field__label" for="subject">
                                Subject <span class="req" aria-hidden="true">*</span>
                            </label>
                            <input class="input" type="text" id="subject" name="subject"
                                   value="<?= e(old('subject')) ?>"
                                   placeholder="e.g. Request for an extra evening trip"
                                   data-label="Subject" data-rule-required
                                   data-rule-min="4" data-rule-max="150" required>
                            <p class="field__error"></p>
                        </div>

                        <div class="field">
                            <label class="field__label" for="message">
                                Message <span class="req" aria-hidden="true">*</span>
                            </label>
                            <textarea class="textarea" id="message" name="message"
                                      maxlength="2000" data-counter="messageCount"
                                      data-label="Message" data-rule-required
                                      data-rule-min="10" data-rule-max="2000"
                                      placeholder="Describe your question in a few sentences..."
                                      required><?= e(old('message')) ?></textarea>
                            <p class="char-count" id="messageCount">0 / 2000 characters</p>
                            <p class="field__error"></p>
                        </div>

                        <div class="form-actions">
                            <button class="btn btn--primary" type="submit">
                                <?= icon('mail') ?> Send message
                            </button>
                            <button class="btn btn--ghost" type="reset">Clear form</button>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </div>
</section>

<?php
clearOld();
require_once __DIR__ . '/includes/footer.php';
?>
