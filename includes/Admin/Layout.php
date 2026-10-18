<?php
namespace SI\SimpleForms\Admin;

defined('ABSPATH') || exit;

use SI\SimpleForms\Core\DbHelper;
use SI\SimpleForms\Core\Logger;

final class Layout
{
    const SUPPORT_URL = 'https://plugins.shahinurislam.com/support/';

    /** @var array<int,array<string,mixed>> */
    private static function navItems()
    {
        global $wpdb;
        $msgCount = 0;
        $subCount = 0;
        try {
            $msgCount = DbHelper::count($wpdb->prefix . 'si_messages', "status = 'unread'");
            $subCount = DbHelper::count($wpdb->prefix . 'si_subscribers');
        } catch (\Throwable $e) {
            Logger::log('Nav Count', $e->getMessage());
        }

        return [
            'management' => [
                ['slug' => 'si-simple-forms', 'label' => __('Dashboard', 'si-simple-forms'), 'icon' => 'dashicons-grid-view'],
                ['slug' => 'si-simple-forms-contact-builder', 'label' => __('Contact Form Builder', 'si-simple-forms'), 'icon' => 'dashicons-media-document'],
                ['slug' => 'si-simple-forms-my-contact-forms', 'label' => __('My Contact Forms', 'si-simple-forms'), 'icon' => 'dashicons-portfolio'],
                ['slug' => 'si-simple-forms-subscribe-builder', 'label' => __('Subscription Forms', 'si-simple-forms'), 'icon' => 'dashicons-email-alt'],
                ['slug' => 'si-simple-forms-my-subscription-forms', 'label' => __('My Subscription Forms', 'si-simple-forms'), 'icon' => 'dashicons-megaphone'],
                ['slug' => 'si-simple-forms-messages', 'label' => __('Messages', 'si-simple-forms'), 'icon' => 'dashicons-format-chat', 'badge' => $msgCount],
                ['slug' => 'si-simple-forms-subscribers', 'label' => __('Subscribers', 'si-simple-forms'), 'icon' => 'dashicons-groups', 'badge' => $subCount],
                ['slug' => 'si-simple-forms-settings', 'label' => __('Settings', 'si-simple-forms'), 'icon' => 'dashicons-admin-generic'],
            ],
            'tools' => [
                ['slug' => 'si-simple-forms-how-to', 'label' => __('How to Guide', 'si-simple-forms'), 'icon' => 'dashicons-editor-help'],
            ],
        ];
    }

    public static function currentScreenSlugs()
    {
        $groups = self::navItems();
        $slugs = [];
        foreach ($groups as $group) {
            foreach ($group as $item) {
                $slugs[] = $item['slug'];
            }
        }
        return $slugs;
    }

