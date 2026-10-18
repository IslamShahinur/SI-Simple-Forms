<?php
namespace SI\SimpleForms\Core;

defined('ABSPATH') || exit;

final class Deactivator
{
    public static function deactivate()
    {
        RetentionManager::unschedule();
        flush_rewrite_rules();
    }
}