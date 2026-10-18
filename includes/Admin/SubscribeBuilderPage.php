<?php
namespace SI\SimpleForms\Admin;

defined('ABSPATH') || exit;

use SI\SimpleForms\Core\DbHelper;
use SI\SimpleForms\Core\Logger;
use SI\SimpleForms\Forms\FormRegistry;

final class SubscribeBuilderPage
{
    const OPTION_KEY = 'si_subscribe_form_config';

    public function register()
    {
        add_action('admin_post_si_save_subscribe_builder', [$this, 'saveConfig']);
        add_action('admin_post_si_sync_mailpoet', [$this, 'syncNow']);
    }

    public static function defaultConfig()
    {
        return [
            'title' => 'Subscribe to our Newsletter',
            'desc' => 'Stay updated with our latest newsletter and announcements.',
            'buttonText' => 'SUBSCRIBE NOW',
            'consentText' => 'I agree to receive weekly emails and marketing updates.',
            'privacyConsent' => true,
            'elements' => [
                ['id' => 'name', 'type' => 'name', 'label' => 'Your Name', 'placeholder' => 'Shahinur Islam', 'options' => []],
                ['id' => 'email', 'type' => 'email', 'label' => 'Email Address', 'placeholder' => 'subscriber@example.com', 'options' => []],
            ],
            'provider' => 'mailpoet',
            'apiMode' => 'Automatic (Native WP)',
            'targetList' => 'Newsletter',
            'tags' => ['Website'],
            'subscriberStatus' => 'unconfirmed',
            'doubleOptIn' => true,
            'autoCreate' => true,
            'welcomeEmail' => true,
        ];
    }

    public static function getConfig()
    {
        $saved = get_option(self::OPTION_KEY, []);
        $config = wp_parse_args(is_array($saved) ? $saved : [], self::defaultConfig());
        if (empty($config['elements']) || !is_array($config['elements'])) {
            $config['elements'] = self::defaultConfig()['elements'];
        }
        return $config;
    }

