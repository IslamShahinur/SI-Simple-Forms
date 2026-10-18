<?php
namespace SI\SimpleForms\Core;

defined('ABSPATH') || exit;

/**
 * Small cached data-access layer around the plugin's two custom tables
 * (si_messages, si_subscribers).
 *
 * Centralizing every raw $wpdb call here means the rest of the plugin
 * never touches $wpdb directly for these tables, and reads are cached
 * via the WordPress object cache. Each table has its own "generation"
 * counter baked into its cache keys; invalidate() bumps it instead of
 * flushing the whole cache group, so this only needs the object-cache
 * functions available since WordPress 2.0 (no 6.1+ requirement).
 */
final class DbHelper
{
    private static function cacheGroup()
    {
        return 'si_simple_forms';
    }

    private static function generation($table)
    {
        $key = 'gen_' . $table;
        $gen = wp_cache_get($key, self::cacheGroup());
        if (false === $gen) {
            $gen = 1;
            wp_cache_set($key, $gen, self::cacheGroup());
        }

        return (int) $gen;
    }

    /**
     * Invalidates all cached reads for $table by bumping its generation counter.
     */
    public static function invalidate($table)
    {
        wp_cache_set('gen_' . $table, self::generation($table) + 1, self::cacheGroup());
    }

    public static function tableExists($table)
    {
        global $wpdb;

        $cacheKey = 'exists_' . $table;
        $cached = wp_cache_get($cacheKey, self::cacheGroup());
        if (false !== $cached) {
            return (bool) $cached;
        }

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- result is cached above via wp_cache_get()/wp_cache_set(); the value is passed through $wpdb->prepare().
        $exists = ($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table)) === $table);
        wp_cache_set($cacheKey, $exists, self::cacheGroup(), 5 * MINUTE_IN_SECONDS);

        return $exists;
    }

    /**
     * @param string $table
     * @param string $whereSql Optional hard-coded (never user-supplied) WHERE clause, without the "WHERE" keyword.
     */
    public static function count($table, $whereSql = '')
    {
        global $wpdb;

        if (!self::tableExists($table)) {
            return 0;
        }

        $cacheKey = 'count_' . self::generation($table) . '_' . md5($table . '|' . $whereSql);
        $cached = wp_cache_get($cacheKey, self::cacheGroup());
        if (false !== $cached) {
            return (int) $cached;
        }

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter -- $table/$whereSql are always hard-coded call-site literals (never user input); table/column identifiers cannot be passed through $wpdb->prepare(). Result is cached above.
        $count = (int) $wpdb->get_var($wpdb->prepare('SELECT COUNT(*) FROM ' . $table . ($whereSql !== '' ? ' WHERE ' . $whereSql : '')));
        wp_cache_set($cacheKey, $count, self::cacheGroup(), MINUTE_IN_SECONDS);

        return $count;
    }

    /**
     * @param string $table
     * @param string $orderBySql Hard-coded (never user-supplied) ORDER BY clause, without the "ORDER BY" keyword.
     * @param int    $limit
     * @return array<int,array<string,mixed>>
     */
    public static function getResults($table, $orderBySql, $limit)
    {
        global $wpdb;

        if (!self::tableExists($table)) {
            return [];
        }

        $cacheKey = 'results_' . self::generation($table) . '_' . md5($table . '|' . $orderBySql . '|' . $limit);
        $cached = wp_cache_get($cacheKey, self::cacheGroup());
        if (false !== $cached) {
            return $cached;
        }

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter -- $table/$orderBySql are always hard-coded call-site literals (never user input); table/column identifiers cannot be passed through $wpdb->prepare(). The %d limit is prepared. Result is cached above.
        $results = $wpdb->get_results($wpdb->prepare('SELECT * FROM ' . $table . ' ORDER BY ' . $orderBySql . ' LIMIT %d', $limit), ARRAY_A);
        $results = is_array($results) ? $results : [];
        wp_cache_set($cacheKey, $results, self::cacheGroup(), 30);

        return $results;
    }

    /**
     * Deletes rows older than $days from $table (based on its created_at column) and invalidates its cache.
     */
    public static function purgeOlderThan($table, $days)
    {
        global $wpdb;

        if (!self::tableExists($table)) {
            return;
        }

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter -- $table is always a hard-coded call-site literal (never user input); table identifiers cannot be passed through $wpdb->prepare(). The %d value is prepared. This is a write operation, not cacheable.
        $wpdb->query($wpdb->prepare('DELETE FROM ' . $table . ' WHERE created_at < DATE_SUB(NOW(), INTERVAL %d DAY)', (int) $days));

        self::invalidate($table);
    }
}
