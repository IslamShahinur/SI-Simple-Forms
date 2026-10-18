<?php
namespace SI\SimpleForms\Admin;

defined('ABSPATH') || exit;

use SI\SimpleForms\Core\DbHelper;
use SI\SimpleForms\Core\Logger;

final class SubscribersPage
{
    public function register()
    {
        add_action('admin_post_si_delete_subscriber', [$this, 'handleDelete']);
        add_action('admin_post_si_add_subscriber', [$this, 'handleAdd']);
    }

    public function handleDelete()
    {
        if (!isset($_GET['_wpnonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['_wpnonce'])), 'si_delete_subscriber')) {
            wp_die(esc_html__('Security check failed.', 'si-simple-forms'));
        }
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('You do not have permission to do this.', 'si-simple-forms'));
        }
        global $wpdb;
        $id = isset($_GET['id']) ? absint($_GET['id']) : 0;
        if ($id > 0) {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- deleting a single plugin-owned subscriber row by validated integer ID.
            $wpdb->delete($wpdb->prefix . 'si_subscribers', ['id' => $id], ['%d']);
            DbHelper::invalidate($wpdb->prefix . 'si_subscribers');
        }
        wp_safe_redirect(admin_url('admin.php?page=si-simple-forms-subscribers'));
        exit;
    }

    public function handleAdd()
    {
        if (!isset($_POST['si_add_subscriber_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['si_add_subscriber_nonce'])), 'si_add_subscriber')) {
            wp_die(esc_html__('Security check failed.', 'si-simple-forms'));
        }
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('You do not have permission to do this.', 'si-simple-forms'));
        }
        global $wpdb;
        $email = isset($_POST['email']) ? sanitize_email(wp_unslash($_POST['email'])) : '';
        if (is_email($email)) {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- inserting a single plugin-owned subscriber row from validated/sanitized input.
            $wpdb->insert($wpdb->prefix . 'si_subscribers', [
                'name' => isset($_POST['name']) ? sanitize_text_field(wp_unslash($_POST['name'])) : '',
                'email' => $email,
                'status' => 'Active',
                'tags' => 'Manual',
            ]);
            DbHelper::invalidate($wpdb->prefix . 'si_subscribers');
        }
        wp_safe_redirect(admin_url('admin.php?page=si-simple-forms-subscribers'));
        exit;
    }

    public function render()
    {
        global $wpdb;
        $rows = [];
        try {
            $rows = DbHelper::getResults($wpdb->prefix . 'si_subscribers', 'id DESC', 200);
        } catch (\Throwable $e) {
            Logger::log('Subscribers Query', $e->getMessage());
        }

        Layout::open('si-simple-forms-subscribers');

        echo '<div class="si-table-card">';
        echo '<div class="si-table-head">';
        echo '<span class="si-table-icon dashicons dashicons-groups"></span>';
        echo '<div><h2>' . esc_html__('Subscribers Directory', 'si-simple-forms') . '</h2>';
        /* translators: %1$d: number of active subscribers. %2$s: database table name. */
        echo '<p>' . sprintf(esc_html__('Total %1$d active newsletter subscribers in %2$s.', 'si-simple-forms'), count($rows), '<code class="si-code-inline">wp_si_subscribers</code>') . '</p></div>';
        echo '<span class="si-topbar-spacer"></span>';
        echo '<a class="si-btn si-btn-primary si-btn-sm" href="#" onclick="document.getElementById(\'si-add-sub-form\').style.display=\'flex\';return false;"><span class="dashicons dashicons-plus-alt2"></span>' . esc_html__('Add Subscriber', 'si-simple-forms') . '</a>';
        echo '<a class="si-btn si-btn-ghost si-btn-sm" href="' . esc_url(wp_nonce_url(admin_url('admin-post.php?action=si_export_csv&type=subscribers'), 'si_export_csv')) . '"><span class="dashicons dashicons-download"></span>' . esc_html__('Export CSV', 'si-simple-forms') . '</a>';
        echo '</div>';

        echo '<form id="si-add-sub-form" method="post" action="' . esc_url(admin_url('admin-post.php')) . '" style="display:none;gap:10px;padding:16px 20px;border-bottom:1px solid #eef0f5;">';
        echo '<input type="hidden" name="action" value="si_add_subscriber" />';
        wp_nonce_field('si_add_subscriber', 'si_add_subscriber_nonce');
        echo '<input type="text" name="name" placeholder="' . esc_attr__('Name', 'si-simple-forms') . '" class="si-search-input" style="max-width:200px;" />';
        echo '<input type="email" name="email" required placeholder="' . esc_attr__('Email address', 'si-simple-forms') . '" class="si-search-input" style="max-width:260px;" />';
        echo '<button type="submit" class="si-btn si-btn-primary si-btn-sm">' . esc_html__('Save', 'si-simple-forms') . '</button>';
        echo '</form>';

        echo '<div class="si-search-row">';
        echo '<input type="text" class="si-search-input" data-si-search="si-subscribers-table" placeholder="' . esc_attr__('Search subscribers by name or email...', 'si-simple-forms') . '" />';
        /* translators: %d: number of matching rows currently shown. */
        echo '<span class="si-count-label">' . sprintf(esc_html__('Showing %d entries', 'si-simple-forms'), count($rows)) . '</span>';
        echo '</div>';

        echo '<table class="si-data-table" id="si-subscribers-table">';
        echo '<thead><tr><th>ID</th><th>' . esc_html__('Name', 'si-simple-forms') . '</th><th>' . esc_html__('Email', 'si-simple-forms') . '</th><th>' . esc_html__('Status', 'si-simple-forms') . '</th><th>' . esc_html__('Action', 'si-simple-forms') . '</th></tr></thead><tbody>';
        if (empty($rows)) {
            echo '<tr><td colspan="5" style="text-align:center;color:#94a3b8;">' . esc_html__('No subscribers registered yet.', 'si-simple-forms') . '</td></tr>';
        } else {
            foreach ($rows as $r) {
                $search = strtolower(($r['name'] ?: '') . ' ' . $r['email']);
                echo '<tr data-search="' . esc_attr($search) . '">';
                echo '<td>' . esc_html($r['id']) . '</td>';
                echo '<td><strong>' . esc_html($r['name'] ?: 'N/A') . '</strong></td>';
                echo '<td>' . esc_html($r['email']) . '</td>';
                echo '<td><span class="si-badge-active">&#10003; ' . esc_html($r['status'] ?: 'Active') . '</span></td>';
                echo '<td class="si-row-actions"><a class="si-delete" href="' . esc_url(wp_nonce_url(admin_url('admin-post.php?action=si_delete_subscriber&id=' . (int) $r['id']), 'si_delete_subscriber')) . '">&#128465;</a></td>';
                echo '</tr>';
            }
        }
        echo '</tbody></table>';
        echo '</div>';

        Layout::close();
    }
}
