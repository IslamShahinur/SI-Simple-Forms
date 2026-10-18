<?php
namespace SI\SimpleForms\Core;

defined('ABSPATH') || exit;

/**
 * Centralized, WP_DEBUG-gated debug logging.
 *
 * All internal catch blocks should call Logger::log() instead of calling
 * error_log() directly, so the whole plugin has a single, clearly-justified
 * place where debug output is written.
 */
final class Logger
{
    /**
     * @param string $context Short label identifying where the error came from.
     * @param string $message Error detail (e.g. an exception message).
     */
    public static function log($context, $message)
    {
        if (!defined('WP_DEBUG') || !WP_DEBUG) {
            return;
        }

        // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- sole, centralized debug-logging sink for the plugin; only runs when WP_DEBUG is enabled.
        error_log(sprintf('[SI Simple Forms] %s: %s', $context, $message));
    }
}