    public static function open($activeSlug, $pageTitle = '', $pageDesc = '')
    {
        $groups = self::navItems();
        $mailpoetConnected = class_exists('\MailPoet\API\API');
        $version = defined('SI_SIMPLE_FORMS_VERSION') ? SI_SIMPLE_FORMS_VERSION : '1.0.3';

        echo '<div class="si-app">';

        // ---------- Sidebar (left pane) ----------
        echo '<aside class="si-sidebar">';
        echo '<div class="si-sidebar-brand">';
        echo '<span class="si-brand-mark">SI</span>';
        echo '<div><div class="si-brand-title">SI Simple Forms</div><div class="si-brand-sub">Version ' . esc_html(SI_SIMPLE_FORMS_VERSION) . '</div></div>';
        echo '<span class="si-sidebar-shield dashicons dashicons-shield-alt"></span>';
        echo '</div>';

        echo '<div class="si-nav-label">' . esc_html__('Plugin Management', 'si-simple-forms') . '</div>';
        echo '<div class="si-nav">';
        foreach ($groups['management'] as $item) {
            self::renderNavItem($item, $activeSlug);
        }
        echo '</div>';

        echo '<div class="si-nav" style="margin-top:6px;">';
        foreach ($groups['tools'] as $item) {
            self::renderNavItem($item, $activeSlug, true);
        }
        echo '</div>';

        // Left pane footer: Contributor info + Exit to WordPress + Need Support?
        echo '<div class="si-sidebar-footer">';
        echo '<div>' . esc_html__('Contributor:', 'si-simple-forms') . ' <strong>Shahinur Islam</strong></div>';
        echo '<div>' . esc_html__('WP Standard:', 'si-simple-forms') . ' <strong style="color:#34d399;">7.1 Compatible</strong></div>';
        echo '<a class="si-support-btn" style="background:#1e293b;color:#e2e8f0;" href="' . esc_url(admin_url('index.php')) . '"><span class="dashicons dashicons-arrow-left-alt2"></span> ' . esc_html__('Exit to WordPress', 'si-simple-forms') . '</a>';
        echo '<a class="si-support-btn" href="' . esc_url(self::SUPPORT_URL) . '" target="_blank" rel="noopener">' . esc_html__('Need Support?', 'si-simple-forms') . '</a>';
        echo '</div>';
        echo '</aside>';

        // ---------- Main column ----------
        echo '<div class="si-main">';

        echo '<div class="si-topbar">';

        // Left: elegant golden SI brand + version (support link removed from header)
        echo '<div class="si-topbar-brand">';
        echo '<span class="si-brand-mark si-brand-mark-gold">SI</span>';
        echo '<div>';
        echo '<span class="si-topbar-brand-title">' . esc_html__('SI Simple Forms', 'si-simple-forms') . '<span class="si-topbar-brand-ver">v' . esc_html($version) . '</span></span>';
        echo '</div>';
        echo '</div>';

        echo '<span class="si-topbar-spacer"></span>';

        // Middle: two live sandboxes
        echo '<div class="si-topbar-center">';
        echo '<a class="si-pill" href="' . esc_url(admin_url('admin.php?page=si-simple-forms-sandbox&type=contact')) . '"><span class="dashicons dashicons-media-document"></span>' . esc_html__('Contact Form Live Sandbox', 'si-simple-forms') . '</a>';
        echo '<a class="si-pill" href="' . esc_url(admin_url('admin.php?page=si-simple-forms-sandbox&type=subscribe')) . '"><span class="dashicons dashicons-email-alt"></span>' . esc_html__('Subscription Form Live Sandbox', 'si-simple-forms') . '</a>';
        echo '</div>';

        echo '<span class="si-topbar-spacer"></span>';

        // Right: MailPoet auto-detection only
        echo '<span class="si-pill si-pill-status">' . esc_html__('MailPoet:', 'si-simple-forms') . '&nbsp;' . ($mailpoetConnected ? esc_html__('Connected', 'si-simple-forms') : esc_html__('Not Installed', 'si-simple-forms')) . '</span>';
        echo '</div>';

        echo '<div class="si-content">';

        if ($pageTitle !== '') {
            echo '<h1 class="si-page-title">' . esc_html($pageTitle) . '</h1>';
            if ($pageDesc !== '') {
                echo '<p class="si-page-desc">' . esc_html($pageDesc) . '</p>';
            }
        }
    }

    public static function close()
    {
        echo '</div>'; // .si-content
        echo '</div>'; // .si-main
        echo '</div>'; // .si-app
    }

    private static function renderNavItem($item, $activeSlug, $isTool = false)
    {
        $isActive = ($item['slug'] === $activeSlug);
        $classes = 'si-nav-item' . ($isActive ? ' is-active' : '') . ($isTool ? ' si-nav-tool' : '');
        echo '<a class="' . esc_attr($classes) . '" href="' . esc_url(admin_url('admin.php?page=' . $item['slug'])) . '">';
        echo '<span class="dashicons ' . esc_attr($item['icon']) . '"></span>';
        echo '<span>' . esc_html($item['label']) . '</span>';
        if (!empty($item['badge'])) {
            echo '<span class="si-nav-badge">' . (int) $item['badge'] . '</span>';
        }
        echo '</a>';
    }
}