<?php
namespace SI\SimpleForms\Admin;

defined('ABSPATH') || exit;

use SI\SimpleForms\Forms\FormRegistry;

final class ContactBuilderPage
{
    const OPTION_KEY = 'si_contact_form_config';

    public function register()
    {
        add_action('admin_post_si_save_contact_builder', [$this, 'saveConfig']);
    }

    public static function defaultConfig()
    {
        return [
            'title' => 'Contact Form',
            'desc' => 'Fill out the information below to reach us.',
            'buttonText' => 'Send Message',
            'privacyConsent' => true,
            'fields' => [
                ['id' => 'name', 'type' => 'text', 'label' => 'Name', 'placeholder' => 'Enter your full name', 'required' => true, 'width' => 100, 'help' => '', 'cssClass' => '', 'options' => []],
                ['id' => 'email', 'type' => 'email', 'label' => 'Email', 'placeholder' => 'you@example.com', 'required' => true, 'width' => 100, 'help' => '', 'cssClass' => '', 'options' => []],
                ['id' => 'phone', 'type' => 'tel', 'label' => 'Phone', 'placeholder' => '+1 (555) 000-0000', 'required' => false, 'width' => 100, 'help' => '', 'cssClass' => '', 'options' => []],
                ['id' => 'subject', 'type' => 'text', 'label' => 'Subject', 'placeholder' => 'How can we help?', 'required' => true, 'width' => 100, 'help' => '', 'cssClass' => '', 'options' => []],
                ['id' => 'message', 'type' => 'textarea', 'label' => 'Message', 'placeholder' => 'Write your message here...', 'required' => true, 'width' => 100, 'help' => '', 'cssClass' => '', 'options' => []],
            ],
            'styling' => [
                'theme' => 'modern-light',
                'layout' => 'one-column',
                'radius' => 12,
                'inputStyle' => 'outline',
                'buttonStyle' => 'rounded-pill',
            ],
            'email' => [
                'recipient' => get_bloginfo('admin_email'),
                'subjectTemplate' => 'New Contact Form Submission: {subject}',
                'successMessage' => "Thanks! We'll be in touch soon.",
            ],
        ];
    }

    public static function getConfig()
    {
        $saved = get_option(self::OPTION_KEY, []);
        $config = wp_parse_args(is_array($saved) ? $saved : [], self::defaultConfig());
        if (empty($config['fields']) || !is_array($config['fields'])) {
            $config['fields'] = self::defaultConfig()['fields'];
        }
        return $config;
    }

