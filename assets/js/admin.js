(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        // Generic search-filter for si-data-table rows
        document.querySelectorAll('[data-si-search]').forEach(function (input) {
            var tableId = input.getAttribute('data-si-search');
            var table = document.getElementById(tableId);
            if (!table) return;
            input.addEventListener('input', function () {
                var q = input.value.trim().toLowerCase();
                var rows = table.querySelectorAll('tbody tr');
                rows.forEach(function (row) {
                    var text = row.getAttribute('data-search') || row.innerText;
                    row.style.display = text.toLowerCase().indexOf(q) === -1 ? 'none' : '';
                });
            });
        });

        // Simple confirm for delete row actions
        document.querySelectorAll('.si-row-actions .si-delete').forEach(function (btn) {
            btn.addEventListener('click', function (e) {
                if (!confirm('Delete this entry? This cannot be undone.')) {
                    e.preventDefault();
                }
            });
        });

        // Settings page: mirror custom CSS textarea count / basic tab toggle groups
        document.querySelectorAll('[data-si-tabs]').forEach(function (group) {
            var tabs = group.querySelectorAll('.si-tab');
            var panels = document.querySelectorAll('[data-si-panel="' + group.getAttribute('data-si-tabs') + '"]');
            tabs.forEach(function (tab) {
                tab.addEventListener('click', function () {
                    tabs.forEach(function (t) { t.classList.remove('is-active'); });
                    tab.classList.add('is-active');
                    var target = tab.getAttribute('data-tab-target');
                    panels.forEach(function (p) {
                        p.style.display = (p.getAttribute('data-panel-name') === target) ? '' : 'none';
                    });
                });
            });
        });

        // Copy-to-clipboard for shortcode copy buttons (My Contact / My Subscription Forms)
        document.querySelectorAll('[data-si-copy]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var text = btn.getAttribute('data-si-copy') || '';
                var original = btn.innerHTML;

                function done() {
                    btn.innerHTML = '&#10003; Copied!';
                    setTimeout(function () { btn.innerHTML = original; }, 1600);
                }

                if (navigator.clipboard && navigator.clipboard.writeText) {
                    navigator.clipboard.writeText(text).then(done, function () { fallbackCopy(text, done); });
                } else {
                    fallbackCopy(text, done);
                }
            });
        });

        function fallbackCopy(text, done) {
            var ta = document.createElement('textarea');
            ta.value = text;
            ta.style.position = 'fixed';
            ta.style.opacity = '0';
            document.body.appendChild(ta);
            ta.select();
            try { document.execCommand('copy'); } catch (e) { /* noop */ }
            document.body.removeChild(ta);
            done();
        }
    });
})();