    public function saveConfig()
    {
        if (!isset($_POST['si_subscribe_builder_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['si_subscribe_builder_nonce'])), 'si_save_subscribe_builder')) {
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
        $clean['consentText'] = sanitize_text_field($decoded['consentText'] ?? $clean['consentText']);
        $clean['privacyConsent'] = !isset($decoded['privacyConsent']) || !empty($decoded['privacyConsent']);

        $clean['elements'] = [];
        foreach ((array) ($decoded['elements'] ?? []) as $el) {
            $clean['elements'][] = [
                'id' => sanitize_key($el['id'] ?? uniqid('e')),
                'type' => sanitize_key($el['type'] ?? 'text'),
                'label' => sanitize_text_field($el['label'] ?? ''),
                'placeholder' => sanitize_text_field($el['placeholder'] ?? ''),
                'options' => array_slice(array_map('sanitize_text_field', (array) ($el['options'] ?? [])), 0, 20),
            ];
        }
        if (empty($clean['elements'])) {
            $clean['elements'] = self::defaultConfig()['elements'];
        }

        $clean['provider'] = in_array($decoded['provider'] ?? '', ['mailpoet', 'fluentcrm', 'mailchimp'], true) ? $decoded['provider'] : 'mailpoet';
        $clean['apiMode'] = sanitize_text_field($decoded['apiMode'] ?? 'Automatic (Native WP)');
        $clean['targetList'] = sanitize_text_field($decoded['targetList'] ?? 'Newsletter');
        $clean['tags'] = array_map('sanitize_text_field', (array) ($decoded['tags'] ?? ['Website']));
        $clean['subscriberStatus'] = in_array($decoded['subscriberStatus'] ?? '', ['unconfirmed', 'confirmed'], true) ? $decoded['subscriberStatus'] : 'unconfirmed';
        $clean['doubleOptIn'] = !isset($decoded['doubleOptIn']) || !empty($decoded['doubleOptIn']);
        $clean['autoCreate'] = !isset($decoded['autoCreate']) || !empty($decoded['autoCreate']);
        $clean['welcomeEmail'] = !isset($decoded['welcomeEmail']) || !empty($decoded['welcomeEmail']);

        // Preserve legacy option + store in registry.
        update_option(self::OPTION_KEY, $clean);
        $formId = FormRegistry::save(FormRegistry::SUBSCRIBE, $clean, (isset($_POST['edit_id']) ? absint($_POST['edit_id']) : 0));

        wp_safe_redirect(add_query_arg(['si_saved' => '1', 'si_form_id' => $formId], wp_get_referer()));
        exit;
    }

    public function syncNow()
    {
        if (!isset($_GET['_wpnonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['_wpnonce'])), 'si_sync_mailpoet')) {
            wp_die(esc_html__('Security check failed.', 'si-simple-forms'));
        }
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('You do not have permission to do this.', 'si-simple-forms'));
        }
        if (!class_exists('\MailPoet\API\API')) {
            wp_safe_redirect(add_query_arg('si_sync', 'err', admin_url('admin.php?page=si-simple-forms-subscribe-builder')));
            exit;
        }

        $synced = 0;
        try {
            global $wpdb;
            $api = \MailPoet\API\API::MP('v1');
            $rows = DbHelper::getResults($wpdb->prefix . 'si_subscribers', 'id DESC', 500);
            foreach ($rows as $row) {
                if (!is_email($row['email'])) {
                    continue;
                }
                try {
                    $api->addSubscriber([
                        'email' => $row['email'],
                        'first_name' => $row['name'] ?: '',
                    ], [], 'unconfirmed');
                    $synced++;
                } catch (\Throwable $e) {
                    continue;
                }
            }
        } catch (\Throwable $e) {
            Logger::log('MailPoet Sync', $e->getMessage());
            wp_safe_redirect(add_query_arg('si_sync', 'err', admin_url('admin.php?page=si-simple-forms-subscribe-builder')));
            exit;
        }

        wp_safe_redirect(add_query_arg('si_sync', (string) $synced, admin_url('admin.php?page=si-simple-forms-subscribe-builder')));
        exit;
    }

    private function mailpoetLists()
    {
        if (!class_exists('\MailPoet\API\API')) {
            return ['Newsletter'];
        }
        try {
            $api = \MailPoet\API\API::MP('v1');
            $lists = $api->getLists();
            $names = wp_list_pluck($lists, 'name');
            return $names ?: ['Newsletter'];
        } catch (\Throwable $e) {
            return ['Newsletter'];
        }
    }

    private function mailpoetVersion()
    {
        if (!class_exists('\MailPoet\API\API')) {
            return '';
        }
        if (defined('MAILPOET_VERSION')) {
            return MAILPOET_VERSION;
        }
        try {
            if (!function_exists('get_plugin_data')) {
                require_once ABSPATH . 'wp-admin/includes/plugin.php';
            }
            $data = get_plugin_data(WP_PLUGIN_DIR . '/mailpoet/mailpoet.php', false, false);
            return isset($data['Version']) ? $data['Version'] : '';
        } catch (\Throwable $e) {
            return '';
        }
    }

    public function render()
    {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only 'which form to edit' selector; no data is processed or written here.
        $editId = isset($_GET['edit_form']) ? absint($_GET['edit_form']) : 0;
        $config = self::getConfig();
        if ($editId > 0) {
            $row = FormRegistry::get(FormRegistry::SUBSCRIBE, $editId);
            if ($row && is_array($row['config'])) {
                $config = wp_parse_args($row['config'], self::defaultConfig());
            }
        }

        $jsState = $config;
        $jsState['mailpoetLists'] = $this->mailpoetLists();
        $jsState['mailpoetInstalled'] = class_exists('\MailPoet\API\API');
        $jsState['mailpoetVersion'] = $this->mailpoetVersion();
        $jsState['availableTags'] = ['Website', 'Research', 'Technology', 'Product Updates', 'Marketing'];
        $jsState['turnstileSiteKey'] = get_option('si_turnstile_site_key', '');
        $jsState['syncUrl'] = wp_nonce_url(admin_url('admin-post.php?action=si_sync_mailpoet'), 'si_sync_mailpoet');
        $jsState['privacyUrl'] = admin_url('admin.php?page=si-simple-forms-settings') . '#si-privacy-data';

        Layout::open('si-simple-forms-subscribe-builder');

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only post-save status flag for display only; no data is processed or written here.
        if (isset($_GET['si_saved'])) {
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only post-save status flag for display only; no data is processed or written here.
            $ok = sanitize_text_field(wp_unslash($_GET['si_saved'])) === '1';
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only post-save form-id flag for display only; no data is processed or written here.
            $formId = isset($_GET['si_form_id']) ? absint($_GET['si_form_id']) : 0;
            echo '<div class="si-card" style="margin-bottom:16px;border-color:' . ($ok ? '#bbf7d0' : '#fecaca') . ';background:' . ($ok ? '#f0fdf4' : '#fef2f2') . ';color:' . ($ok ? '#166534' : '#991b1b') . ';padding:12px 18px;font-size:13px;font-weight:600;">';
            if ($ok) {
                echo esc_html__('Subscribe form saved successfully.', 'si-simple-forms');
                if ($formId > 0) {
                    $shortcode = '[si_subscribe_form id="' . $formId . '"]';
                    /* translators: %s: the generated shortcode, e.g. [si_subscribe_form id="3"]. */
                    $shortcodeNotice = sprintf(__('Shortcode: %s — manage it under My Subscription Forms.', 'si-simple-forms'), $shortcode);
                    echo ' ' . esc_html($shortcodeNotice);
                }
            } else {
                echo esc_html__('Could not save the form. Please try again.', 'si-simple-forms');
            }
            echo '</div>';
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only post-sync status flag for display only; no data is processed or written here.
        if (isset($_GET['si_sync'])) {
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only post-sync status flag for display only; no data is processed or written here.
            $val = sanitize_text_field(wp_unslash($_GET['si_sync']));
            if ($val === 'err') {
                echo '<div class="si-card" style="margin-bottom:16px;border-color:#fecaca;background:#fef2f2;color:#991b1b;padding:12px 18px;font-size:13px;font-weight:600;">' . esc_html__('MailPoet sync failed. Make sure MailPoet is installed and active.', 'si-simple-forms') . '</div>';
            } else {
                /* translators: %s: number of subscribers pushed to MailPoet. */
                echo '<div class="si-card" style="margin-bottom:16px;border-color:#bbf7d0;background:#f0fdf4;color:#166534;padding:12px 18px;font-size:13px;font-weight:600;">' . sprintf(esc_html__('MailPoet sync completed. %s subscriber(s) pushed to MailPoet.', 'si-simple-forms'), (int) $val) . '</div>';
            }
        }

        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
        echo '<input type="hidden" name="action" value="si_save_subscribe_builder" />';
        wp_nonce_field('si_save_subscribe_builder', 'si_subscribe_builder_nonce');
        echo '<input type="hidden" name="config_json" value="" />';
        echo '<input type="hidden" name="edit_id" value="' . esc_attr($editId) . '" />';

        echo '<div class="si-builder-top">';
        echo '<a href="' . esc_url(admin_url('admin.php?page=si-simple-forms-my-subscription-forms')) . '" class="si-back dashicons dashicons-arrow-left-alt2"></a>';
        echo '<span style="font-weight:700;font-size:13px;color:#64748b;">' . esc_html__('Form Name:', 'si-simple-forms') . '</span>';
        echo '<input type="text" class="si-form-name" name="form_title_top" value="' . esc_attr($config['title']) . '" />';
        echo '<span class="si-topbar-spacer"></span>';
        echo '<button type="submit" class="si-btn si-btn-primary si-btn-sm">' . esc_html__('Save your Form', 'si-simple-forms') . '</button>';
        echo '</div>';

        // Row 1: Form Elements + Preview on top. Integration moved to the bottom.
        echo '<div class="si-builder-body si-builder-vertical" data-si-subscribe-builder>';
        echo '<div class="si-sub-row-top">';
        echo '<div class="si-builder-col-left">';
        echo '<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;">';
        echo '<strong style="font-size:13px;">' . esc_html__('Form Elements', 'si-simple-forms') . '</strong>';
        echo '<button type="button" class="si-btn si-btn-ghost si-btn-sm">' . esc_html__('Save Section', 'si-simple-forms') . '</button>';
        echo '</div>';
        echo '<div data-si-palette></div>';
        echo '<div data-si-el-settings style="margin-top:14px;"></div>';
        echo '</div>';
        echo '<div class="si-builder-col-center"><div class="si-vp-stage" data-si-vp-stage><div data-si-preview></div></div></div>';
        echo '</div>';

        echo '<div class="si-sub-row-bottom">';
        echo '<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;">';
        echo '<strong style="font-size:14px;">' . esc_html__('Email Marketing Provider & Integration', 'si-simple-forms') . '</strong>';
        echo '<button type="button" class="si-btn si-btn-ghost si-btn-sm">' . esc_html__('Save Integration', 'si-simple-forms') . '</button>';
        echo '</div>';
        echo '<div data-si-provider-panel></div>';
        echo '</div>';

        echo '</div>';
        echo '</form>';

        echo '<script>document.addEventListener("DOMContentLoaded",function(){var root=document.querySelector("[data-si-subscribe-builder]");if(root&&window.SISubscribeBuilder){window.SISubscribeBuilder(root,' . wp_json_encode($jsState) . ');}});</script>';

        Layout::close();
    }
}