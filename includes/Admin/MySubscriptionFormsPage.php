<?php
namespace SI\SimpleForms\Admin;

defined('ABSPATH') || exit;

use SI\SimpleForms\Forms\FormRegistry;

final class MySubscriptionFormsPage
{
    public function register()
    {
        add_action('admin_post_si_delete_subscribe_form', [$this, 'handleDelete']);
    }

    public function handleDelete()
    {
        if (!isset($_GET['_wpnonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['_wpnonce'])), 'si_delete_subscribe_form')) {
            wp_die(esc_html__('Security check failed.', 'si-simple-forms'));
        }
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('You do not have permission to do this.', 'si-simple-forms'));
        }
        $id = isset($_GET['id']) ? absint($_GET['id']) : 0;
        if ($id > 0) {
            FormRegistry::delete(FormRegistry::SUBSCRIBE, $id);
        }
        wp_safe_redirect(admin_url('admin.php?page=si-simple-forms-my-subscription-forms'));
        exit;
    }

    public function render()
    {
        $rows = FormRegistry::all(FormRegistry::SUBSCRIBE);

        Layout::open('si-simple-forms-my-subscription-forms', __('My Subscription Forms', 'si-simple-forms'), __('Every saved subscription form with its auto-generated shortcode. Copy, edit, or delete forms here.', 'si-simple-forms'));

        echo '<div class="si-table-card">';
        echo '<div class="si-table-head">';
        echo '<span class="si-table-icon dashicons dashicons-email-alt"></span>';
        echo '<div><h2>' . esc_html__('Subscription Forms Directory', 'si-simple-forms') . '</h2>';
        /* translators: %d: numeric count. */
        echo '<p>' . sprintf(esc_html__('Total %d saved subscription forms. Each form gets a unique, permanent shortcode.', 'si-simple-forms'), count($rows)) . '</p></div>';
        echo '<span class="si-topbar-spacer"></span>';
        echo '<a class="si-btn si-btn-primary si-btn-sm" href="' . esc_url(admin_url('admin.php?page=si-simple-forms-subscribe-builder')) . '"><span class="dashicons dashicons-plus-alt2"></span>' . esc_html__('New Subscription Form', 'si-simple-forms') . '</a>';
        echo '</div>';

        echo '<div class="si-search-row">';
        echo '<input type="text" class="si-search-input" data-si-search="si-subscribe-forms-table" placeholder="' . esc_attr__('Search forms by name or ID...', 'si-simple-forms') . '" />';
        /* translators: %d: numeric count. */
        echo '<span class="si-count-label">' . sprintf(esc_html__('Showing %d entries', 'si-simple-forms'), count($rows)) . '</span>';
        echo '</div>';

        echo '<table class="si-data-table" id="si-subscribe-forms-table">';
        echo '<thead><tr><th>' . esc_html__('Form ID', 'si-simple-forms') . '</th><th>' . esc_html__('Form Name', 'si-simple-forms') . '</th><th>' . esc_html__('Shortcode', 'si-simple-forms') . '</th><th>' . esc_html__('Updated', 'si-simple-forms') . '</th><th>' . esc_html__('Action', 'si-simple-forms') . '</th></tr></thead><tbody>';

        if (empty($rows)) {
            echo '<tr><td colspan="5" style="text-align:center;color:#94a3b8;">' . esc_html__('No subscription forms saved yet. Use the Subscription Form Builder and click Save your Form.', 'si-simple-forms') . '</td></tr>';
        } else {
            foreach ($rows as $row) {
                $id        = (int) $row['id'];
                $shortcode = '[si_subscribe_form id="' . $id . '"]';
                $search    = strtolower($id . ' ' . $row['name']);
                echo '<tr data-search="' . esc_attr($search) . '">';
                echo '<td><strong>#' . esc_html($id) . '</strong></td>';
                echo '<td><strong>' . esc_html($row['name']) . '</strong></td>';
                echo '<td><code class="si-code-inline">' . esc_html($shortcode) . '</code>' .
                     '<button type="button" class="si-btn si-btn-ghost si-btn-sm si-copy-btn" data-si-copy="' . esc_attr($shortcode) . '"><span class="dashicons dashicons-clipboard"></span> ' . esc_html__('Copy', 'si-simple-forms') . '</button></td>';
                echo '<td>' . esc_html($row['updated_at'] ?? '') . '</td>';
                echo '<td class="si-row-actions">';
                echo '<a class="si-view" href="' . esc_url(admin_url('admin.php?page=si-simple-forms-subscribe-builder&edit_form=' . $id)) . '">&#9998; ' . esc_html__('Edit', 'si-simple-forms') . '</a>';
                echo '<a class="si-delete" href="' . esc_url(wp_nonce_url(admin_url('admin-post.php?action=si_delete_subscribe_form&id=' . $id), 'si_delete_subscribe_form')) . '">&#128465; ' . esc_html__('Delete', 'si-simple-forms') . '</a>';
                echo '</td>';
                echo '</tr>';
            }
        }
        echo '</tbody></table>';
        echo '</div>';

        Layout::close();
    }
}