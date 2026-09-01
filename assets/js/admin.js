/* ==========================================================================
   KUET BUS SERVICE  -  ADMIN PANEL JAVASCRIPT
   --------------------------------------------------------------------------
   Adds the few behaviours that only the dashboard needs:
     1. Off-canvas sidebar on small screens
     2. Animated dashboard bar chart
     3. "Select all days" helper on the schedule form
     4. Instant row filtering inside admin tables
   main.js already provides toasts, the confirm modal, image previews,
   character counters and the password toggles.
   ========================================================================== */
(function () {
    'use strict';

    var $  = function (s, r) { return (r || document).querySelector(s); };
    var $$ = function (s, r) {
        return Array.prototype.slice.call((r || document).querySelectorAll(s));
    };

    /* =====================================================================
       1. SIDEBAR DRAWER
       ===================================================================== */
    function initSidebar() {
        var side     = $('#adminSide');
        var burger   = $('#adminBurger');
        var backdrop = $('#adminBackdrop');
        var closeBtn = $('#adminSideClose');

        if (!side || !burger || !backdrop) { return; }

        /* The backdrop is hidden with CSS visibility, so one class does it all. */
        function open() {
            side.classList.add('is-open');
            backdrop.classList.add('is-open');
            burger.setAttribute('aria-expanded', 'true');
            document.body.style.overflow = 'hidden';
        }

        function close() {
            side.classList.remove('is-open');
            backdrop.classList.remove('is-open');
            burger.setAttribute('aria-expanded', 'false');
            document.body.style.overflow = '';
        }

        burger.addEventListener('click', open);
        backdrop.addEventListener('click', close);
        if (closeBtn) { closeBtn.addEventListener('click', close); }

        document.addEventListener('keydown', function (ev) {
            if (ev.key === 'Escape' && side.classList.contains('is-open')) { close(); }
        });
        window.addEventListener('resize', function () {
            if (window.innerWidth > 992 && side.classList.contains('is-open')) { close(); }
        });
    }

    /* =====================================================================
       2. DASHBOARD BAR CHART
       ---------------------------------------------------------------------
       The bars carry their value in data-value; the height is calculated
       here so the chart animates upwards when the page opens.
       ===================================================================== */
    function initChart() {
        var bars = $$('.chart__bar');
        if (!bars.length) { return; }

        var max = bars.reduce(function (m, bar) {
            return Math.max(m, parseFloat(bar.getAttribute('data-value')) || 0);
        }, 0);

        bars.forEach(function (bar) {
            var value  = parseFloat(bar.getAttribute('data-value')) || 0;
            var height = max > 0 ? Math.max(6, Math.round((value / max) * 100)) : 6;
            bar.style.height = '0%';
            window.setTimeout(function () { bar.style.height = height + '%'; }, 60);

            if (max > 0 && value === max) {
                bar.parentElement.classList.add('chart__col--peak');
            }
        });
    }

    /* =====================================================================
       3. OPERATING DAYS HELPERS (schedule form)
       ===================================================================== */
    function initDayHelpers() {
        var all  = $('#daysAll');
        var week = $('#daysWorking');
        var none = $('#daysNone');
        var boxes = $$('input[name="operating_days[]"]');

        if (!boxes.length) { return; }

        function set(filter) {
            boxes.forEach(function (box) { box.checked = filter(box.value); });
            // Let the validator know the group changed.
            if (boxes[0]) { boxes[0].dispatchEvent(new Event('change', { bubbles: true })); }
        }

        if (all)  { all.addEventListener('click',  function () { set(function () { return true; }); }); }
        if (none) { none.addEventListener('click', function () { set(function () { return false; }); }); }
        if (week) {
            week.addEventListener('click', function () {
                set(function (day) { return day !== 'Friday'; });
            });
        }
    }

    /* =====================================================================
       4. INSTANT TABLE FILTER
       ---------------------------------------------------------------------
       Narrows the rows already on the page while the admin types. The full
       database search still runs on the server when the form is submitted.
       ===================================================================== */
    function initTableFilter() {
        $$('[data-table-filter]').forEach(function (input) {
            var table = document.querySelector(input.getAttribute('data-table-filter'));
            if (!table) { return; }

            var rows     = $$('tbody tr', table);
            var emptyRow = $('[data-filter-empty]');

            input.addEventListener('input', function () {
                var term  = input.value.trim().toLowerCase();
                var shown = 0;

                rows.forEach(function (row) {
                    var match = !term || row.textContent.toLowerCase().indexOf(term) !== -1;
                    row.style.display = match ? '' : 'none';
                    if (match) { shown++; }
                });

                if (emptyRow) { emptyRow.hidden = shown !== 0; }
            });
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        initSidebar();
        initChart();
        initDayHelpers();
        initTableFilter();
    });
})();