    public function saveConfig()
    {
        if (!isset($_POST['si_contact_builder_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['si_contact_builder_nonce'])), 'si_save_contact_builder')) {
            wp_die(esc_html__('Security check failed.', 'si-simple-forms'));
        }
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('You do not have permission to do this.', 'si-simple-forms'));
        }

        $json = isset($_POST['config_json']) ? sanitize_textarea_field(wp_unslash($_POST['config_json'])) : '';
        $decoded = json_decode($json, true);
        if (!is_array($decoded)) {
            wp_safe_redirect(add_query_arg('si_saved', '0', wp_get_referer()));
            exit;
        }

        $clean = self::defaultConfig();
        $clean['title'] = sanitize_text_field($decoded['title'] ?? $clean['title']);
        $clean['desc'] = sanitize_text_field($decoded['desc'] ?? $clean['desc']);
        $clean['buttonText'] = sanitize_text_field($decoded['buttonText'] ?? $clean['buttonText']);
        $clean['privacyConsent'] = !isset($decoded['privacyConsent']) || !empty($decoded['privacyConsent']);

        $clean['fields'] = [];
        foreach ((array) ($decoded['fields'] ?? []) as $f) {
            $clean['fields'][] = [
                'id' => sanitize_key($f['id'] ?? uniqid('f')),
                'type' => sanitize_key($f['type'] ?? 'text'),
                'label' => sanitize_text_field($f['label'] ?? ''),
                'placeholder' => sanitize_text_field($f['placeholder'] ?? ''),
                'required' => !empty($f['required']),
                'width' => in_array((int) ($f['width'] ?? 100), [100, 50, 33], true) ? (int) $f['width'] : 100,
                'help' => sanitize_text_field($f['help'] ?? ''),
                'cssClass' => sanitize_html_class($f['cssClass'] ?? ''),
                'options' => array_slice(array_map('sanitize_text_field', (array) ($f['options'] ?? [])), 0, 20),
            ];
        }
        if (empty($clean['fields'])) {
            $clean['fields'] = self::defaultConfig()['fields'];
        }

        $s = (array) ($decoded['styling'] ?? []);
        $clean['styling'] = [
            'theme' => sanitize_key($s['theme'] ?? 'modern-light'),
            'layout' => sanitize_key($s['layout'] ?? 'one-column'),
            'radius' => max(0, min(24, (int) ($s['radius'] ?? 12))),
            'inputStyle' => sanitize_key($s['inputStyle'] ?? 'outline'),
            'buttonStyle' => sanitize_key($s['buttonStyle'] ?? 'rounded-pill'),
        ];

        $e = (array) ($decoded['email'] ?? []);
        $clean['email'] = [
            'recipient' => sanitize_email($e['recipient'] ?? get_bloginfo('admin_email')),
            'subjectTemplate' => sanitize_text_field($e['subjectTemplate'] ?? 'New Contact Form Submission: {subject}'),
            'successMessage' => sanitize_text_field($e['successMessage'] ?? "Thanks! We'll be in touch soon."),
        ];

        // Preserve legacy option (backward compatibility) + store in registry.
        update_option(self::OPTION_KEY, $clean);
        $formId = FormRegistry::save(FormRegistry::CONTACT, $clean, (isset($_POST['edit_id']) ? absint($_POST['edit_id']) : 0));

        wp_safe_redirect(add_query_arg(['si_saved' => '1', 'si_form_id' => $formId], wp_get_referer()));
        exit;
    }

