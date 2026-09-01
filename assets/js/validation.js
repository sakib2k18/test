/* ==========================================================================
   KUET BUS SERVICE  -  CLIENT-SIDE FORM VALIDATION
   --------------------------------------------------------------------------
   Every form marked with  class="js-validate"  is validated here before it
   is sent. Each field describes its own rules with data-* attributes:

       data-rule-required          field must not be empty
       data-rule-email             must be a valid email address
       data-rule-phone             Bangladeshi mobile number
       data-rule-password          min 6 chars, one letter + one digit
       data-rule-match="otherId"   must equal another field
       data-rule-min="3"           minimum length
       data-rule-max="120"         maximum length
       data-rule-number="1,120"    numeric value inside a range
       data-rule-time              HH:MM
       data-rule-file="jpg,png"    allowed file extensions
       data-rule-checked           checkbox must be ticked
       data-rule-group             at least one checkbox of the group ticked
       data-label="Full name"      friendly name used in the message

   IMPORTANT: this is only a convenience for the visitor. The very same
   rules are checked again in PHP on the server, which is what actually
   protects the database.
   ========================================================================== */
(function () {
    'use strict';

    var PATTERNS = {
        email: /^[^\s@]+@[^\s@]+\.[a-zA-Z]{2,}$/,
        phone: /^(?:\+?88)?01[3-9]\d{8}$/,
        time:  /^([01]\d|2[0-3]):[0-5]\d$/
    };

    /* ---------------------------------------------------------------------
       Error message helpers
       --------------------------------------------------------------------- */
    function labelOf(field) {
        return field.getAttribute('data-label') || field.getAttribute('name') || 'This field';
    }

    /** Find (or build) the <p class="field__error"> that belongs to a field. */
    function errorBox(field) {
        var wrap = field.closest('.field') || field.parentElement;
        if (!wrap) { return null; }

        var box = wrap.querySelector('.field__error');
        if (!box) {
            box = document.createElement('p');
            box.className = 'field__error';
            wrap.appendChild(box);
        }
        return box;
    }

    function showError(field, message) {
        var box = errorBox(field);
        if (box) {
            box.innerHTML = '<svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" ' +
                'stroke-width="2" stroke-linecap="round" aria-hidden="true">' +
                '<circle cx="12" cy="12" r="9"/><path d="M12 7v5"/><circle cx="12" cy="16" r=".7"/></svg>' +
                '<span></span>';
            box.querySelector('span').textContent = message;
            box.classList.add('is-shown');
        }
        field.classList.add('is-invalid');
        field.classList.remove('is-valid');
        field.setAttribute('aria-invalid', 'true');
    }

    function clearError(field, markValid) {
        var box = errorBox(field);
        if (box) { box.classList.remove('is-shown'); }
        field.classList.remove('is-invalid');
        field.removeAttribute('aria-invalid');
        if (markValid && field.value.trim() !== '') {
            field.classList.add('is-valid');
        } else {
            field.classList.remove('is-valid');
        }
    }

    /* ---------------------------------------------------------------------
       The rules
       --------------------------------------------------------------------- */
    function validateField(field) {
        var value = (field.value || '').trim();
        var name  = labelOf(field);
        var a     = function (attr) { return field.getAttribute(attr); };

        /* required ------------------------------------------------------- */
        if (field.hasAttribute('data-rule-required') && value === '') {
            showError(field, name + ' is required.');
            return false;
        }

        /* checkbox that must be ticked (terms and conditions) ------------- */
        if (field.hasAttribute('data-rule-checked') && !field.checked) {
            showError(field, 'Please accept ' + name.toLowerCase() + ' to continue.');
            return false;
        }

        /* at least one checkbox of a group ------------------------------- */
        if (field.hasAttribute('data-rule-group')) {
            var group = document.getElementsByName(field.name);
            var any   = Array.prototype.some.call(group, function (c) { return c.checked; });
            if (!any) {
                showError(field, 'Please select at least one ' + name.toLowerCase() + '.');
                return false;
            }
        }

        // Optional and empty -> nothing more to check
        if (value === '' && !field.hasAttribute('data-rule-required')) {
            clearError(field, false);
            return true;
        }

        /* email ---------------------------------------------------------- */
        if (field.hasAttribute('data-rule-email') && !PATTERNS.email.test(value)) {
            showError(field, 'Please enter a valid email address, e.g. name@kuet.ac.bd');
            return false;
        }

        /* phone ---------------------------------------------------------- */
        if (field.hasAttribute('data-rule-phone') && !PATTERNS.phone.test(value.replace(/[\s-]/g, ''))) {
            showError(field, 'Please enter a valid mobile number, e.g. 01712345678');
            return false;
        }

        /* password strength ---------------------------------------------- */
        if (field.hasAttribute('data-rule-password')) {
            if (value.length < 6) {
                showError(field, 'Password must be at least 6 characters long.');
                return false;
            }
            if (!/[A-Za-z]/.test(value) || !/[0-9]/.test(value)) {
                showError(field, 'Password must contain at least one letter and one number.');
                return false;
            }
        }

        /* confirm password ----------------------------------------------- */
        if (a('data-rule-match')) {
            var other = document.getElementById(a('data-rule-match'));
            if (other && value !== other.value) {
                showError(field, 'The two passwords do not match.');
                return false;
            }
        }

        /* length --------------------------------------------------------- */
        if (a('data-rule-min') && value.length < parseInt(a('data-rule-min'), 10)) {
            showError(field, name + ' must be at least ' + a('data-rule-min') + ' characters.');
            return false;
        }
        if (a('data-rule-max') && value.length > parseInt(a('data-rule-max'), 10)) {
            showError(field, name + ' must not be longer than ' + a('data-rule-max') + ' characters.');
            return false;
        }

        /* numeric range --------------------------------------------------- */
        if (a('data-rule-number')) {
            var parts = a('data-rule-number').split(',');
            var min   = parseFloat(parts[0]);
            var max   = parseFloat(parts[1]);
            var num   = parseFloat(value);
            if (isNaN(num)) {
                showError(field, name + ' must be a number.');
                return false;
            }
            if (num < min || num > max) {
                showError(field, name + ' must be between ' + min + ' and ' + max + '.');
                return false;
            }
        }

        /* time ------------------------------------------------------------ */
        if (field.hasAttribute('data-rule-time') && !PATTERNS.time.test(value)) {
            showError(field, 'Please enter a valid time in 24-hour format (HH:MM).');
            return false;
        }

        /* file ------------------------------------------------------------ */
        if (a('data-rule-file') && field.files && field.files.length) {
            var allowed = a('data-rule-file').split(',');
            var file    = field.files[0];
            var ext     = file.name.split('.').pop().toLowerCase();

            if (allowed.indexOf(ext) === -1) {
                showError(field, 'Allowed image types: ' + allowed.join(', ') + '.');
                return false;
            }
            var maxMb = parseFloat(a('data-rule-filesize') || '2');
            if (file.size > maxMb * 1024 * 1024) {
                showError(field, 'The image must be smaller than ' + maxMb + ' MB.');
                return false;
            }
        }

        clearError(field, true);
        return true;
    }

    /* ---------------------------------------------------------------------
       Wire the forms up
       --------------------------------------------------------------------- */
    function initForm(form) {
        var fields = Array.prototype.slice.call(
            form.querySelectorAll('[data-rule-required], [data-rule-email], [data-rule-phone], ' +
                '[data-rule-password], [data-rule-match], [data-rule-min], [data-rule-max], ' +
                '[data-rule-number], [data-rule-time], [data-rule-file], [data-rule-checked], ' +
                '[data-rule-group]')
        );

        fields.forEach(function (field) {
            // Validate as soon as the visitor leaves the field ...
            field.addEventListener('blur', function () { validateField(field); });
            // ... and clear the message again while they are fixing it.
            field.addEventListener('input', function () {
                if (field.classList.contains('is-invalid')) { validateField(field); }
            });
            if (field.type === 'checkbox' || field.tagName === 'SELECT') {
                field.addEventListener('change', function () { validateField(field); });
            }
        });

        form.setAttribute('novalidate', 'novalidate');

        form.addEventListener('submit', function (ev) {
            var firstBad = null;

            fields.forEach(function (field) {
                if (!validateField(field) && !firstBad) { firstBad = field; }
            });

            if (firstBad) {
                ev.preventDefault();
                ev.stopPropagation();
                firstBad.focus();
                firstBad.scrollIntoView({ behavior: 'smooth', block: 'center' });
                if (window.showToast) {
                    window.showToast('Please correct the highlighted fields.', 'error');
                }
                // Release any loading state that main.js may have applied.
                var btn = form.querySelector('[type="submit"]');
                if (btn) { btn.classList.remove('is-loading'); }
            }
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        Array.prototype.slice.call(document.querySelectorAll('form.js-validate')).forEach(initForm);
    });
})();
