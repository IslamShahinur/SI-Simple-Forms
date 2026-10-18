<?php
namespace SI\SimpleForms\Forms;

defined('ABSPATH') || exit;

use SI\SimpleForms\Spam\SpamProtectionManager;
use SI\SimpleForms\Admin\ContactBuilderPage;

final class ContactForm
{
    public function register()
    {
        add_shortcode('si_contact_form', [$this, 'render']);
        add_action('admin_post_nopriv_si_contact_submit', [$this, 'handleSubmit']);
        add_action('admin_post_si_contact_submit', [$this, 'handleSubmit']);
    }

    /**
     * Resolve form config: [si_contact_form id="N"] → registry; else first registry form; else legacy option.
     *
     * @return array{0:int,1:array}
     */
    private function resolveConfig($atts)
    {
        $atts = shortcode_atts(['id' => 0], (array) $atts, 'si_contact_form');
        $id = (int) $atts['id'];

        if ($id > 0) {
            $row = FormRegistry::get(FormRegistry::CONTACT, $id);
            if ($row && is_array($row['config'])) {
                return [$id, wp_parse_args($row['config'], ContactBuilderPage::defaultConfig())];
            }
        }

        $first = FormRegistry::first(FormRegistry::CONTACT);
        if ($first && is_array($first['config'])) {
            return [(int) $first['id'], wp_parse_args($first['config'], ContactBuilderPage::defaultConfig())];
        }

        return [1, ContactBuilderPage::getConfig()];
    }

    public function render($atts = [])
    {
        list($formId, $config) = $this->resolveConfig($atts);

        $styling = is_array($config['styling'] ?? null) ? $config['styling'] : [];
        $radius = (int) ($styling['radius'] ?? 12) . 'px';
        $btnRadius = ($styling['buttonStyle'] ?? '') === 'rounded-pill' ? '999px' : (($styling['buttonStyle'] ?? '') === 'square' ? '4px' : '8px');
        $inputBorder = ($styling['inputStyle'] ?? '') === 'filled' ? 'transparent' : '#d1d5db';

        ob_start();
        $turnstileWidget = SpamProtectionManager::instance()->renderWidget('turnstile');
        $honeypotWidget = SpamProtectionManager::instance()->renderWidget('honeypot');

        echo '<form action="' . esc_url(admin_url('admin-post.php')) . '" method="post" class="si-simple-form" style="--si-radius:' . esc_attr($radius) . ';--si-btn-radius:' . esc_attr($btnRadius) . ';--si-input-border:' . esc_attr($inputBorder) . ';">';
        echo '<input type="hidden" name="action" value="si_contact_submit" />';
        echo '<input type="hidden" name="si_form_id" value="' . esc_attr($formId) . '" />';
        wp_nonce_field('si_contact_action', 'si_contact_nonce');
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- $honeypotWidget is plugin-generated markup (input field), not user input.
        echo $honeypotWidget;

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only display flag, no data is processed or written here.
        if (isset($_GET['si_status']) && sanitize_text_field(wp_unslash($_GET['si_status'])) === 'success') {
            echo '<div class="si-f-notice success">' . esc_html($config['email']['successMessage'] ?? "Thanks! We'll be in touch soon.") . '</div>';
        }

        foreach ((array) ($config['fields'] ?? []) as $field) {
            $required = !empty($field['required']);
            echo '<div class="si-f-field' . (empty($field['cssClass']) ? '' : ' ' . esc_attr($field['cssClass'])) . '">';
            echo '<label>' . esc_html($field['label']) . ($required ? ' <span class="si-req">*</span>' : '') . '</label>';
            $name = esc_attr($field['id']);
            $placeholder = esc_attr($field['placeholder']);
            $reqAttr = $required ? ' required' : '';
            if ($field['type'] === 'textarea') {
                // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- $name/$placeholder are esc_attr()'d above; $reqAttr is a hard-coded literal.
                echo '<textarea name="' . $name . '" rows="5" placeholder="' . $placeholder . '"' . $reqAttr . '></textarea>';
            } elseif ($field['type'] === 'select') {
                // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- $name is esc_attr()'d above; $reqAttr is a hard-coded literal.
                echo '<select name="' . $name . '"' . $reqAttr . '><option value="">' . esc_html($field['placeholder'] ?: 'Select an option') . '</option>';
                foreach ((array) ($field['options'] ?? []) as $option) {
                    if ($option === '') {
                        continue;
                    }
                    echo '<option value="' . esc_attr($option) . '">' . esc_html($option) . '</option>';
                }
                echo '</select>';
            } else {
                $htmlType = in_array($field['type'], ['email', 'tel', 'date'], true) ? $field['type'] : 'text';
                // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- $name/$placeholder are esc_attr()'d above; $reqAttr is a hard-coded literal.
                echo '<input type="' . esc_attr($htmlType) . '" name="' . $name . '" placeholder="' . $placeholder . '"' . $reqAttr . ' />';
            }
            if (!empty($field['help'])) {
                echo '<small>' . esc_html($field['help']) . '</small>';
            }
            echo '</div>';
        }

        // Privacy & Data Retention consent (links to Settings → Privacy & Data section)
        if (!isset($config['privacyConsent']) || !empty($config['privacyConsent'])) {
            echo '<div class="si-f-consent"><input type="checkbox" name="privacy_consent" required /><span>' .
                sprintf(
                    /* translators: %s: linked "Privacy & Data Retention" label. */
                    esc_html__('I agree to the %s policy.', 'si-simple-forms'),
                    '<a class="si-privacy-link" href="' . esc_url(admin_url('admin.php?page=si-simple-forms-settings') . '#si-privacy-data') . '" target="_blank" rel="noopener">' . esc_html__('Privacy & Data Retention', 'si-simple-forms') . '</a>'
                ) . '</span></div>';
        }

        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- $turnstileWidget is plugin-generated markup, not user input.
        echo $turnstileWidget;
        echo '<p><button type="submit">' . esc_html($config['buttonText'] ?? 'Send Message') . '</button></p>';
        echo '</form>';
        return ob_get_clean();
    }