    public function render()
    {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only 'which form to edit' selector; no data is processed or written here.
        $editId = isset($_GET['edit_form']) ? absint($_GET['edit_form']) : 0;
        $config = self::getConfig();
        if ($editId > 0) {
            $row = FormRegistry::get(FormRegistry::CONTACT, $editId);
            if ($row && is_array($row['config'])) {
                $config = wp_parse_args($row['config'], self::defaultConfig());
            }
        }

        $turnstileActive = get_option('si_turnstile_site_key', '') !== '' && get_option('si_turnstile_secret_key', '') !== '';
        $jsState = $config;
        $jsState['turnstileActive'] = $turnstileActive;
        $jsState['privacyUrl'] = admin_url('admin.php?page=si-simple-forms-settings') . '#si-privacy-data';

        Layout::open('si-simple-forms-contact-builder');

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only post-save status flag for display only; no data is processed or written here.
        if (isset($_GET['si_saved'])) {
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only post-save status flag for display only; no data is processed or written here.
            $ok = sanitize_text_field(wp_unslash($_GET['si_saved'])) === '1';
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only post-save form-id flag for display only; no data is processed or written here.
            $formId = isset($_GET['si_form_id']) ? absint($_GET['si_form_id']) : 0;
            echo '<div class="si-card" style="margin-bottom:16px;border-color:' . ($ok ? '#bbf7d0' : '#fecaca') . ';background:' . ($ok ? '#f0fdf4' : '#fef2f2') . ';color:' . ($ok ? '#166534' : '#991b1b') . ';padding:12px 18px;font-size:13px;font-weight:600;">';
            if ($ok) {
                echo esc_html__('Contact form saved successfully.', 'si-simple-forms');
                if ($formId > 0) {
                    $shortcode = '[si_contact_form id="' . $formId . '"]';
                    /* translators: %s: the generated shortcode, e.g. [si_contact_form id="3"]. */
                    $shortcodeNotice = sprintf(__('Shortcode: %s — manage it under My Contact Forms.', 'si-simple-forms'), $shortcode);
                    echo ' ' . esc_html($shortcodeNotice);
                }
            } else {
                echo esc_html__('Could not save the form. Please try again.', 'si-simple-forms');
            }
            echo '</div>';
        }

        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
        echo '<input type="hidden" name="action" value="si_save_contact_builder" />';
        wp_nonce_field('si_save_contact_builder', 'si_contact_builder_nonce');
        echo '<input type="hidden" name="config_json" value="" />';
        echo '<input type="hidden" name="edit_id" value="' . esc_attr($editId) . '" />';

        echo '<div class="si-builder-top">';
        echo '<a href="' . esc_url(admin_url('admin.php?page=si-simple-forms-my-contact-forms')) . '" class="si-back dashicons dashicons-arrow-left-alt2"></a>';
        echo '<div><strong style="font-size:15px;">' . esc_html__('Contact Form', 'si-simple-forms') . '</strong></div>';
        echo '<input type="text" class="si-form-name" name="form_title_top" value="' . esc_attr($config['title']) . '" placeholder="' . esc_attr__('Form Name', 'si-simple-forms') . '" />';
        echo '<span class="si-slug-badge">slug: contact-form' . ($editId > 0 ? ' &bull; editing #' . (int) $editId : '') . '</span>';
        echo '<span class="si-status-badge">Active</span>';
        echo '<span class="si-topbar-spacer"></span>';
        echo '<div class="si-viewport-toggle"><button type="button" class="is-active" data-viewport="desktop">&#128421; ' . esc_html__('Desktop', 'si-simple-forms') . '</button><button type="button" data-viewport="tablet">&#9707; ' . esc_html__('Tablet', 'si-simple-forms') . '</button><button type="button" data-viewport="mobile">&#128241; ' . esc_html__('Mobile', 'si-simple-forms') . '</button></div>';
        echo '<button type="button" class="si-btn si-btn-ghost si-btn-sm">' . esc_html__('Preview', 'si-simple-forms') . '</button>';
        echo '<button type="submit" class="si-btn si-btn-primary si-btn-sm">' . esc_html__('Save Form', 'si-simple-forms') . '</button>';
        echo '</div>';

        // Row 1: the 4 tabbed sections full-width on top.
        echo '<div class="si-builder-body si-builder-vertical" data-si-contact-builder>';
        echo '<div class="si-builder-row-top">';
        echo '<div class="si-tab-row" data-si-tabs="contact">';
        echo '<button type="button" class="si-tab is-active" data-left-tab="fields">Fields</button>';
        echo '<button type="button" class="si-tab" data-left-tab="styling">Styling</button>';
        echo '<button type="button" class="si-tab" data-left-tab="email">Email</button>';
        echo '<button type="button" class="si-tab" data-left-tab="spam">Spam</button>';
        echo '</div>';
        echo '<div data-si-left-fields></div>';
        echo '<div data-si-left-styling style="display:none;"></div>';
        echo '<div data-si-left-email style="display:none;"></div>';
        echo '<div data-si-left-spam style="display:none;"></div>';
        echo '</div>';

        // Row 2: preview (left) + field settings (right).
        echo '<div class="si-builder-row-bottom">';
        echo '<div class="si-builder-col-center"><div class="si-vp-stage" data-si-vp-stage><div data-si-preview></div></div></div>';
        echo '<div class="si-builder-col-right"><div data-si-right-panel></div></div>';
        echo '</div>';

        echo '</div>';
        echo '</form>';

        echo '<script>document.addEventListener("DOMContentLoaded",function(){var root=document.querySelector("[data-si-contact-builder]");if(root&&window.SIContactBuilder){window.SIContactBuilder(root,' . wp_json_encode($jsState) . ');}});</script>';

        Layout::close();
    }
}