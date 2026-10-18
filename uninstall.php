<?php
// If uninstall is not called from WordPress, exit.
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

wp_clear_scheduled_hook('si_simple_forms_retention_cron');

$deleteData = get_option('si_delete_data_on_uninstall', 'no');

if ($deleteData === 'yes') {
    global $wpdb;
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $wpdb->prefix is not user input; table identifiers cannot be passed through $wpdb->prepare(). Dropping our own plugin tables only runs when the user has explicitly opted in to full data removal on uninstall.
    $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}si_messages");
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $wpdb->prefix is not user input; table identifiers cannot be passed through $wpdb->prepare(). Dropping our own plugin tables only runs when the user has explicitly opted in to full data removal on uninstall.
    $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}si_subscribers");

    $options = [
        'si_simple_forms_db_version',
        'si_contact_form_config',
        'si_subscribe_form_config',
        'si_contact_forms_registry',
        'si_subscribe_forms_registry',
        'si_contact_forms_registry_migrated',
        'si_subscribe_forms_registry_migrated',
        'si_sender_name',
        'si_sender_email',
        'si_turnstile_site_key',
        'si_turnstile_secret_key',
        'si_rest_api_enabled',
        'si_mailpoet_sync_enabled',
        'si_rate_limiting_enabled',
        'si_debug_logging_enabled',
        'si_global_custom_css',
        'si_delete_data_on_uninstall',
        'si_retention_messages',
        'si_retention_subscribers',
        'si_retention_auto_delete',
    ];
    foreach ($options as $option) {
        delete_option($option);
    }
}