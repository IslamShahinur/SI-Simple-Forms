<?php
namespace SI\SimpleForms\Core;

defined('ABSPATH') || exit;

final class Autoloader
{
    public static function register()
    {
        spl_autoload_register([self::class, 'autoload']);
    }

    public static function autoload($class)
    {
        $prefix = 'SI\\SimpleForms\\';
        $baseDir = SI_SIMPLE_FORMS_DIR . 'includes/';

        $len = strlen($prefix);
        if (strncmp($prefix, $class, $len) !== 0) {
            return;
        }

        $relativeClass = substr($class, $len);
        $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';

        if (file_exists($file)) {
            require_once $file;
        }
    }
}