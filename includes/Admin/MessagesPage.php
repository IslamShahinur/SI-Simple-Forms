<?php
namespace SI\SimpleForms\Admin;

defined('ABSPATH') || exit;

use SI\SimpleForms\Core\DbHelper;
use SI\SimpleForms\Core\Logger;

final class MessagesPage
{
    public function register()
    {
        add_action('admin_post_si_delete_message', [$this, 'handleDelete']);
    }

    public function handleDelete()
    {
        if (!isset($_GET['_wpnonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['_wpnonce'])), 'si_delete_message')) {
            wp_die(esc_html__('Security check failed.', 'si-simple-forms'));
        }
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('You do not have permission to do this.', 'si-simple-forms'));
        }
        global $wpdb;
        $id = isset($_GET['id']) ? absint($_GET['id']) : 0;
        if ($id > 0) {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- deleting a single plugin-owned message row by validated integer ID.
            $wpdb->delete($wpdb->prefix . 'si_messages', ['id' => $id], ['%d']);
            DbHelper::invalidate($wpdb->prefix . 'si_messages');
        }
        wp_safe_redirect(admin_url('admin.php?page=si-simple-forms-messages'));
        exit;
    }

    public function render()
    {
        global $wpdb;
        $rows = [];
        try {
            $rows = DbHelper::getResults($wpdb->prefix . 'si_messages', 'id DESC', 200);
        } catch (\Throwable $e) {
            Logger::log('Messages Query', $e->getMessage());
        }

        Layout::open('si-simple-forms-messages');

        echo '<div class="si-table-card">';
        echo '<div class="si-table-head">';
        echo '<span class="si-table-icon dashicons dashicons-format-chat"></span>';
        echo '<div><h2>' . esc_html__('Messages Inbox', 'si-simple-forms') . '</h2>';
        /* translators: %1$d: number of stored submissions. %2$s: database table name. */
        echo '<p>' . sprintf(esc_html__('Total %1$d form submissions received. Stored securely in %2$s.', 'si-simple-forms'), count($rows), '<code class="si-code-inline">wp_si_messages</code>') . '</p></div>';
        echo '<span class="si-topbar-spacer"></span>';
        echo '<a class="si-btn si-btn-ghost si-btn-sm" href="' . esc_url(wp_nonce_url(admin_url('admin-post.php?action=si_export_csv&type=messages'), 'si_export_csv')) . '"><span class="dashicons dashicons-download"></span>' . esc_html__('Export CSV', 'si-simple-forms') . '</a>';
        echo '</div>';

        echo '<div class="si-search-row">';
        echo '<input type="text" class="si-search-input" data-si-search="si-messages-table" placeholder="' . esc_attr__('Search messages by name, email, or subject...', 'si-simple-forms') . '" />';
        /* translators: %d: number of matching rows currently shown. */
        echo '<span class="si-count-label">' . sprintf(esc_html__('Showing %d entries', 'si-simple-forms'), count($rows)) . '</span>';
        echo '</div>';

        echo '<table class="si-data-table" id="si-messages-table">';
        echo '<thead><tr><th>ID</th><th>' . esc_html__('Name', 'si-simple-forms') . '</th><th>' . esc_html__('Email', 'si-simple-forms') . '</th><th>' . esc_html__('Subject', 'si-simple-forms') . '</th><th>' . esc_html__('Date', 'si-simple-forms') . '</th><th>' . esc_html__('Action', 'si-simple-forms') . '</th></tr></thead><tbody>';
        if (empty($rows)) {
            echo '<tr><td colspan="6" style="text-align:center;color:#94a3b8;">' . esc_html__('No messages submitted yet.', 'si-simple-forms') . '</td></tr>';
        } else {
            foreach ($rows as $r) {
                $search = strtolower($r['name'] . ' ' . $r['email'] . ' ' . $r['subject']);
                echo '<tr data-search="' . esc_attr($search) . '">';
                echo '<td>' . esc_html($r['id']) . '</td>';
                echo '<td><strong>' . esc_html($r['name']) . '</strong></td>';
                echo '<td>' . esc_html($r['email']) . '</td>';
                echo '<td>' . esc_html($r['subject']) . '</td>';
                echo '<td>' . esc_html($r['created_at']) . '</td>';
                echo '<td class="si-row-actions">';
                echo '<a class="si-view" href="#" data-view-message="' . esc_attr(wp_json_encode($r)) . '">&#128065; ' . esc_html__('View', 'si-simple-forms') . '</a>';
                echo '<a class="si-delete" href="' . esc_url(wp_nonce_url(admin_url('admin-post.php?action=si_delete_message&id=' . (int) $r['id']), 'si_delete_message')) . '">&#128465;</a>';
                echo '</td>';
                echo '</tr>';
            }
        }
        echo '</tbody></table>';
        echo '</div>';

        Layout::close();
    }
}
