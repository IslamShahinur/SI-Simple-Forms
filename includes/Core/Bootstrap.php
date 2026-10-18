<?php
namespace SI\SimpleForms\Core;

defined('ABSPATH') || exit;

use SI\SimpleForms\Admin\AdminMenu;
use SI\SimpleForms\Database\Installer;
use SI\SimpleForms\Forms\ContactForm;
use SI\SimpleForms\Forms\SubscriptionForm;
use SI\SimpleForms\Spam\SpamProtectionManager;
use SI\SimpleForms\Integrations\IntegrationManager;
use SI\SimpleForms\Rest\HealthEndpoint;

final class Bootstrap
{
    public function register()
    {
        try {
            Installer::maybeInstall();

            // Register Spam & Integration Managers
            SpamProtectionManager::instance()->init();
            IntegrationManager::instance()->init();

            (new ContactForm())->register();
            (new SubscriptionForm())->register();
            (new HealthEndpoint())->register();
            (new RetentionManager())->register();

            // AdminMenu registers all admin pages (dashboard, builders, my-forms, tools)
            // and their admin_post_* handlers, and enqueues the app-shell assets.
            (new AdminMenu())->register();
        } catch (\Throwable $e) {
            Logger::log('Bootstrap', $e->getMessage());
        }
    }
}