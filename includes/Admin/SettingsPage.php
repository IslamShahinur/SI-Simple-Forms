<?php
namespace SI\SimpleForms\Admin;

defined('ABSPATH') || exit;

final class SettingsPage
{
    /** Remote fallback in case the bundled PDF is missing. */
    const PRIVACY_DOC_URL = 'https://plugins.shahinurislam.com/docs/privacy-data-guide.pdf';

    public function register()
    {
        add_action('admin_post_si_save_global_settings', [$this, 'saveSettings']);
    }

    /**
     * Bundled Privacy & Data Guide (PDF) shipped inside the plugin:
     * {plugin}/docs/privacy-data-guide.pdf — falls back to the remote copy if absent.
     */
    private function privacyDocUrl()
    {
        $localPath = SI_SIMPLE_FORMS_DIR . 'docs/privacy-data-guide.pdf';
        if (file_exists($localPath)) {
            return SI_SIMPLE_FORMS_URL . 'docs/privacy-data-guide.pdf';
        }
        return self::PRIVACY_DOC_URL;
    }

    public function saveSettings()
    {
        if (!isset($_POST['si_settings_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['si_settings_nonce'])), 'si_save_global_settings')) {
            wp_die(esc_html__('Security check failed.', 'si-simple-forms'));
        }
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('You do not have permission to do this.', 'si-simple-forms'));
        }

        update_option('si_sender_name', sanitize_text_field(wp_unslash($_POST['si_sender_name'] ?? 'SI Simple Forms')));
        update_option('si_sender_email', sanitize_email(wp_unslash($_POST['si_sender_email'] ?? get_bloginfo('admin_email'))));
        update_option('si_turnstile_site_key', sanitize_text_field(wp_unslash($_POST['si_turnstile_site_key'] ?? '')));
        update_option('si_turnstile_secret_key', sanitize_text_field(wp_unslash($_POST['si_turnstile_secret_key'] ?? '')));
        update_option('si_rest_api_enabled', !empty($_POST['si_rest_api_enabled']));
        update_option('si_mailpoet_sync_enabled', !empty($_POST['si_mailpoet_sync_enabled']));
        update_option('si_rate_limiting_enabled', !empty($_POST['si_rate_limiting_enabled']));
        update_option('si_debug_logging_enabled', !empty($_POST['si_debug_logging_enabled']));
        update_option('si_global_custom_css', wp_strip_all_tags(wp_unslash($_POST['si_global_custom_css'] ?? '')));
        update_option('si_delete_data_on_uninstall', sanitize_text_field(wp_unslash($_POST['si_delete_data_on_uninstall'] ?? 'no')));

        // Privacy & Data Retention
        update_option('si_retention_messages', sanitize_text_field(wp_unslash($_POST['si_retention_messages'] ?? '180')));
        update_option('si_retention_subscribers', sanitize_text_field(wp_unslash($_POST['si_retention_subscribers'] ?? 'never')));
        update_option('si_retention_auto_delete', !empty($_POST['si_retention_auto_delete']));

        wp_safe_redirect(add_query_arg('si_saved', '1', admin_url('admin.php?page=si-simple-forms-settings')));
        exit;
    }

