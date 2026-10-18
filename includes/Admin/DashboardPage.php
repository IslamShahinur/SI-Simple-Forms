<?php
namespace SI\SimpleForms\Admin;

defined('ABSPATH') || exit;

use SI\SimpleForms\Core\DbHelper;
use SI\SimpleForms\Core\Logger;

final class DashboardPage
{
    public function register()
    {
    }

    public function render()
    {
        global $wpdb;
        $msgCount = 0;
        $subCount = 0;
        $subCountPrevMonth = 0;
        try {
            $msgCount = DbHelper::count($wpdb->prefix . 'si_messages');
            $subCount = DbHelper::count($wpdb->prefix . 'si_subscribers');
            $subCountPrevMonth = DbHelper::count($wpdb->prefix . 'si_subscribers', "created_at < DATE_SUB(NOW(), INTERVAL 30 DAY)");
        } catch (\Throwable $e) {
            Logger::log('Dashboard Query', $e->getMessage());
        }

        $mailpoetConnected = class_exists('\MailPoet\API\API');
        $turnstileActive = get_option('si_turnstile_site_key', '') !== '' && get_option('si_turnstile_secret_key', '') !== '';
        $growth = ($subCountPrevMonth > 0) ? round((($subCount - $subCountPrevMonth) / max(1, $subCountPrevMonth)) * 100) : ($subCount > 0 ? 100 : 0);

        Layout::open('si-simple-forms');

        echo '<div class="si-hero">';
        echo '<div>';
        echo '<span class="si-hero-badge">&#9881; SI Simple Forms &bull; Version ' . esc_html(SI_SIMPLE_FORMS_VERSION) . '</span>';
        echo '<h1>' . esc_html__('Welcome!', 'si-simple-forms') . '</h1>';
        echo '<p style="color:#e2e8f0;font-weight:600;">' . esc_html__('Build Professional Contact & Newsletter Forms with Confidence.', 'si-simple-forms') . '</p>';
        echo '<p>' . esc_html__('Simple Forms helps you build professional contact and subscription forms with built-in spam protection, shortcode support, secure data handling, and a streamlined admin experience.', 'si-simple-forms') . '</p>';
        echo '</div>';
        echo '<div class="si-hero-actions">';
        echo '<a class="si-btn si-btn-primary" href="' . esc_url(admin_url('admin.php?page=si-simple-forms-contact-builder')) . '"><span class="dashicons dashicons-plus-alt2"></span>' . esc_html__('New Contact Form', 'si-simple-forms') . '</a>';
        echo '<a class="si-btn si-btn-indigo" href="' . esc_url(admin_url('admin.php?page=si-simple-forms-subscribe-builder')) . '"><span class="dashicons dashicons-plus-alt2"></span>' . esc_html__('New Subscribe Form', 'si-simple-forms') . '</a>';
        echo '</div>';
        echo '</div>';

        echo '<div class="si-grid si-grid-4">';

        echo '<div class="si-card"><div class="si-stat-label">' . esc_html__('Messages', 'si-simple-forms') . '<span class="si-stat-icon dashicons dashicons-format-chat"></span></div>';
        echo '<div class="si-stat-value">' . esc_html($msgCount) . '</div><div class="si-stat-sub muted">' . esc_html__('Active inbox &bull; Real-time storage', 'si-simple-forms') . '</div></div>';

        echo '<div class="si-card"><div class="si-stat-label">' . esc_html__('Subscribers', 'si-simple-forms') . '<span class="si-stat-icon dashicons dashicons-groups"></span></div>';
        echo '<div class="si-stat-value">' . esc_html($subCount) . '</div><div class="si-stat-sub">' . ($growth >= 0 ? '+' : '') . esc_html($growth) . '% ' . esc_html__('this month', 'si-simple-forms') . '</div></div>';

        echo '<div class="si-card"><div class="si-stat-label">' . esc_html__('MailPoet', 'si-simple-forms') . '<span class="si-stat-icon dashicons dashicons-email"></span></div>';
        echo '<div class="' . ($mailpoetConnected ? 'si-stat-status-ok' : 'si-stat-status-warn') . '">' . ($mailpoetConnected ? '&#10003; ' . esc_html__('Connected', 'si-simple-forms') : esc_html__('Not Installed', 'si-simple-forms')) . '</div>';
        echo '<div class="si-stat-sub muted">' . esc_html__('Syncing active list', 'si-simple-forms') . '</div></div>';

        echo '<div class="si-card"><div class="si-stat-label">' . esc_html__('System Status', 'si-simple-forms') . '<span class="si-stat-icon dashicons dashicons-chart-line"></span></div>';
        echo '<div class="si-stat-status-ok">&#9889; ' . esc_html__('Healthy', 'si-simple-forms') . '</div>';
        echo '<div class="si-stat-sub muted">' . esc_html__('All DB tables & REST API operational', 'si-simple-forms') . '</div></div>';

        echo '</div>';

        echo '<h2 class="si-section-title">' . esc_html__('Quick Actions', 'si-simple-forms') . '</h2>';
        echo '<div class="si-quick-grid">';

        $quick = [
            ['url' => 'admin.php?page=si-simple-forms-contact-builder', 'icon' => 'dashicons-plus-alt2', 'bg' => '#eef2ff', 'fg' => '#4338ca', 'title' => __('Contact Form', 'si-simple-forms')],
            ['url' => 'admin.php?page=si-simple-forms-subscribe-builder', 'icon' => 'dashicons-plus-alt2', 'bg' => '#f5f3ff', 'fg' => '#6d28d9', 'title' => __('Subscribe Form', 'si-simple-forms')],
            ['url' => 'admin.php?page=si-simple-forms-settings', 'icon' => 'dashicons-admin-generic', 'bg' => '#f1f5f9', 'fg' => '#334155', 'title' => __('Settings', 'si-simple-forms')],
            ['url' => 'admin.php?page=si-simple-forms-messages', 'icon' => 'dashicons-format-chat', 'bg' => '#eff6ff', 'fg' => '#2563eb', 'title' => __('Messages', 'si-simple-forms') . ' (' . $msgCount . ')'],
            ['url' => 'admin.php?page=si-simple-forms-subscribers', 'icon' => 'dashicons-groups', 'bg' => '#eff6ff', 'fg' => '#2563eb', 'title' => __('Subscribers', 'si-simple-forms') . ' (' . $subCount . ')'],
        ];
        foreach ($quick as $q) {
            echo '<a class="si-quick-card" href="' . esc_url(admin_url($q['url'])) . '">';
            echo '<span class="si-quick-icon" style="background:' . esc_attr($q['bg']) . ';color:' . esc_attr($q['fg']) . ';"><span class="dashicons ' . esc_attr($q['icon']) . '"></span></span>';
            echo '<span class="si-quick-title">' . esc_html($q['title']) . '</span>';
            echo '</a>';
        }
        echo '<a class="si-quick-card" href="' . esc_url(wp_nonce_url(admin_url('admin-post.php?action=si_export_csv&type=messages'), 'si_export_csv')) . '">';
        echo '<span class="si-quick-icon" style="background:#ecfdf5;color:#059669;"><span class="dashicons dashicons-download"></span></span>';
        echo '<span class="si-quick-title">' . esc_html__('Export CSV', 'si-simple-forms') . '</span></a>';
        echo '</div>';

        echo '<h2 class="si-section-title">' . esc_html__('System Overview', 'si-simple-forms') . '</h2>';
        echo '<div class="si-grid si-grid-2">';
        echo '<div class="si-card"><h3 style="margin-top:0;font-size:14px;">' . esc_html__('Cloudflare Turnstile Anti-Spam', 'si-simple-forms') . '</h3>';
        echo '<p style="font-size:12.5px;color:#64748b;">' . ($turnstileActive ? esc_html__('Active and protecting your forms against bots.', 'si-simple-forms') : esc_html__('Not configured yet. Add your keys in Settings to enable bot protection.', 'si-simple-forms')) . '</p>';
        echo '<a href="' . esc_url(admin_url('admin.php?page=si-simple-forms-settings')) . '" class="si-btn si-btn-ghost si-btn-sm">' . esc_html__('Configure', 'si-simple-forms') . '</a></div>';
        echo '<div class="si-card"><h3 style="margin-top:0;font-size:14px;">' . esc_html__('Live Frontend Sandboxes', 'si-simple-forms') . '</h3>';
        echo '<p style="font-size:12.5px;color:#64748b;">' . esc_html__('Preview exactly how your forms render on a live page before publishing.', 'si-simple-forms') . '</p>';
        echo '<a href="' . esc_url(admin_url('admin.php?page=si-simple-forms-sandbox&type=contact')) . '" class="si-btn si-btn-ghost si-btn-sm">' . esc_html__('Open Contact Sandbox', 'si-simple-forms') . '</a></div>';
        echo '</div>';

        Layout::close();
    }
}