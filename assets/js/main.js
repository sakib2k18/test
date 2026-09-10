/* ==========================================================================
   KUET BUS SERVICE  -  MAIN JAVASCRIPT
   --------------------------------------------------------------------------
   Handles every interactive piece of the public site:
     1.  Mobile navigation drawer
     2.  Sticky navbar shadow
     3.  Toast notifications
     4.  Confirmation modal for destructive actions
     5.  Reveal-on-scroll animation
     6.  Password visibility toggle
     7.  Character counters
     8.  Live image preview for uploads
     9.  Instant client-side search / filter
     10. Auto-submitting filter controls + button loading states
     11. Autofill guard for the login form
   ========================================================================== */
(function () {
    'use strict';

    /* Tiny helpers ------------------------------------------------------- */
    var $  = function (sel, root) { return (root || document).querySelector(sel); };
    var $$ = function (sel, root) {
        return Array.prototype.slice.call((root || document).querySelectorAll(sel));
    };

    /* =====================================================================
       1. MOBILE NAVIGATION DRAWER
       ===================================================================== */
    function initDrawer() {
        var toggle   = $('#navToggle');
        var drawer   = $('#navDrawer');
        var backdrop = $('#navBackdrop');
        var closeBtn = $('#navClose');

        if (!toggle || !drawer || !backdrop) { return; }

        /* The backdrop is hidden with CSS visibility (not display:none), so a
           single class toggle is enough - no extra frame is needed. */
        function open() {
            drawer.classList.add('is-open');
            backdrop.classList.add('is-open');
            drawer.setAttribute('aria-hidden', 'false');
            toggle.setAttribute('aria-expanded', 'true');
            document.body.style.overflow = 'hidden';
            var firstLink = drawer.querySelector('a, button');
            if (firstLink) { firstLink.focus(); }
        }

        function close() {
            drawer.classList.remove('is-open');
            backdrop.classList.remove('is-open');
            drawer.setAttribute('aria-hidden', 'true');
            toggle.setAttribute('aria-expanded', 'false');
            document.body.style.overflow = '';
        }

        toggle.addEventListener('click', open);
        backdrop.addEventListener('click', close);
        if (closeBtn) { closeBtn.addEventListener('click', close); }

        document.addEventListener('keydown', function (ev) {
            if (ev.key === 'Escape' && drawer.classList.contains('is-open')) {
                close();
                toggle.focus();
            }
        });

        // Close the drawer again when the screen becomes wide enough.
        window.addEventListener('resize', function () {
            if (window.innerWidth > 900 && drawer.classList.contains('is-open')) { close(); }
        });
    }

    /* =====================================================================
       2. STICKY NAVBAR SHADOW
       ===================================================================== */
    function initStickyNav() {
        var nav = $('#navbar');
        if (!nav) { return; }

        var onScroll = function () {
            nav.classList.toggle('is-stuck', window.scrollY > 8);
        };
        onScroll();
        window.addEventListener('scroll', onScroll, { passive: true });
    }

    /* =====================================================================
       3. TOAST NOTIFICATIONS
       ===================================================================== */
    var ICONS = {
        success: '<path d="M20 6L9 17l-5-5"/>',
        error:   '<path d="M12 3l9.5 17H2.5z"/><path d="M12 9.5v4"/><circle cx="12" cy="17" r=".7"/>',
        warning: '<path d="M12 3l9.5 17H2.5z"/><path d="M12 9.5v4"/><circle cx="12" cy="17" r=".7"/>',
        info:    '<circle cx="12" cy="12" r="9"/><path d="M12 11v5"/><circle cx="12" cy="8" r=".7"/>'
    };

    function svg(paths) {
        return '<svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" ' +
               'stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' +
               paths + '</svg>';
    }

    function dismissToast(toast) {
        if (!toast || toast.classList.contains('is-leaving')) { return; }
        toast.classList.add('is-leaving');
        window.setTimeout(function () { toast.remove(); }, 260);
    }

    function watchToast(toast) {
        var closeBtn = toast.querySelector('.toast__close');
        if (closeBtn) {
            closeBtn.addEventListener('click', function () { dismissToast(toast); });
        }
        window.setTimeout(function () { dismissToast(toast); }, 5200);
    }

    /** Public helper so any script can raise a toast: showToast('Saved', 'success') */
    window.showToast = function (message, type) {
        var stack = $('#toastStack');
        if (!stack) { return; }
        type = ICONS[type] ? type : 'info';

        var toast = document.createElement('div');
        toast.className = 'toast toast--' + type;
        toast.innerHTML = svg(ICONS[type]) + '<span></span>' +
            '<button class="toast__close" type="button" aria-label="Dismiss message">' +
            svg('<path d="M18 6L6 18M6 6l12 12"/>') + '</button>';
        toast.querySelector('span').textContent = message;

        stack.appendChild(toast);
        watchToast(toast);
    };

    function initToasts() {
        $$('[data-toast]').forEach(watchToast);
    }

    /* =====================================================================
       4. CONFIRMATION MODAL
       ---------------------------------------------------------------------
       Any form or link carrying data-confirm="message" is intercepted and
       only continues once the user confirms.
       ===================================================================== */
    function initConfirm() {
        var modal = $('#confirmModal');
        if (!modal) { return; }

        var textEl   = $('#confirmText');
        var okBtn    = modal.querySelector('[data-confirm-ok]');
        var cancelEl = modal.querySelector('[data-confirm-cancel]');
        var pending  = null;
        var lastFocus = null;

        function openModal(message, action) {
            textEl.textContent = message || 'This action cannot be undone.';
            pending   = action;
            lastFocus = document.activeElement;
            modal.hidden = false;
            modal.classList.add('is-open');
            document.body.style.overflow = 'hidden';
            okBtn.focus();
        }

        function closeModal() {
            modal.classList.remove('is-open');
            modal.hidden = true;
            pending = null;
            document.body.style.overflow = '';
            if (lastFocus) { lastFocus.focus(); }
        }

        okBtn.addEventListener('click', function () {
            var action = pending;
            closeModal();
            if (typeof action === 'function') { action(); }
        });

        cancelEl.addEventListener('click', closeModal);
        modal.addEventListener('click', function (ev) {
            if (ev.target === modal) { closeModal(); }
        });
        document.addEventListener('keydown', function (ev) {
            if (ev.key === 'Escape' && modal.classList.contains('is-open')) { closeModal(); }
        });

        // Forms (used by every delete button in the admin panel)
        document.addEventListener('submit', function (ev) {
            var form = ev.target;
            if (!form.hasAttribute || !form.hasAttribute('data-confirm')) { return; }
            if (form.dataset.confirmed === 'yes') { return; }
            ev.preventDefault();
            openModal(form.getAttribute('data-confirm'), function () {
                form.dataset.confirmed = 'yes';
                form.submit();
            });
        });

        // Links
        document.addEventListener('click', function (ev) {
            var link = ev.target.closest ? ev.target.closest('a[data-confirm]') : null;
            if (!link) { return; }
            ev.preventDefault();
            openModal(link.getAttribute('data-confirm'), function () {
                window.location.href = link.href;
            });
        });
    }

    /* =====================================================================
       5. REVEAL ON SCROLL
       ===================================================================== */
    function initReveal() {
        var items = $$('.reveal');
        if (!items.length) { return; }

        items.forEach(function (el, i) {
            el.style.transitionDelay = Math.min(i % 6, 5) * 60 + 'ms';
        });

        var lastRun = 0;

        /* Reveal everything that has reached the viewport. A plain scroll
           check is used (instead of IntersectionObserver) because a large
           jump - pressing End, or following an anchor - can skip observer
           callbacks and leave a whole section invisible for good. */
        function sweep() {
            lastRun = Date.now();
            var limit = window.innerHeight - 40;

            items = items.filter(function (el) {
                if (el.getBoundingClientRect().top < limit) {
                    el.classList.add('is-visible');
                    return false;           // done with this one
                }
                return true;
            });

            if (!items.length) {
                window.removeEventListener('scroll', request);
                window.removeEventListener('resize', request);
            }
        }

        /* Cheap time based throttle - deliberately not requestAnimationFrame,
           which is paused in background tabs and would leave content hidden. */
        function request() {
            if (Date.now() - lastRun > 60) {
                sweep();
            }
        }

        window.addEventListener('scroll', request, { passive: true });
        window.addEventListener('resize', request);
        sweep();
    }

    /* =====================================================================
       6. PASSWORD VISIBILITY TOGGLE
       ===================================================================== */
    function initPasswordToggles() {
        $$('[data-toggle-password]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var input = document.getElementById(btn.getAttribute('data-toggle-password'));
                if (!input) { return; }
                var show = input.type === 'password';
                input.type = show ? 'text' : 'password';
                btn.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
                btn.innerHTML = show
                    ? svg('<path d="M3 3l18 18"/><path d="M10.6 6.1A9.7 9.7 0 0 1 12 6c6 0 9.5 6 9.5 6a17 17 0 0 1-3.2 3.9"/><path d="M6.3 8.1A16.7 16.7 0 0 0 2.5 12S6 18 12 18a9.5 9.5 0 0 0 3.5-.7"/>')
                    : svg('<path d="M2.5 12S6 5.5 12 5.5 21.5 12 21.5 12 18 18.5 12 18.5 2.5 12 2.5 12z"/><circle cx="12" cy="12" r="3"/>');
            });
        });
    }

    /* =====================================================================
       7. CHARACTER COUNTERS
       ===================================================================== */
    function initCounters() {
        $$('[data-counter]').forEach(function (field) {
            var output = document.getElementById(field.getAttribute('data-counter'));
            if (!output) { return; }
            var max = parseInt(field.getAttribute('maxlength') || field.getAttribute('data-max') || '0', 10);

            var update = function () {
                var len = field.value.length;
                output.textContent = max ? (len + ' / ' + max + ' characters') : (len + ' characters');
                output.classList.toggle('is-over', max > 0 && len > max);
            };
            field.addEventListener('input', update);
            update();
        });
    }

    /* =====================================================================
       8. IMAGE PREVIEW
       ===================================================================== */
    function initImagePreview() {
        $$('[data-preview]').forEach(function (input) {
            var box = document.getElementById(input.getAttribute('data-preview'));
            if (!box) { return; }
            var img = box.querySelector('img');

            input.addEventListener('change', function () {
                var file = input.files && input.files[0];
                if (!file) { box.classList.remove('is-shown'); return; }

                if (!/^image\/(jpeg|png|webp)$/.test(file.type)) {
                    window.showToast('Please choose a JPG, PNG or WEBP image.', 'error');
                    input.value = '';
                    box.classList.remove('is-shown');
                    return;
                }
                if (file.size > 2 * 1024 * 1024) {
                    window.showToast('The image must be smaller than 2 MB.', 'error');
                    input.value = '';
                    box.classList.remove('is-shown');
                    return;
                }

                var reader = new FileReader();
                reader.onload = function (ev) {
                    img.src = ev.target.result;
                    box.classList.add('is-shown');
                };
                reader.readAsDataURL(file);
            });
        });
    }

    /* =====================================================================
       9. INSTANT CLIENT-SIDE SEARCH
       ---------------------------------------------------------------------
       Narrows a list that is already on the page while the visitor types.
       The full database search still runs on the server when the form is
       submitted, so the feature also works without JavaScript.
       ===================================================================== */
    function initLiveFilter() {
        $$('[data-live-search]').forEach(function (input) {
            var scope = document.querySelector(input.getAttribute('data-live-search'));
            if (!scope) { return; }

            var emptyBox = document.querySelector(input.getAttribute('data-live-empty') || '#liveEmpty');

            input.addEventListener('input', function () {
                var term  = input.value.trim().toLowerCase();
                var shown = 0;

                $$('[data-search-text]', scope).forEach(function (item) {
                    var match = !term || item.getAttribute('data-search-text').indexOf(term) !== -1;
                    item.style.display = match ? '' : 'none';
                    if (match) { shown++; }
                });

                if (emptyBox) { emptyBox.hidden = shown !== 0; }
            });
        });
    }

    /* =====================================================================
       10. AUTO-SUBMIT FILTERS & BUTTON LOADING STATES
       ===================================================================== */
    function initFormBehaviour() {
        // Selects that submit their form as soon as they change
        $$('[data-auto-submit]').forEach(function (control) {
            control.addEventListener('change', function () {
                if (control.form) { control.form.submit(); }
            });
        });

        // Show a spinner on the submit button while the page is posting
        document.addEventListener('submit', function (ev) {
            var form = ev.target;
            if (ev.defaultPrevented || !form.querySelector) { return; }
            if (form.hasAttribute('data-confirm') && form.dataset.confirmed !== 'yes') { return; }

            var button = form.querySelector('[type="submit"]');
            if (button && !button.classList.contains('is-loading')) {
                window.setTimeout(function () { button.classList.add('is-loading'); }, 40);
            }
        });
    }

    /* =====================================================================
       11. AUTOFILL GUARD FOR THE LOGIN FORM
       ---------------------------------------------------------------------
       Fields marked data-no-autofill are switched to readonly while the
       page loads, because browsers skip readonly fields when they auto-fill
       saved credentials. The field becomes editable again as soon as the
       visitor focuses it, so typing and password managers still work.
       ===================================================================== */
    function initAutofillGuard() {
        $$('[data-no-autofill]').forEach(function (field) {
            field.setAttribute('readonly', 'readonly');
            field.setAttribute('aria-readonly', 'false');
            var unlock = function () {
                field.removeAttribute('readonly');
            };
            field.addEventListener('focus', unlock);
            field.addEventListener('pointerdown', unlock);
        });
    }

    /* =====================================================================
       BOOT
       ===================================================================== */
    document.addEventListener('DOMContentLoaded', function () {
        initDrawer();
        initStickyNav();
        initToasts();
        initConfirm();
        initReveal();
        initPasswordToggles();
        initCounters();
        initImagePreview();
        initLiveFilter();
        initFormBehaviour();
        initAutofillGuard();
    });
})();
