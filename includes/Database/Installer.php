<?php
namespace SI\SimpleForms\Database;

defined('ABSPATH') || exit;

use SI\SimpleForms\Core\Logger;
use SI\SimpleForms\Core\RetentionManager;
use SI\SimpleForms\Forms\FormRegistry;

final class Installer
{
    const DB_VERSION = '1.1.0';

    public static function maybeInstall()
    {
        try {
            $installed = get_option('si_simple_forms_db_version');
            if ($installed !== self::DB_VERSION) {
                self::install();
            }
            RetentionManager::schedule();
        } catch (\Throwable $e) {
            Logger::log('DB Install Check', $e->getMessage());
        }
    }

    public static function install()
    {
        try {
            global $wpdb;
            if (!function_exists('dbDelta')) {
                require_once ABSPATH . 'wp-admin/includes/upgrade.php';
            }

            $charsetCollate = $wpdb->get_charset_collate();
            $messagesTable = $wpdb->prefix . 'si_messages';
            $subscribersTable = $wpdb->prefix . 'si_subscribers';

            $sqlMessages = "CREATE TABLE {$messagesTable} (
                id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                form_id varchar(100) NOT NULL DEFAULT 'contact-form-1',
                name varchar(190) NOT NULL,
                email varchar(190) NOT NULL,
                phone varchar(100) DEFAULT '',
                subject varchar(255) NOT NULL,
                message longtext NOT NULL,
                status varchar(30) NOT NULL DEFAULT 'unread',
                created_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
                PRIMARY KEY  (id),
                KEY email  (email)
            ) {$charsetCollate};";

            $sqlSubscribers = "CREATE TABLE {$subscribersTable} (
                id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                form_id varchar(100) NOT NULL DEFAULT 'subscribe-form-1',
                name varchar(190) NULL,
                email varchar(190) NOT NULL,
                status varchar(30) NOT NULL DEFAULT 'Active',
                tags text NULL,
                source varchar(100) DEFAULT 'Website Form',
                created_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
                PRIMARY KEY  (id),
                KEY email  (email)
            ) {$charsetCollate};";

            if (function_exists('dbDelta')) {
                dbDelta($sqlMessages);
                dbDelta($sqlSubscribers);
            }
            update_option('si_simple_forms_db_version', self::DB_VERSION, false);

            // Safe upgrade migration: seed form registries from legacy options (never deletes data).
            FormRegistry::migrate();
            RetentionManager::schedule();
        } catch (\Throwable $e) {
            Logger::log('DB Install', $e->getMessage());
        }
    }
}