    public function render()
    {
        $senderName = get_option('si_sender_name', 'SI Simple Forms');
        $senderEmail = get_option('si_sender_email', get_bloginfo('admin_email'));
        $turnstileSite = get_option('si_turnstile_site_key', '');
        $turnstileSecret = get_option('si_turnstile_secret_key', '');
        $restApi = get_option('si_rest_api_enabled', true);
        $mailpoetSync = get_option('si_mailpoet_sync_enabled', true);
        $rateLimiting = get_option('si_rate_limiting_enabled', true);
        $debugLogging = get_option('si_debug_logging_enabled', false);
        $customCss = get_option('si_global_custom_css', "/* Custom form CSS styles */\n.si-simple-form {\n  margin-bottom: 1.5rem;\n}");
        $deleteOnUninstall = get_option('si_delete_data_on_uninstall', 'no');
        $retentionMessages = get_option('si_retention_messages', '180');
        $retentionSubscribers = get_option('si_retention_subscribers', 'never');
        $retentionAutoDelete = get_option('si_retention_auto_delete', true);

        Layout::open('si-simple-forms-settings', __('SI Simple Forms Global Settings', 'si-simple-forms'), __('Configure global sender identities, Cloudflare Turnstile anti-spam credentials, REST API status, and custom CSS.', 'si-simple-forms'));

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only post-save status flag for display only; no data is processed or written here.
        if (isset($_GET['si_saved'])) {
            echo '<div class="si-card" style="margin-bottom:16px;border-color:#bbf7d0;background:#f0fdf4;color:#166534;padding:12px 18px;font-size:13px;font-weight:600;">' . esc_html__('Settings saved successfully.', 'si-simple-forms') . '</div>';
        }

        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
        echo '<input type="hidden" name="action" value="si_save_global_settings" />';
        wp_nonce_field('si_save_global_settings', 'si_settings_nonce');

        // Default Email Sender Identity
        echo '<div class="si-settings-block">';
        echo '<h2><span class="dashicons dashicons-email"></span> ' . esc_html__('Default Email Sender Identity', 'si-simple-forms') . '</h2>';
        echo '<div class="si-field-row">';
        echo '<div class="si-field"><label>' . esc_html__('From Name', 'si-simple-forms') . '</label><input type="text" name="si_sender_name" value="' . esc_attr($senderName) . '" /></div>';
        echo '<div class="si-field"><label>' . esc_html__('From Email Address', 'si-simple-forms') . '</label><input type="email" name="si_sender_email" value="' . esc_attr($senderEmail) . '" /></div>';
        echo '</div>';
        echo '<p class="si-hint">' . esc_html__('These credentials are used in the From: header for outgoing auto-replies and notification emails.', 'si-simple-forms') . '</p>';
        echo '</div>';

        // Cloudflare Turnstile
        $turnstileConfigured = $turnstileSite !== '' && $turnstileSecret !== '';
        echo '<div class="si-settings-block">';
        echo '<h2 style="display:flex;justify-content:space-between;"><span><span class="dashicons dashicons-shield"></span> ' . esc_html__('Cloudflare Turnstile Integration', 'si-simple-forms') . '</span><span class="si-status-pill">' . ($turnstileConfigured ? esc_html__('Active Protection', 'si-simple-forms') : esc_html__('Not Configured', 'si-simple-forms')) . '</span></h2>';
        echo '<p class="si-hint">' . esc_html__('Protect your contact and subscription forms automatically against bots without frustrating users with traditional CAPTCHAs. Get your keys at the Cloudflare Turnstile dashboard.', 'si-simple-forms') . '</p>';
        echo '<div class="si-field-row">';
        echo '<div class="si-field"><label>' . esc_html__('Site Key', 'si-simple-forms') . '</label><input type="text" name="si_turnstile_site_key" value="' . esc_attr($turnstileSite) . '" /></div>';
        echo '<div class="si-field"><label>' . esc_html__('Secret Key', 'si-simple-forms') . '</label><input type="password" name="si_turnstile_secret_key" value="' . esc_attr($turnstileSecret) . '" /></div>';
        echo '</div>';
        echo '</div>';

        // REST API & Security Controls
        echo '<div class="si-settings-block">';
        echo '<h2><span class="dashicons dashicons-admin-site-alt3"></span> ' . esc_html__('REST API & Security Controls', 'si-simple-forms') . '</h2>';
        echo '<div class="si-toggle-grid">';
        $this->toggleCard('si_rest_api_enabled', $restApi, __('REST API Endpoints', 'si-simple-forms'), __('Expose /wp-json/si-simple-forms/v1/ endpoints', 'si-simple-forms'));
        $this->toggleCard('si_mailpoet_sync_enabled', $mailpoetSync, __('MailPoet Syncing', 'si-simple-forms'), __('Auto-sync subscribers with MailPoet lists', 'si-simple-forms'));
        $this->toggleCard('si_rate_limiting_enabled', $rateLimiting, __('Enable Rate Limiting', 'si-simple-forms'), __('Limit max 5 submissions per 10 mins per IP', 'si-simple-forms'));
        $this->toggleCard('si_debug_logging_enabled', $debugLogging, __('Enable Debug Logging', 'si-simple-forms'), __('Write submission and sync events to debug.log', 'si-simple-forms'));
        echo '</div></div>';

        // Global Custom Form CSS
        echo '<div class="si-settings-block">';
        echo '<h2><span class="dashicons dashicons-editor-code"></span> ' . esc_html__('Global Custom Form CSS', 'si-simple-forms') . '</h2>';
        echo '<div class="si-field"><label>' . esc_html__('CSS Overrides', 'si-simple-forms') . '</label><textarea rows="6" name="si_global_custom_css" class="si-code">' . esc_textarea($customCss) . '</textarea></div>';
        echo '<p class="si-hint">' . esc_html__('Inject custom CSS rule blocks for all frontend form instances.', 'si-simple-forms') . '</p>';
        echo '</div>';

        // Delete Data on Uninstall
        echo '<div class="si-settings-block">';
        echo '<h2><span class="dashicons dashicons-trash"></span> ' . esc_html__('Delete Data on Uninstall', 'si-simple-forms') . '</h2>';
        /* translators: %1$s: messages table name. %2$s: subscribers table name. */
        echo '<p class="si-hint">' . sprintf(esc_html__('When enabled, deleting this plugin will permanently drop custom database tables (%1$s & %2$s) and clear options.', 'si-simple-forms'), '<code class="si-code-inline">wp_si_messages</code>', '<code class="si-code-inline">wp_si_subscribers</code>') . '</p>';
        echo '<label class="si-radio-line"><input type="radio" name="si_delete_data_on_uninstall" value="yes" ' . checked($deleteOnUninstall, 'yes', false) . ' /> ' . esc_html__('Yes, purge database tables on uninstall', 'si-simple-forms') . '</label>';
        echo '<label class="si-radio-line"><input type="radio" name="si_delete_data_on_uninstall" value="no" ' . checked($deleteOnUninstall, 'no', false) . ' /> ' . esc_html__('No, preserve messages & subscribers (Recommended)', 'si-simple-forms') . '</label>';
        echo '</div>';

        // Privacy & Data (retention + documentation) — anchor target for builder consent links
        echo '<div class="si-settings-block" id="si-privacy-data">';
        echo '<h2><span class="dashicons dashicons-privacy"></span> ' . esc_html__('Privacy & Data', 'si-simple-forms') . '</h2>';
        echo '<div class="si-builder-label">' . esc_html__('Data Retention', 'si-simple-forms') . '</div>';
        echo '<div class="si-field-row">';
        echo '<div class="si-field"><label>' . esc_html__('Contact Messages', 'si-simple-forms') . '</label><select name="si_retention_messages">';
        foreach (['30' => __('30 Days', 'si-simple-forms'), '60' => __('60 Days', 'si-simple-forms'), '90' => __('90 Days', 'si-simple-forms'), '180' => __('180 Days', 'si-simple-forms'), '365' => __('365 Days', 'si-simple-forms'), 'never' => __('Never Delete', 'si-simple-forms')] as $val => $label) {
            echo '<option value="' . esc_attr($val) . '" ' . selected($retentionMessages, $val, false) . '>' . esc_html($label) . '</option>';
        }
        echo '</select></div>';
        echo '<div class="si-field"><label>' . esc_html__('Subscribers', 'si-simple-forms') . '</label><select name="si_retention_subscribers">';
        foreach (['30' => __('30 Days', 'si-simple-forms'), '60' => __('60 Days', 'si-simple-forms'), '90' => __('90 Days', 'si-simple-forms'), '180' => __('180 Days', 'si-simple-forms'), '365' => __('365 Days', 'si-simple-forms'), 'never' => __('Never Delete', 'si-simple-forms')] as $val => $label) {
            echo '<option value="' . esc_attr($val) . '" ' . selected($retentionSubscribers, $val, false) . '>' . esc_html($label) . '</option>';
        }
        echo '</select></div>';
        echo '</div>';
        echo '<label class="si-check-line"><input type="checkbox" name="si_retention_auto_delete" value="1" ' . checked((bool) $retentionAutoDelete, true, false) . ' /> ' . esc_html__('Automatically delete expired records', 'si-simple-forms') . '</label>';
        echo '<div class="si-builder-label">' . esc_html__('Documentation', 'si-simple-forms') . '</div>';
        echo '<div class="si-privacy-doc-row"><span>&#128196; ' . esc_html__('Privacy & Data Guide (PDF)', 'si-simple-forms') . '</span><a href="' . esc_url($this->privacyDocUrl()) . '" target="_blank" rel="noopener">' . esc_html__('View Documentation →', 'si-simple-forms') . '</a></div>';
        echo '</div>';

        echo '<div style="text-align:right;"><button type="submit" class="si-btn si-btn-primary"><span class="dashicons dashicons-saved"></span> ' . esc_html__('Save Global Settings', 'si-simple-forms') . '</button></div>';

        echo '</form>';

        Layout::close();
    }

    private function toggleCard($name, $checked, $title, $desc)
    {
        echo '<div class="si-toggle-card">';
        echo '<div><strong>' . esc_html($title) . '</strong><br/><span>' . esc_html($desc) . '</span></div>';
        echo '<label class="si-switch"><input type="checkbox" name="' . esc_attr($name) . '" ' . checked($checked, true, false) . ' /><span class="slider"></span></label>';
        echo '</div>';
    }
}