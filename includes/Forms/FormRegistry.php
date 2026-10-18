<?php
namespace SI\SimpleForms\Forms;

defined('ABSPATH') || exit;

/**
 * Option-based registry for saved Contact & Subscription forms.
 * Never deletes or renames existing data; legacy single-form options remain untouched.
 */
final class FormRegistry
{
    const CONTACT   = 'contact';
    const SUBSCRIBE = 'subscribe';

    private static function optionKey($type)
    {
        return $type === self::SUBSCRIBE ? 'si_subscribe_forms_registry' : 'si_contact_forms_registry';
    }

    /** @return array<int,array<string,mixed>> */
    public static function all($type)
    {
        $rows = get_option(self::optionKey($type), []);
        return is_array($rows) ? $rows : [];
    }

    /** @return array<string,mixed>|null */
    public static function get($type, $id)
    {
        foreach (self::all($type) as $row) {
            if ((int) ($row['id'] ?? 0) === (int) $id) {
                return $row;
            }
        }
        return null;
    }

    /** @return array<string,mixed>|null */
    public static function first($type)
    {
        $rows = self::all($type);
        return $rows ? $rows[0] : null;
    }

    /**
     * Save (create or update) a form entry. Returns the form ID.
     */
    public static function save($type, array $config, $editId = 0)
    {
        $rows   = self::all($type);
        $editId = (int) $editId;

        if ($editId > 0) {
            foreach ($rows as $i => $row) {
                if ((int) ($row['id'] ?? 0) === $editId) {
                    $rows[$i]['config']     = $config;
                    $rows[$i]['name']       = isset($config['title']) ? $config['title'] : '';
                    $rows[$i]['updated_at'] = current_time('mysql');
                    update_option(self::optionKey($type), $rows);
                    return $editId;
                }
            }
        }

        $nextId = 1;
        foreach ($rows as $row) {
            $nextId = max($nextId, (int) ($row['id'] ?? 0) + 1);
        }

        $rows[] = [
            'id'         => $nextId,
            'name'       => isset($config['title']) ? $config['title'] : '',
            'config'     => $config,
            'updated_at' => current_time('mysql'),
        ];
        update_option(self::optionKey($type), $rows);
        return $nextId;
    }

    public static function delete($type, $id)
    {
        $rows = array_values(array_filter(self::all($type), function ($row) use ($id) {
            return (int) ($row['id'] ?? 0) !== (int) $id;
        }));
        update_option(self::optionKey($type), $rows);
    }

    /**
     * One-time safe migration: seed registry ID 1 from legacy single-form options.
     * Legacy options are preserved (never deleted).
     */
    public static function migrate()
    {
        if (!get_option('si_contact_forms_registry_migrated', false)) {
            $legacy = get_option('si_contact_form_config', null);
            if (is_array($legacy) && !empty($legacy) && empty(self::all(self::CONTACT))) {
                update_option(self::optionKey(self::CONTACT), [
                    [
                        'id'         => 1,
                        'name'       => isset($legacy['title']) ? $legacy['title'] : 'Contact Form',
                        'config'     => $legacy,
                        'updated_at' => current_time('mysql'),
                    ],
                ]);
            }
            update_option('si_contact_forms_registry_migrated', '1');
        }

        if (!get_option('si_subscribe_forms_registry_migrated', false)) {
            $legacy = get_option('si_subscribe_form_config', null);
            if (is_array($legacy) && !empty($legacy) && empty(self::all(self::SUBSCRIBE))) {
                update_option(self::optionKey(self::SUBSCRIBE), [
                    [
                        'id'         => 1,
                        'name'       => isset($legacy['title']) ? $legacy['title'] : 'Subscribe to our Newsletter',
                        'config'     => $legacy,
                        'updated_at' => current_time('mysql'),
                    ],
                ]);
            }
            update_option('si_subscribe_forms_registry_migrated', '1');
        }
    }
}