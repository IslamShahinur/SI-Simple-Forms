<?php
namespace SI\SimpleForms\Admin;

defined('ABSPATH') || exit;

final class ShortcodesPage
{
    public function register()
    {
    }

    public function render()
    {
        Layout::open('si-simple-forms-shortcodes', __('Shortcode Generator & Documentation', 'si-simple-forms'), __('Copy shortcodes or PHP template code to display forms anywhere on your WordPress site.', 'si-simple-forms'));

        echo '<div class="si-grid si-grid-2">';

        echo '<div class="si-card">';
        echo '<h2 style="margin-top:0;font-size:15px;color:#2563eb;">&#128221; ' . esc_html__('Contact Form Shortcode', 'si-simple-forms') . '</h2>';
        echo '<p style="font-size:12.5px;color:#64748b;">' . esc_html__('Renders the responsive contact form with Turnstile anti-spam protection.', 'si-simple-forms') . '</p>';
        echo '<code class="si-code-view" style="display:block;margin:12px 0;">[si_contact_form]</code>';
        echo '<div style="font-size:11.5px;font-weight:700;margin-bottom:4px;">' . esc_html__('PHP Template Function:', 'si-simple-forms') . '</div>';
        echo '<code class="si-code-inline">&lt;?php echo do_shortcode(\'[si_contact_form]\'); ?&gt;</code>';
        echo '</div>';

        echo '<div class="si-card">';
        echo '<h2 style="margin-top:0;font-size:15px;color:#4f46e5;">&#9993;&#65039; ' . esc_html__('Subscribe Form Shortcode', 'si-simple-forms') . '</h2>';
        echo '<p style="font-size:12.5px;color:#64748b;">' . esc_html__('Renders the newsletter subscription form with MailPoet auto-sync.', 'si-simple-forms') . '</p>';
        echo '<code class="si-code-view" style="display:block;margin:12px 0;">[si_subscribe_form]</code>';
        echo '<div style="font-size:11.5px;font-weight:700;margin-bottom:4px;">' . esc_html__('PHP Template Function:', 'si-simple-forms') . '</div>';
        echo '<code class="si-code-inline">&lt;?php echo do_shortcode(\'[si_subscribe_form]\'); ?&gt;</code>';
        echo '</div>';

        echo '</div>';

        Layout::close();
    }
}
