(function () {
    'use strict';

    var PALETTE = [
        { type: 'email', label: 'Email Address', icon: 'dashicons-email' },
        { type: 'name', label: 'Subscriber Name', icon: 'dashicons-admin-users' },
        { type: 'phone', label: 'Phone Number', icon: 'dashicons-phone' },
        { type: 'company', label: 'Company / Organization', icon: 'dashicons-building' },
        { type: 'gdpr', label: 'GDPR Compliance', icon: 'dashicons-shield' },
        { type: 'dropdown', label: 'Dropdown Select', icon: 'dashicons-arrow-down-alt2' },
        { type: 'date', label: 'Date', icon: 'dashicons-calendar-alt' },
        { type: 'heading', label: 'Heading Title', icon: 'dashicons-editor-textcolor' },
        { type: 'divider', label: 'Divider Line', icon: 'dashicons-minus' },
        { type: 'customhtml', label: 'Custom HTML Block', icon: 'dashicons-editor-code' }
    ];

    function uid() { return 'e' + Math.random().toString(36).slice(2, 9); }

    function escHtml(str) {
        return (str || '').toString().replace(/[&<>"']/g, function (c) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
        });
    }

    window.SISubscribeBuilder = function (rootEl, initialState) {
        var state = JSON.parse(JSON.stringify(initialState));
        var selectedElId = null;

        // FIX: resolve hidden inputs via the enclosing <form> (they sit outside rootEl).
        var formEl = rootEl.closest('form');
        var els = {
            palette: rootEl.querySelector('[data-si-palette]'),
            elSettings: rootEl.querySelector('[data-si-el-settings]'),
            preview: rootEl.querySelector('[data-si-preview]'),
            providerPanel: rootEl.querySelector('[data-si-provider-panel]'),
            hiddenInput: formEl ? formEl.querySelector('[name="config_json"]') : null,
            formNameInput: formEl ? formEl.querySelector('[name="form_title_top"]') : null
        };

        function elementPreviewHtml(el, idx) {
            var inner = '';
            switch (el.type) {
                case 'email':
                    inner = '<label>' + escHtml(el.label) + '</label><input type="email" placeholder="' + escHtml(el.placeholder) + '" readonly />';
                    break;
                case 'name':
                    inner = '<label>' + escHtml(el.label) + '</label><input type="text" placeholder="' + escHtml(el.placeholder) + '" readonly />';
                    break;
                case 'phone':
                    inner = '<label>' + escHtml(el.label) + '</label><input type="tel" placeholder="' + escHtml(el.placeholder || '+1 (555) 000-0000') + '" readonly />';
                    break;
                case 'company':
                    inner = '<label>' + escHtml(el.label) + '</label><input type="text" placeholder="' + escHtml(el.placeholder || 'Acme Inc.') + '" readonly />';
                    break;
                case 'gdpr':
                    inner = '<div class="si-consent-row"><input type="checkbox" disabled /><span>' + escHtml(el.label || 'I consent to my data being processed per the Privacy Policy.') + '</span></div>';
                    break;
                case 'dropdown':
                    var opts = (el.options && el.options.length ? el.options : []).map(function (o) { return '<option>' + escHtml(o) + '</option>'; }).join('');
                    inner = '<label>' + escHtml(el.label) + '</label><select><option>' + escHtml(el.placeholder || 'Select an option') + '</option>' + opts + '</select>';
                    break;
                case 'date':
                    inner = '<label>' + escHtml(el.label) + '</label><input type="date" readonly />';
                    break;
                case 'heading':
                    inner = '<div class="si-f-heading" style="font-weight:700;font-size:15px;">' + escHtml(el.label || 'Section Heading') + '</div>';
                    break;
                case 'divider':
                    inner = '<hr style="border:0;border-top:1px solid #e2e8f0;" />';
                    break;
                case 'customhtml':
                    inner = '<div style="font-size:12px;color:#94a3b8;border:1px dashed #cbd5e1;border-radius:6px;padding:8px;">Custom HTML Block</div>';
                    break;
            }
            return '<div class="si-pv-field' + (el.id === selectedElId ? ' is-selected' : '') + '" data-idx="' + idx + '" data-el-id="' + el.id + '" draggable="true">' + inner + '</div>';
        }

        function applyVars() {
            rootEl.querySelector('[data-si-preview]').style.setProperty('--si-radius', '10px');
        }

        function renderPreview() {
            applyVars();
            var html = '<div class="si-preview-card">';
            html += '<div class="si-mailpoet-banner">MAILPOET SYNC ENABLED</div>';
            html += '<h2 class="si-preview-title" style="text-align:center;">' + escHtml(state.title) + '</h2>';
            html += '<p class="si-preview-desc" style="text-align:center;">' + escHtml(state.desc) + '</p>';
            state.elements.forEach(function (el, idx) { html += elementPreviewHtml(el, idx); });
            html += '<div class="si-consent-row"><input type="checkbox" disabled /><span>' + escHtml(state.consentText) + '</span></div>';
            html += '<div class="si-consent-row"><input type="checkbox" disabled /><span>I agree to the <a class="si-privacy-link" href="' + escHtml(state.privacyUrl || '#') + '" target="_blank" rel="noopener">Privacy &amp; Data Retention</a> policy.</span></div>';
            html += '<div class="si-turnstile-badge"><span>&#128737; Cloudflare Turnstile Protected<br/><small style="font-weight:400;">Sitekey: ' + escHtml(state.turnstileSiteKey || 'Not configured') + '</small></span><span class="verified">Verified</span></div>';
            html += '<button type="button" class="si-pv-btn">&#10148; ' + escHtml(state.buttonText) + '</button>';
            html += '</div>';
            els.preview.innerHTML = html;

            els.preview.querySelectorAll('[data-el-id]').forEach(function (node) {
                node.addEventListener('dragover', function (e) { e.preventDefault(); });
                node.addEventListener('drop', function (e) {
                    e.preventDefault();
                    var fromIdx = rootEl._dragIdx;
                    var toIdx = parseInt(node.getAttribute('data-idx'), 10);
                    if (fromIdx === undefined || fromIdx === toIdx) return;
                    var moved = state.elements.splice(fromIdx, 1)[0];
                    state.elements.splice(toIdx, 0, moved);
                    renderPreview();
                });
                node.addEventListener('dragstart', function () { rootEl._dragIdx = parseInt(node.getAttribute('data-idx'), 10); });
                node.addEventListener('click', function () {
                    selectedElId = node.getAttribute('data-el-id');
                    renderPreview();
                    renderElSettings();
                });
                node.addEventListener('dblclick', function () {
                    var id = node.getAttribute('data-el-id');
                    state.elements = state.elements.filter(function (e) { return e.id !== id; });
                    if (selectedElId === id) selectedElId = null;
                    renderPreview();
                    renderElSettings();
                });
                node.title = 'Click to edit • Double-click to remove • Drag to reorder';
            });
        }

        function renderPalette() {
            var html = '';
            PALETTE.forEach(function (p) {
                html += '<div class="si-palette-item" data-add-type="' + p.type + '">' +
                    '<span class="dashicons ' + p.icon + '"></span><span>' + p.label + '</span>' +
                    '<span class="si-add-plus">+</span></div>';
            });
            els.palette.innerHTML = html;
            els.palette.querySelectorAll('[data-add-type]').forEach(function (item) {
                item.addEventListener('click', function () {
                    var type = item.getAttribute('data-add-type');
                    var meta = PALETTE.find(function (p) { return p.type === type; });
                    var newEl = { id: uid(), type: type, label: meta.label, placeholder: '', options: type === 'dropdown' ? ['Option 1', 'Option 2', 'Option 3'] : [] };
                    state.elements.push(newEl);
                    selectedElId = newEl.id;
                    renderPreview();
                    renderElSettings();
                });
            });
        }

        function renderElSettings() {
            if (!els.elSettings) return;
            var el = state.elements.find(function (e) { return e.id === selectedElId; });
            if (!el) {
                els.elSettings.innerHTML = '<div class="si-fs-empty" style="padding:14px 6px;">Click an element in the preview to edit it.</div>';
                return;
            }
            var html = '<div class="si-builder-label" style="margin-top:0;">Element Settings</div>' +
                '<div class="si-fs-field"><label>Label</label><input type="text" data-elset="label" value="' + escHtml(el.label) + '" /></div>';
            if (el.type !== 'divider') {
                html += '<div class="si-fs-field"><label>' + (el.type === 'customhtml' ? 'HTML Content' : 'Placeholder') + '</label><input type="text" data-elset="placeholder" value="' + escHtml(el.placeholder) + '" /></div>';
            }
            if (el.type === 'dropdown') {
                el.options = el.options || [];
                html += '<div class="si-fs-field"><label>Dropdown Options</label><div data-elset-options>';
                el.options.forEach(function (opt, i) {
                    html += '<div class="si-fs-opt-row"><span class="si-opt-num">Option ' + (i + 1) + '</span><input type="text" data-elset-opt="' + i + '" value="' + escHtml(opt) + '" /></div>';
                });
                html += '</div><button type="button" class="si-btn si-btn-ghost si-btn-sm" data-elset-addopt style="margin-top:6px;">+ Add Option</button></div>';
            }
            els.elSettings.innerHTML = html;

            els.elSettings.querySelectorAll('[data-elset]').forEach(function (input) {
                input.addEventListener('input', function () {
                    el[input.getAttribute('data-elset')] = input.value;
                    renderPreview();
                });
            });
            els.elSettings.querySelectorAll('[data-elset-opt]').forEach(function (input) {
                input.addEventListener('input', function () {
                    el.options[parseInt(input.getAttribute('data-elset-opt'), 10)] = input.value;
                    renderPreview();
                });
            });
            var addOpt = els.elSettings.querySelector('[data-elset-addopt]');
            if (addOpt) {
                addOpt.addEventListener('click', function () {
                    el.options.push('Option ' + (el.options.length + 1));
                    renderElSettings();
                    renderPreview();
                });
            }
        }

        function renderProviderPanel() {
            var providers = [
                { key: 'mailpoet', label: 'MailPoet', tag: 'Active', future: false },
                { key: 'fluentcrm', label: 'FluentCRM', tag: 'Future', future: true },
                { key: 'mailchimp', label: 'Mailchimp', tag: 'Future', future: true }
            ];
            var allTags = state.availableTags || ['Website', 'Research', 'Technology', 'Product Updates', 'Marketing'];

            var html = '<div class="si-builder-label" style="margin-top:0;">Select Provider</div>';
            providers.forEach(function (p) {
                html += '<label class="si-provider-radio' + (state.provider === p.key ? ' is-active' : '') + '">' +
                    '<span><input type="radio" name="si_provider_radio" value="' + p.key + '" ' + (state.provider === p.key ? 'checked' : '') + ' /> ' + p.label + '</span>' +
                    '<span class="tag' + (p.future ? ' future' : '') + '">' + p.tag + '</span></label>';
            });

            if (state.provider === 'mailpoet' && state.mailpoetInstalled) {
                // Exact MailPoet connection block (shown only when installed & selected)
                html += '<div class="si-integration-grid" style="margin-top:16px;">';
                html += '<div>';
                html += '<div class="si-builder-label" style="margin-top:0;">MailPoet Status</div>';
                html += '<div class="si-mp-status-row"><span class="si-mp-dot"></span> Connected</div>';
                html += '<div class="si-mp-kv"><strong>Version</strong><br/>' + escHtml(state.mailpoetVersion || '5.x.x') + '</div>';
                html += '<div class="si-builder-label">Subscriber List</div>';
                html += '<select class="si-theme-select" data-provider-field="targetList">' +
                    (state.mailpoetLists || ['Newsletter']).map(function (l) {
                        return '<option value="' + escHtml(l) + '"' + (state.targetList === l ? ' selected' : '') + '>' + escHtml(l) + '</option>';
                    }).join('') + '</select>';
                html += '<div class="si-builder-label">Subscriber Status</div>';
                html += '<label class="si-radio-line"><input type="radio" name="si_sub_status" value="unconfirmed" ' + (state.subscriberStatus !== 'confirmed' ? 'checked' : '') + ' /> Unconfirmed (Recommended)</label>';
                html += '<label class="si-radio-line"><input type="radio" name="si_sub_status" value="confirmed" ' + (state.subscriberStatus === 'confirmed' ? 'checked' : '') + ' /> Confirmed</label>';
                html += '</div>';
                html += '<div>';
                html += '<div class="si-toggle-card" style="margin-bottom:10px;"><div><strong>Enable Double Opt-in</strong></div><span class="si-status-pill">&#10003; Enabled</span></div>';
                html += '<div class="si-toggle-card" style="margin-bottom:10px;"><div><strong>Automatically create subscribers</strong></div><span class="si-status-pill">&#10003; Enabled</span></div>';
                html += '<div class="si-toggle-card" style="margin-bottom:10px;"><div><strong>Send Welcome Email</strong></div><span class="si-status-pill">&#10003; Enabled (Handled by MailPoet)</span></div>';
                html += '<div class="si-api-mode-row"><span>&#9889; API Mode</span><span class="si-status-pill">' + escHtml(state.apiMode) + '</span></div>';
                html += '<div class="si-builder-label">Subscriber Tags</div><div class="si-tags-grid">';
                allTags.forEach(function (tag) {
                    var checked = (state.tags || []).indexOf(tag) !== -1;
                    html += '<label><input type="checkbox" data-provider-tag="' + escHtml(tag) + '" ' + (checked ? 'checked' : '') + ' /> #' + tag.replace(/\s+/g, '') + '</label>';
                });
                html += '</div>';
                html += '<div class="si-builder-label">Sync Existing Subscribers</div>';
                html += '<a class="si-btn si-btn-primary si-btn-sm" href="' + escHtml(state.syncUrl || '#') + '"><span class="dashicons dashicons-update"></span> Sync Now</a>';
                html += '</div></div>';
            } else if (state.provider === 'mailpoet') {
                html += '<div class="si-toggle-card" style="margin-top:14px;"><div><strong>MailPoet: Not Installed</strong><br/><span>Install & activate MailPoet to unlock lists, status, and syncing. Everything else stays hidden until then.</span></div><span class="si-status-pill" style="background:#fff7ed;color:#d97706;">Not Installed</span></div>';
            } else {
                html += '<div class="si-toggle-card" style="margin-top:14px;"><div><strong>' + escHtml(state.provider === 'fluentcrm' ? 'FluentCRM' : 'Mailchimp') + ' is on the roadmap.</strong><br/><span>Select MailPoet for live integration today.</span></div><span class="si-status-pill" style="background:#f1f5f9;color:#94a3b8;">Future</span></div>';
            }

            els.providerPanel.innerHTML = html;

            els.providerPanel.querySelectorAll('[name="si_provider_radio"]').forEach(function (radio) {
                radio.addEventListener('change', function () {
                    state.provider = radio.value;
                    renderProviderPanel();
                });
            });
            els.providerPanel.querySelectorAll('[data-provider-field]').forEach(function (input) {
                input.addEventListener('change', function () {
                    state[input.getAttribute('data-provider-field')] = input.value;
                });
            });
            els.providerPanel.querySelectorAll('[name="si_sub_status"]').forEach(function (radio) {
                radio.addEventListener('change', function () {
                    state.subscriberStatus = radio.value;
                });
            });
            els.providerPanel.querySelectorAll('[data-provider-tag]').forEach(function (cb) {
                cb.addEventListener('change', function () {
                    var tag = cb.getAttribute('data-provider-tag');
                    state.tags = state.tags || [];
                    if (cb.checked) {
                        if (state.tags.indexOf(tag) === -1) state.tags.push(tag);
                    } else {
                        state.tags = state.tags.filter(function (t) { return t !== tag; });
                    }
                });
            });
        }

        if (els.formNameInput) {
            els.formNameInput.addEventListener('input', function () {
                state.title = els.formNameInput.value;
                renderPreview();
            });
        }

        if (formEl) {
            formEl.addEventListener('submit', function () {
                if (els.hiddenInput) {
                    els.hiddenInput.value = JSON.stringify(state);
                }
            });
        }

        renderPalette();
        renderPreview();
        renderElSettings();
        renderProviderPanel();
    };
})();