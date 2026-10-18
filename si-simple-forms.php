<?php
/**
 * Plugin Name: SI Simple Forms
 * Plugin URI: https://plugins.shahinurislam.com/
 * Description: Lightweight contact and subscription forms with Cloudflare Turnstile anti-spam and MailPoet integration.
 * Version: 1.1.0
 * Requires at least: 6.8
 * Tested up to: 7.1
 * Requires PHP: 7.3
 * Author: Shahinur Islam
 * Author URI : https://shahinurislam.com/
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: si-simple-forms
 */
defined('ABSPATH') || exit;

use SI\SimpleForms\Core\Activator;
use SI\SimpleForms\Core\Application;
use SI\SimpleForms\Core\Autoloader;
use SI\SimpleForms\Core\Deactivator;

if (!defined('SI_SIMPLE_FORMS_FILE')) {
    define('SI_SIMPLE_FORMS_FILE', __FILE__);
}
if (!defined('SI_SIMPLE_FORMS_DIR')) {
    define('SI_SIMPLE_FORMS_DIR', plugin_dir_path(__FILE__));
}
if (!defined('SI_SIMPLE_FORMS_URL')) {
    define('SI_SIMPLE_FORMS_URL', plugin_dir_url(__FILE__));
}
if (!defined('SI_SIMPLE_FORMS_VERSION')) {
    define('SI_SIMPLE_FORMS_VERSION', '1.1.0');
}

require_once SI_SIMPLE_FORMS_DIR . 'includes/Core/Autoloader.php';
Autoloader::register();

register_activation_hook(__FILE__, static function () {
    try {
        require_once SI_SIMPLE_FORMS_DIR . 'includes/Core/Autoloader.php';
        Autoloader::register();
        if (class_exists(Activator::class)) {
            Activator::activate();
        }
    } catch (Throwable $e) {
        \SI\SimpleForms\Core\Logger::log('Activation', $e->getMessage());
    }
});

register_deactivation_hook(__FILE__, static function () {
    try {
        require_once SI_SIMPLE_FORMS_DIR . 'includes/Core/Autoloader.php';
        Autoloader::register();
        if (class_exists(Deactivator::class)) {
            Deactivator::deactivate();
        }
    } catch (Throwable $e) {
        \SI\SimpleForms\Core\Logger::log('Deactivation', $e->getMessage());
    }
});

add_action('plugins_loaded', static function () {
    if (class_exists(Application::class)) {
        Application::instance()->boot();
    }
});