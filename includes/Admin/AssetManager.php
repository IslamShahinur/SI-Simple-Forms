<?php
namespace SI\SimpleForms\Admin;

defined('ABSPATH') || exit;

final class AssetManager
{
    public function register()
    {
        add_action('admin_enqueue_scripts', [$this, 'enqueue']);
        add_filter('admin_body_class', [$this, 'bodyClass']);
        add_action('admin_menu', [$this, 'maybeHideAdminBar'], 1);
        add_action('wp_enqueue_scripts', [$this, 'enqueueFrontend']);
    }

    private function isOurScreen()
    {
        if (!function_exists('get_current_screen')) {
            return false;
        }
        $screen = get_current_screen();
        if (!$screen) {
            return false;
        }
        return strpos($screen->id, 'si-simple-forms') !== false;
    }

    public function maybeHideAdminBar()
    {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only check of the current admin page slug; no data is processed.
        if (isset($_GET['page']) && strpos(sanitize_text_field(wp_unslash($_GET['page'])), 'si-simple-forms') === 0) {
            show_admin_bar(false);
        }
    }

    public function bodyClass($classes)
    {
        if ($this->isOurScreen()) {
            $classes .= ' si-app-shell ';
        }
        return $classes;
    }

    public function enqueue($hook)
    {
        if (!$this->isOurScreen()) {
            return;
        }
        wp_enqueue_style('dashicons');
        wp_enqueue_style('si-simple-forms-admin', SI_SIMPLE_FORMS_URL . 'assets/css/admin.css', [], SI_SIMPLE_FORMS_VERSION);
        wp_enqueue_style('si-simple-forms-frontend', SI_SIMPLE_FORMS_URL . 'assets/css/frontend.css', [], SI_SIMPLE_FORMS_VERSION);
        wp_enqueue_script('si-simple-forms-admin', SI_SIMPLE_FORMS_URL . 'assets/js/admin.js', [], SI_SIMPLE_FORMS_VERSION, true);
        wp_enqueue_script('si-simple-forms-builder-contact', SI_SIMPLE_FORMS_URL . 'assets/js/builder-contact.js', [], SI_SIMPLE_FORMS_VERSION, true);
        wp_enqueue_script('si-simple-forms-builder-subscribe', SI_SIMPLE_FORMS_URL . 'assets/js/builder-subscribe.js', [], SI_SIMPLE_FORMS_VERSION, true);
    }

    public function enqueueFrontend()
    {
        wp_enqueue_style('si-simple-forms-frontend', SI_SIMPLE_FORMS_URL . 'assets/css/frontend.css', [], SI_SIMPLE_FORMS_VERSION);
    }
}