    public function handleSubmit()
    {
        if (!isset($_POST['si_contact_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['si_contact_nonce'])), 'si_contact_action')) {
            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- __() returns a static, developer-defined string.
            wp_die(esc_html__('Invalid nonce verification', 'si-simple-forms'));
        }

        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- raw $_POST is passed through to SpamProtectionManager, which sanitizes each field it reads.
        $isValid = SpamProtectionManager::instance()->verifySubmission(true, $_POST);
        if ($isValid !== true) {
            $errorMsg = is_wp_error($isValid) ? $isValid->get_error_message() : __('Security verification failed. Please try again.', 'si-simple-forms');
            wp_die(esc_html($errorMsg));
        }

        list($formId, $config) = $this->resolveConfig(['id' => isset($_POST['si_form_id']) ? absint($_POST['si_form_id']) : 0]);

        global $wpdb;
        $name = isset($_POST['name']) ? sanitize_text_field(wp_unslash($_POST['name'])) : '';
        $email = isset($_POST['email']) ? sanitize_email(wp_unslash($_POST['email'])) : '';
        $subject = isset($_POST['subject']) ? sanitize_text_field(wp_unslash($_POST['subject'])) : '';
        $message = isset($_POST['message']) ? sanitize_textarea_field(wp_unslash($_POST['message'])) : '';
        $phone = isset($_POST['phone']) ? sanitize_text_field(wp_unslash($_POST['phone'])) : '';

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- writing a form submission to a plugin-owned table; not cacheable data.
        $wpdb->insert(
            $wpdb->prefix . 'si_messages',
            [
                'form_id' => 'contact-form-' . (int) $formId,
                'name'    => $name,
                'email'   => $email,
                'phone'   => $phone,
                'subject' => $subject,
                'message' => $message,
                'status'  => 'unread',
                'created_at' => current_time('mysql'),
            ]
        );

        $recipient = $config['email']['recipient'] ?? get_bloginfo('admin_email');
        $subjectTemplate = str_replace('{subject}', $subject, $config['email']['subjectTemplate'] ?? 'New Contact Form Submission: {subject}');
        if (is_email($recipient)) {
            wp_mail($recipient, $subjectTemplate, "From: {$name} <{$email}>\n\n{$message}");
        }

        wp_safe_redirect(add_query_arg('si_status', 'success', wp_get_referer() ?: home_url()));
        exit;
    }
}