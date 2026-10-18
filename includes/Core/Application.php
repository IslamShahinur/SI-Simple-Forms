<?php
namespace SI\SimpleForms\Core;

defined('ABSPATH') || exit;

final class Application
{
    private static $instance = null;

    public static function instance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function boot()
    {
        (new Bootstrap())->register();
    }
}