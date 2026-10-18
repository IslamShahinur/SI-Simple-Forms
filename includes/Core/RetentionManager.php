<?php
namespace SI\SimpleForms\Core;

defined('ABSPATH') || exit;

/**
 * Privacy & Data Retention enforcement (daily cron purge).
 */
final class RetentionManager
{
    const CRON_HOOK = 'si_simple_forms_retention_cron';

    public function register()
    {
        add_action(self::CRON_HOOK, [$this, 'purge']);
    }

    public static function schedule()
    {
        if (!wp_next_scheduled(self::CRON_HOOK)) {
            wp_schedule_event(time() + 60, 'daily', self::CRON_HOOK);
        }
    }

    public static function unschedule()
    {
        $timestamp = wp_next_scheduled(self::CRON_HOOK);
        if ($timestamp) {
            wp_unschedule_event($timestamp, self::CRON_HOOK);
        }
    }

    public function purge()
    {
        if (!get_option('si_retention_auto_delete', true)) {
            return;
        }

        global $wpdb;

        try {
            $messagesRetention = get_option('si_retention_messages', '180');
            if (ctype_digit((string) $messagesRetention) && (int) $messagesRetention > 0) {
                DbHelper::purgeOlderThan($wpdb->prefix . 'si_messages', (int) $messagesRetention);
            }

            $subscribersRetention = get_option('si_retention_subscribers', 'never');
            if (ctype_digit((string) $subscribersRetention) && (int) $subscribersRetention > 0) {
                DbHelper::purgeOlderThan($wpdb->prefix . 'si_subscribers', (int) $subscribersRetention);
            }
        } catch (\Throwable $e) {
            Logger::log('Retention Purge', $e->getMessage());
        }
    }
}