<?php
namespace SI\SimpleForms\Core;

defined('ABSPATH') || exit;

use SI\SimpleForms\Database\Installer;

final class Activator
{
    public static function activate()
    {
        Installer::install();
        RetentionManager::schedule();
        flush_rewrite_rules();
    }
}