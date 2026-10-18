<?php
namespace SI\SimpleForms\Admin;

defined('ABSPATH') || exit;

use SI\SimpleForms\Core\DbHelper;

final class ToolsHandlers
{
    public function register()
    {
        add_action('admin_post_si_export_csv', [$this, 'exportCsv']);
        add_action('admin_post_si_download_plugin_zip', [$this, 'downloadZip']);
    }

    public function exportCsv()
    {
        if (!isset($_GET['_wpnonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['_wpnonce'])), 'si_export_csv')) {
            wp_die(esc_html__('Security check failed.', 'si-simple-forms'));
        }
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('You do not have permission to do this.', 'si-simple-forms'));
        }

        global $wpdb;
        $type = (isset($_GET['type']) && sanitize_text_field(wp_unslash($_GET['type'])) === 'subscribers') ? 'subscribers' : 'messages';
        $table = $wpdb->prefix . 'si_' . $type;
        // Export intentionally has no row cap; 1,000,000 is a practical ceiling far beyond any realistic table size.
        $rows = DbHelper::getResults($table, 'id DESC', 1000000);

        nocache_headers();
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="si-simple-forms-' . $type . '-' . gmdate('Ymd-His') . '.csv"');

        // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen_fopen -- streaming a CSV directly to the browser response (php://output), not a filesystem write; WP_Filesystem cannot target this stream.
        $out = fopen('php://output', 'w');
        if (!empty($rows)) {
            fputcsv($out, array_keys($rows[0]));
            foreach ($rows as $row) {
                fputcsv($out, $row);
            }
        } else {
            fputcsv($out, ['No data']);
        }
        // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose -- closing the php://output stream opened above, not a filesystem handle.
        fclose($out);
        exit;
    }

    public function downloadZip()
    {
        if (!isset($_GET['_wpnonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['_wpnonce'])), 'si_download_zip')) {
            wp_die(esc_html__('Security check failed.', 'si-simple-forms'));
        }
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('You do not have permission to do this.', 'si-simple-forms'));
        }
        if (!class_exists('\ZipArchive')) {
            wp_die(esc_html__('The PHP ZipArchive extension is not available on this server.', 'si-simple-forms'));
        }

        $sourceDir = rtrim(SI_SIMPLE_FORMS_DIR, '/');
        $zipName = 'si-simple-forms-v' . SI_SIMPLE_FORMS_VERSION . '.zip';
        $tmpFile = trailingslashit(get_temp_dir()) . 'si-simple-forms-' . wp_generate_password(8, false) . '.zip';

        $zip = new \ZipArchive();
        if ($zip->open($tmpFile, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            wp_die(esc_html__('Could not create the plugin archive.', 'si-simple-forms'));
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($sourceDir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::LEAVES_ONLY
        );
        foreach ($iterator as $file) {
            if ($file->isDir()) {
                continue;
            }
            $filePath = $file->getRealPath();
            $relativePath = 'si-simple-forms/' . substr($filePath, strlen($sourceDir) + 1);
            $zip->addFile($filePath, $relativePath);
        }
        $zip->close();

        nocache_headers();
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . $zipName . '"');
        header('Content-Length: ' . filesize($tmpFile));
        // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_readfile -- streaming a just-built temporary ZIP straight to the browser download response.
        readfile($tmpFile);
        // phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink -- deleting our own temp file (not user-supplied data) immediately after streaming it.
        wp_delete_file($tmpFile);
        exit;
    }
}
