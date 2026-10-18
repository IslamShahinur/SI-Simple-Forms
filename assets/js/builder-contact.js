(function () {
    'use strict';

    var FIELD_TYPES = {
        text: { label: 'Text Field', icon: 'dashicons-editor-textcolor' },
        email: { label: 'Email Field', icon: 'dashicons-email' },
        tel: { label: 'Phone Field', icon: 'dashicons-phone' },
        textarea: { label: 'Textarea', icon: 'dashicons-editor-alignleft' },
        select: { label: 'Dropdown', icon: 'dashicons-menu-alt' },
        date: { label: 'Date', icon: 'dashicons-calendar-alt' }
    };

    function uid() {
        return 'f' + Math.random().toString(36).slice(2, 9);
    }

    window.SIContactBuilder = function (rootEl, initialState) {
        var state = JSON.parse(JSON.stringify(initialState));
        var selectedFieldId = state.fields.length ? state.fields[0].id : null;
        var activeLeftTab = 'fields';
        var dragIndex = null;

        // FIX: hidden inputs & form-name live in the <form>, outside rootEl — resolve via closest('form').
        var formEl = rootEl.closest('form');
        var els = {
            leftFields: rootEl.querySelector('[data-si-left-fields]'),
            leftStyling: rootEl.querySelector('[data-si-left-styling]'),
            leftEmail: rootEl.querySelector('[data-si-left-email]'),
            leftSpam: rootEl.querySelector('[data-si-left-spam]'),
            preview: rootEl.querySelector('[data-si-preview]'),
            stage: rootEl.querySelector('[data-si-vp-stage]'),
            rightPanel: rootEl.querySelector('[data-si-right-panel]'),
            hiddenInput: formEl ? formEl.querySelector('[name="config_json"]') : null,
            formNameInput: formEl ? formEl.querySelector('[name="form_title_top"]') : null,
            tabs: rootEl.querySelectorAll('.si-tab[data-left-tab]')
        };

        function applyStylingVars() {
            var root = els.preview;
            var s = state.styling;
            root.style.setProperty('--si-radius', (s.radius || 12) + 'px');
            root.style.setProperty('--si-input-border', s.inputStyle === 'filled' ? 'transparent' : '#d1d5db');
            root.style.setProperty('--si-btn-radius', s.buttonStyle === 'rounded-pill' ? '999px' : (s.buttonStyle === 'square' ? '4px' : '8px'));
            root.style.setProperty('--si-btn-color', '#2b3fe0');
        }

        function fieldHtml(f) {
            var reqMark = f.required ? '<span class="req"> *</span>' : '';
            var widthStyle = f.width && f.width < 100 ? 'display:inline-block;width:' + f.width + '%;vertical-align:top;' : '';
            var input = '';
            if (f.type === 'textarea') {
                input = '<textarea rows="4" placeholder="' + escAttr(f.placeholder) + '" readonly></textarea>';
            } else if (f.type === 'select') {
                var opts = (f.options && f.options.length ? f.options : []).map(function (o) {
                    return '<option>' + escHtml(o) + '</option>';
                }).join('');
                input = '<select><option>' + escHtml(f.placeholder || 'Select an option') + '</option>' + opts + '</select>';
            } else {
                var htmlType = (f.type === 'tel') ? 'tel' : (f.type === 'date' ? 'date' : (f.type === 'email' ? 'email' : 'text'));
                input = '<input type="' + htmlType + '" placeholder="' + escAttr(f.placeholder) + '" readonly />';
            }
            return '<div class="si-pv-field' + (f.id === selectedFieldId ? ' is-selected' : '') + '" style="' + widthStyle + '" data-field-id="' + f.id + '">' +
                '<label>' + escHtml(f.label) + reqMark + '</label>' + input +
                (f.help ? '<small style="color:#94a3b8;font-size:11px;display:block;margin-top:4px;">' + escHtml(f.help) + '</small>' : '') +
                '</div>';
        }

        function renderPreview() {
            applyStylingVars();
            var html = '<div class="si-preview-card">' +
                '<span class="si-live-badge">LIVE PREVIEW</span>' +
                '<h2 class="si-preview-title">' + escHtml(state.title) + '</h2>' +
                '<p class="si-preview-desc">' + escHtml(state.desc) + '</p>';
            state.fields.forEach(function (f) { html += fieldHtml(f); });

            // Privacy & Data Retention consent at the end of the form
            html += '<div class="si-consent-row"><input type="checkbox" disabled /><span>I agree to the <a class="si-privacy-link" href="' + escAttr(state.privacyUrl || '#') + '" target="_blank" rel="noopener">Privacy &amp; Data Retention</a> policy.</span></div>';

            html += '<div class="si-pv-submit-wrap"><button type="button" class="si-pv-btn">' + escHtml(state.buttonText || 'Send Message') + '</button></div>';
            html += '</div>';
            els.preview.innerHTML = html;

            els.preview.querySelectorAll('[data-field-id]').forEach(function (node) {
                node.addEventListener('click', function () {
                    selectedFieldId = node.getAttribute('data-field-id');
                    renderPreview();
                    renderRightPanel();
                    renderFieldsTab();
                });
            });
        }

        function renderFieldsTab() {
            var html = '<div class="si-builder-label">Form Fields</div><ul class="si-field-list" data-si-sortable>';
            state.fields.forEach(function (f, idx) {
                html += '<li class="si-field-row-item' + (f.id === selectedFieldId ? ' is-selected' : '') + '" draggable="true" data-idx="' + idx + '" data-field-id="' + f.id + '">' +
                    '<span class="dashicons dashicons-move"></span><span>' + escHtml(f.label) + (f.type === 'select' ? ' <small style="color:#94a3b8;">(' + (f.options || []).length + ' options)</small>' : '') + '</span>' +
                    '<button type="button" class="si-fr-remove" data-remove-id="' + f.id + '" title="Remove field">&times;</button>' +
                    '</li>';
            });
            html += '</ul>';
            html += '<select class="si-add-field-select" data-add-field>' +
                '<option value="">+ Add Field&hellip;</option>' +
                Object.keys(FIELD_TYPES).map(function (k) { return '<option value="' + k + '">' + FIELD_TYPES[k].label + '</option>'; }).join('') +
                '</select>';
            els.leftFields.innerHTML = html;

            els.leftFields.querySelectorAll('[data-field-id]').forEach(function (row) {
                row.addEventListener('click', function (e) {
                    if (e.target.closest('.si-fr-remove')) return;
                    selectedFieldId = row.getAttribute('data-field-id');
                    renderFieldsTab(); renderPreview(); renderRightPanel();
                });
                row.addEventListener('dragstart', function () { dragIndex = parseInt(row.getAttribute('data-idx'), 10); });
                row.addEventListener('dragover', function (e) { e.preventDefault(); });
                row.addEventListener('drop', function (e) {
                    e.preventDefault();
                    var targetIdx = parseInt(row.getAttribute('data-idx'), 10);
                    if (dragIndex === null || dragIndex === targetIdx) return;
                    var moved = state.fields.splice(dragIndex, 1)[0];
                    state.fields.splice(targetIdx, 0, moved);
                    dragIndex = null;
                    renderFieldsTab(); renderPreview();
                });
            });
            els.leftFields.querySelectorAll('.si-fr-remove').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    var id = btn.getAttribute('data-remove-id');
                    state.fields = state.fields.filter(function (f) { return f.id !== id; });
                    if (selectedFieldId === id) selectedFieldId = state.fields.length ? state.fields[0].id : null;
                    renderFieldsTab(); renderPreview(); renderRightPanel();
                });
            });
            var addSelect = els.leftFields.querySelector('[data-add-field]');
            addSelect.addEventListener('change', function () {
                var type = addSelect.value;
                if (!type) return;
                var meta = FIELD_TYPES[type];
                var newField = { id: uid(), type: type, label: meta.label, placeholder: '', required: false, width: 100, help: '', cssClass: '', options: type === 'select' ? ['Option 1', 'Option 2', 'Option 3'] : [] };
                state.fields.push(newField);
                selectedFieldId = newField.id;
                addSelect.value = '';
                renderFieldsTab(); renderPreview(); renderRightPanel();
            });
        }

        function renderStylingTab() {
            var s = state.styling;
            els.leftStyling.innerHTML =
                '<div class="si-builder-label">Theme</div>' +
                '<select class="si-theme-select" data-style="theme">' +
                ['modern-light', 'dark-slate', 'minimal'].map(function (v) {
                    var labels = { 'modern-light': 'Modern Light', 'dark-slate': 'Dark Slate', 'minimal': 'Minimal' };
                    return '<option value="' + v + '"' + (s.theme === v ? ' selected' : '') + '>' + labels[v] + '</option>';
                }).join('') + '</select>' +
                '<div class="si-builder-label">Layout Style</div>' +
                '<select class="si-layout-select" data-style="layout">' +
                ['one-column', 'two-column'].map(function (v) {
                    var labels = { 'one-column': 'One Column', 'two-column': 'Two Column' };
                    return '<option value="' + v + '"' + (s.layout === v ? ' selected' : '') + '>' + labels[v] + '</option>';
                }).join('') + '</select>' +
                '<div class="si-builder-label">Border Radius (<span data-radius-val>' + s.radius + '</span>px)</div>' +
                '<div class="si-radius-row"><input type="range" min="0" max="24" value="' + s.radius + '" data-style="radius" /></div>' +
                '<div class="si-builder-label">Input Style</div>' +
                '<div class="si-input-style-row" data-style-group="inputStyle">' +
                ['outline', 'filled', 'underline'].map(function (v) {
                    var labels = { outline: 'Outline', filled: 'Filled', underline: 'Underline' };
                    return '<button type="button" data-val="' + v + '" class="' + (s.inputStyle === v ? 'is-active' : '') + '">' + labels[v] + '</button>';
                }).join('') + '</div>' +
                '<div class="si-builder-label">Button Style</div>' +
                '<select class="si-btn-style-select" data-style="buttonStyle">' +
                ['rounded-pill', 'rounded', 'square'].map(function (v) {
                    var labels = { 'rounded-pill': 'Rounded Pill', rounded: 'Rounded', square: 'Square' };
                    return '<option value="' + v + '"' + (s.buttonStyle === v ? ' selected' : '') + '>' + labels[v] + '</option>';
                }).join('') + '</select>';

            els.leftStyling.querySelectorAll('[data-style]').forEach(function (input) {
                input.addEventListener('input', function () {
                    var key = input.getAttribute('data-style');
                    state.styling[key] = (input.type === 'range') ? parseInt(input.value, 10) : input.value;
                    if (key === 'radius') {
                        els.leftStyling.querySelector('[data-radius-val]').textContent = state.styling.radius;
                    }
                    renderPreview();
                });
            });
            els.leftStyling.querySelectorAll('[data-style-group="inputStyle"] button').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    state.styling.inputStyle = btn.getAttribute('data-val');
                    renderStylingTab();
                    renderPreview();
                });
            });
        }

        function renderEmailTab() {
            var e = state.email;
            els.leftEmail.innerHTML =
                '<div class="si-builder-label">Notification Recipient</div>' +
                '<input type="text" data-email="recipient" value="' + escAttr(e.recipient) + '" class="si-theme-select" />' +
                '<div class="si-builder-label">Email Subject Template</div>' +
                '<input type="text" data-email="subjectTemplate" value="' + escAttr(e.subjectTemplate) + '" class="si-theme-select" />' +
                '<div class="si-builder-label">Success Message</div>' +
                '<input type="text" data-email="successMessage" value="' + escAttr(e.successMessage) + '" class="si-theme-select" />' +
                '<div class="si-builder-label">Submit Button Text</div>' +
                '<input type="text" data-email="buttonText" value="' + escAttr(state.buttonText) + '" class="si-theme-select" />';
            els.leftEmail.querySelectorAll('[data-email]').forEach(function (input) {
                input.addEventListener('input', function () {
                    var key = input.getAttribute('data-email');
                    if (key === 'buttonText') { state.buttonText = input.value; } else { state.email[key] = input.value; }
                    renderPreview();
                });
            });
        }

        function renderSpamTab() {
            els.leftSpam.innerHTML =
                '<div class="si-toggle-card" style="margin-bottom:10px;">' +
                '<div><strong>Honeypot Trap</strong><br/><span>Hidden field auto-blocks basic bots.</span></div>' +
                '<span class="si-status-pill">Always On</span></div>' +
                '<div class="si-toggle-card">' +
                '<div><strong>Cloudflare Turnstile</strong><br/><span>' + (state.turnstileActive ? 'Active on this form.' : 'Configure Site/Secret keys in Settings to enable.') + '</span></div>' +
                '<span class="si-status-pill" style="' + (state.turnstileActive ? '' : 'background:#f1f5f9;color:#94a3b8;') + '">' + (state.turnstileActive ? 'Active' : 'Inactive') + '</span></div>';
        }

        function renderRightPanel() {
            var field = state.fields.find(function (f) { return f.id === selectedFieldId; });
            if (!field) {
                els.rightPanel.innerHTML = '<div class="si-fs-empty">Select a field in the preview to edit its settings.</div>';
                return;
            }
            var html =
                '<h3 style="margin-top:0;font-size:14px;font-weight:800;">Field Settings</h3>' +
                '<div class="si-fs-field"><label>Field Label</label><input type="text" data-fs="label" value="' + escAttr(field.label) + '" /></div>' +
                '<div class="si-fs-field"><label>Placeholder Text</label><input type="text" data-fs="placeholder" value="' + escAttr(field.placeholder) + '" /></div>';

            // Dropdown options editor (custom texts per option)
            if (field.type === 'select') {
                field.options = field.options || [];
                html += '<div class="si-fs-field"><label>Dropdown Options</label><div data-si-options-list>';
                field.options.forEach(function (opt, i) {
                    html += '<div class="si-fs-opt-row"><span class="si-opt-num">Option ' + (i + 1) + '</span><input type="text" data-opt-index="' + i + '" value="' + escAttr(opt) + '" placeholder="e.g. Complaint" /></div>';
                });
                html += '</div><button type="button" class="si-btn si-btn-ghost si-btn-sm" data-si-add-option style="margin-top:6px;">+ Add Option</button></div>';
            }

            html +=
                '<div class="si-fs-check-row"><strong style="font-size:12.5px;">Required Field</strong><label class="si-switch"><input type="checkbox" data-fs="required" ' + (field.required ? 'checked' : '') + ' /><span class="slider"></span></label></div>' +
                '<div class="si-fs-field"><label>Field Width</label><div class="si-fs-width-row">' +
                [100, 50, 33].map(function (w) {
                    return '<button type="button" data-fs-width="' + w + '" class="' + (field.width === w ? 'is-active' : '') + '">' + w + '%</button>';
                }).join('') + '</div></div>' +
                '<div class="si-fs-field"><label>Help Text / Tooltip</label><input type="text" data-fs="help" value="' + escAttr(field.help) + '" /></div>' +
                '<div class="si-fs-field"><label>CSS Class Name</label><input type="text" data-fs="cssClass" value="' + escAttr(field.cssClass) + '" /></div>';

            els.rightPanel.innerHTML = html;

            els.rightPanel.querySelectorAll('[data-fs]').forEach(function (input) {
                var evt = input.type === 'checkbox' ? 'change' : 'input';
                input.addEventListener(evt, function () {
                    var key = input.getAttribute('data-fs');
                    field[key] = (input.type === 'checkbox') ? input.checked : input.value;
                    renderFieldsTab(); renderPreview();
                });
            });
            els.rightPanel.querySelectorAll('[data-fs-width]').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    field.width = parseInt(btn.getAttribute('data-fs-width'), 10);
                    renderRightPanel(); renderPreview();
                });
            });

            // Dropdown options events
            els.rightPanel.querySelectorAll('[data-opt-index]').forEach(function (input) {
                input.addEventListener('input', function () {
                    field.options[parseInt(input.getAttribute('data-opt-index'), 10)] = input.value;
                    renderPreview();
                });
            });
            var addOpt = els.rightPanel.querySelector('[data-si-add-option]');
            if (addOpt) {
                addOpt.addEventListener('click', function () {
                    field.options.push('Option ' + (field.options.length + 1));
                    renderRightPanel(); renderPreview(); renderFieldsTab();
                });
            }
        }

        function switchLeftTab(name) {
            activeLeftTab = name;
            [els.leftFields, els.leftStyling, els.leftEmail, els.leftSpam].forEach(function (el) { el.style.display = 'none'; });
            ({ fields: els.leftFields, styling: els.leftStyling, email: els.leftEmail, spam: els.leftSpam })[name].style.display = '';
            els.tabs.forEach(function (t) { t.classList.toggle('is-active', t.getAttribute('data-left-tab') === name); });
        }

        els.tabs.forEach(function (t) {
            t.addEventListener('click', function () { switchLeftTab(t.getAttribute('data-left-tab')); });
        });

        // Form Name (top bar) syncs with state.title
        if (els.formNameInput) {
            els.formNameInput.addEventListener('input', function () {
                state.title = els.formNameInput.value;
                renderPreview();
            });
        }

        // FIX: Desktop / Tablet / Mobile responsive preview + safe submit wiring
        if (formEl) {
            formEl.querySelectorAll('[data-viewport]').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    formEl.querySelectorAll('[data-viewport]').forEach(function (b) { b.classList.remove('is-active'); });
                    btn.classList.add('is-active');
                    var vp = btn.getAttribute('data-viewport');
                    if (els.stage) {
                        els.stage.className = 'si-vp-stage' + (vp === 'tablet' ? ' vp-tablet' : (vp === 'mobile' ? ' vp-mobile' : ''));
                    }
                });
            });

            formEl.addEventListener('submit', function () {
                if (els.hiddenInput) {
                    els.hiddenInput.value = JSON.stringify(state);
                }
            });
        }

        function escHtml(str) {
            return (str || '').toString().replace(/[&<>"']/g, function (c) {
                return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
            });
        }
        function escAttr(str) { return escHtml(str); }

        switchLeftTab('fields');
        renderFieldsTab();
        renderStylingTab();
        renderEmailTab();
        renderSpamTab();
        renderPreview();
        renderRightPanel();
    };
})();