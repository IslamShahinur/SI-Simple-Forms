<?php
namespace SI\SimpleForms\Forms;

defined('ABSPATH') || exit;

use SI\SimpleForms\Spam\SpamProtectionManager;
use SI\SimpleForms\Admin\SubscribeBuilderPage;

final class SubscriptionForm
{
    public function register()
    {
        add_shortcode('si_subscribe_form', [$this, 'render']);
        add_shortcode('si_subscription_form', [$this, 'render']); // documented alias
        add_action('admin_post_nopriv_si_subscribe_submit', [$this, 'handleSubmit']);
        add_action('admin_post_si_subscribe_submit', [$this, 'handleSubmit']);
    }

    /**
     * @return array{0:int,1:array}
     */
    private function resolveConfig($atts)
    {
        $atts = shortcode_atts(['id' => 0], (array) $atts, 'si_subscribe_form');
        $id = (int) $atts['id'];

        if ($id > 0) {
            $row = FormRegistry::get(FormRegistry::SUBSCRIBE, $id);
            if ($row && is_array($row['config'])) {
                return [$id, wp_parse_args($row['config'], SubscribeBuilderPage::defaultConfig())];
            }
        }

        $first = FormRegistry::first(FormRegistry::SUBSCRIBE);
        if ($first && is_array($first['config'])) {
            return [(int) $first['id'], wp_parse_args($first['config'], SubscribeBuilderPage::defaultConfig())];
        }

        return [1, SubscribeBuilderPage::getConfig()];
    }

    public function render($atts = [])
    {
        list($formId, $config) = $this->resolveConfig($atts);

        ob_start();
        $turnstileWidget = SpamProtectionManager::instance()->renderWidget('turnstile');
        $honeypotWidget = SpamProtectionManager::instance()->renderWidget('honeypot');

        echo '<form action="' . esc_url(admin_url('admin-post.php')) . '" method="post" class="si-simple-form">';
        echo '<input type="hidden" name="action" value="si_subscribe_submit" />';
        echo '<input type="hidden" name="si_form_id" value="' . esc_attr($formId) . '" />';
        wp_nonce_field('si_subscribe_action', 'si_subscribe_nonce');
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- $honeypotWidget is plugin-generated markup, not user input.
        echo $honeypotWidget;

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only display flag, no data is processed or written here.
        if (isset($_GET['si_status']) && sanitize_text_field(wp_unslash($_GET['si_status'])) === 'subscribed') {
            echo '<div class="si-f-notice success">' . esc_html__('Thanks for subscribing!', 'si-simple-forms') . '</div>';
        }

        foreach ((array) ($config['elements'] ?? []) as $el) {
            $name = esc_attr($el['id']);
            switch ($el['type']) {
                case 'email':
                    echo '<div class="si-f-field"><label>' . esc_html($el['label']) . ' <span class="si-req">*</span></label><input type="email" name="email" placeholder="' . esc_attr($el['placeholder']) . '" required /></div>';
                    break;
                case 'name':
                    echo '<div class="si-f-field"><label>' . esc_html($el['label']) . '</label><input type="text" name="name" placeholder="' . esc_attr($el['placeholder']) . '" /></div>';
                    break;
                case 'phone':
                    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- $name is esc_attr()'d above.
                    echo '<div class="si-f-field"><label>' . esc_html($el['label']) . '</label><input type="tel" name="' . $name . '" placeholder="' . esc_attr($el['placeholder'] ?: '+1 (555) 000-0000') . '" /></div>';
                    break;
                case 'company':
                    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- $name is esc_attr()'d above.
                    echo '<div class="si-f-field"><label>' . esc_html($el['label']) . '</label><input type="text" name="' . $name . '" placeholder="' . esc_attr($el['placeholder'] ?: 'Acme Inc.') . '" /></div>';
                    break;
                case 'gdpr':
                    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- $name is esc_attr()'d above.
                    echo '<div class="si-f-consent"><input type="checkbox" name="' . $name . '" required /><span>' . esc_html($el['label'] ?: 'I consent to my data being processed per the Privacy Policy.') . '</span></div>';
                    break;
                case 'dropdown':
                    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- $name is esc_attr()'d above.
                    echo '<div class="si-f-field"><label>' . esc_html($el['label']) . '</label><select name="' . $name . '"><option value="">' . esc_html($el['placeholder'] ?: 'Select an option') . '</option>';
                    foreach ((array) ($el['options'] ?? []) as $option) {
                        if ($option === '') {
                            continue;
                        }
                        echo '<option value="' . esc_attr($option) . '">' . esc_html($option) . '</option>';
                    }
                    echo '</select></div>';
                    break;
                case 'date':
                    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- $name is esc_attr()'d above.
                    echo '<div class="si-f-field"><label>' . esc_html($el['label']) . '</label><input type="date" name="' . $name . '" /></div>';
                    break;
                case 'heading':
                    echo '<div class="si-f-heading">' . esc_html($el['label']) . '</div>';
                    break;
                case 'divider':
                    echo '<hr class="si-f-divider" />';
                    break;
                case 'customhtml':
                    echo '<div>' . wp_kses_post($el['placeholder'] ?? '') . '</div>';
                    break;
            }
        }

        // Newsletter consent (existing)
        echo '<div class="si-f-consent"><input type="checkbox" name="consent" required /><span>' . esc_html($config['consentText']) . '</span></div>';

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
        echo '<p><button type="submit">' . esc_html($config['buttonText'] ?? 'Subscribe') . '</button></p>';
        echo '</form>';
        return ob_get_clean();
    }

    public function handleSubmit()
    {
        if (!isset($_POST['si_subscribe_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['si_subscribe_nonce'])), 'si_subscribe_action')) {
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

        $subData = [
            'name'  => isset($_POST['name']) ? sanitize_text_field(wp_unslash($_POST['name'])) : '',
            'email' => isset($_POST['email']) ? sanitize_email(wp_unslash($_POST['email'])) : '',
        ];

        global $wpdb;
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- writing a form submission to a plugin-owned table; not cacheable data.
        $wpdb->insert(
            $wpdb->prefix . 'si_subscribers',
            [
                'form_id' => 'subscribe-form-' . (int) $formId,
                'name'   => $subData['name'],
                'email'  => $subData['email'],
                'status' => 'Active',
                'tags'   => implode(', ', $config['tags'] ?? ['Website']),
                'source' => 'Website Form',
                'created_at' => current_time('mysql'),
            ]
        );

        do_action('si_simple_forms_subscribe_created', $subData, [
            'emailService'   => ucfirst($config['provider'] ?? 'mailpoet') === 'Mailpoet' ? 'MailPoet' : ucfirst($config['provider']),
            'mailpoetList'   => $config['targetList'] ?? 'Newsletter',
            'subscriberTags' => $config['tags'] ?? ['Website'],
        ]);

        wp_safe_redirect(add_query_arg('si_status', 'subscribed', wp_get_referer() ?: home_url()));
        exit;
    }
}