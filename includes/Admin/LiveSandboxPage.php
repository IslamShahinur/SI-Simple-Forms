<?php
namespace SI\SimpleForms\Admin;

defined('ABSPATH') || exit;

final class LiveSandboxPage
{
    public function register()
    {
    }

    public function render()
    {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('You do not have permission to access this page.', 'si-simple-forms'));
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only view-switch between the two sandbox previews; no data is processed.
        $type = (isset($_GET['type']) && sanitize_text_field(wp_unslash($_GET['type'])) === 'subscribe') ? 'subscribe' : 'contact';
        $isContact = ($type === 'contact');

        Layout::open('si-simple-forms-sandbox');

        $siteName = get_bloginfo('name') ?: "Shahinur's WordPress Site";
        $year = gmdate('Y');

        echo '<div class="si-sandbox-frame">';

        echo '<div class="si-sandbox-nav">';
        echo '<span class="site-name">' . esc_html($siteName) . '</span>';
        if ($isContact) {
            echo '<nav><a href="#">' . esc_html__('Home', 'si-simple-forms') . '</a><a href="#" class="current">' . esc_html__('Contact Us', 'si-simple-forms') . '</a><a href="#">' . esc_html__('Blog', 'si-simple-forms') . '</a><a href="#">' . esc_html__('About', 'si-simple-forms') . '</a></nav>';
        } else {
            echo '<nav><a href="#">' . esc_html__('Home', 'si-simple-forms') . '</a><a href="#" class="current">' . esc_html__('Newsletter', 'si-simple-forms') . '</a><a href="#">' . esc_html__('Blog', 'si-simple-forms') . '</a><a href="#">' . esc_html__('About', 'si-simple-forms') . '</a></nav>';
        }
        echo '</div>';

        echo '<div class="si-sandbox-hero">';
        if ($isContact) {
            echo '<h1>' . esc_html__('Get in Touch with Us', 'si-simple-forms') . '</h1>';
            echo '<p>' . esc_html__('We respond to all inquiries within 24 hours. Fill out the form shortcode below to reach our team directly.', 'si-simple-forms') . '</p>';
        } else {
            echo '<h1>' . esc_html__('Subscribe to our Newsletter', 'si-simple-forms') . '</h1>';
            echo '<p>' . esc_html__('Stay updated with our latest newsletter and announcements. The subscription form shortcode below renders exactly as configured in the builder.', 'si-simple-forms') . '</p>';
        }
        echo '</div>';

        echo '<div class="si-shortcode-block">';
        echo '<div class="si-shortcode-block-head"><span>' . ($isContact ? esc_html__('Contact Form Shortcode Output Block', 'si-simple-forms') : esc_html__('Subscription Form Shortcode Output Block', 'si-simple-forms')) . '</span><span>' . esc_html__('SI Simple Forms Native Engine', 'si-simple-forms') . '</span></div>';
        echo $isContact ? do_shortcode('[si_contact_form]') : do_shortcode('[si_subscribe_form]');
        echo '</div>';

        echo '<div class="si-sandbox-foot">&copy; ' . esc_html($year) . ' ' . esc_html($siteName) . ' &bull; ' . esc_html__('Powered by SI Simple Forms v', 'si-simple-forms') . esc_html(SI_SIMPLE_FORMS_VERSION) . '</div>';

        echo '</div>';

        Layout::close();
    }
}