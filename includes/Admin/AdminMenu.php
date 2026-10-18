<?php
namespace SI\SimpleForms\Admin;

defined('ABSPATH') || exit;

final class AdminMenu
{
    /** @var array<string,object> */
    private $pages = [];

    public function register()
    {
        $this->pages = [
            'dashboard'           => new DashboardPage(),
            'contactBuilder'      => new ContactBuilderPage(),
            'myContactForms'      => new MyContactFormsPage(),
            'subscribeBuilder'    => new SubscribeBuilderPage(),
            'mySubscriptionForms' => new MySubscriptionFormsPage(),
            'messages'            => new MessagesPage(),
            'subscribers'         => new SubscribersPage(),
            'settings'            => new SettingsPage(),
            'howTo'               => new HowToGuidePage(),
            'sandbox'             => new LiveSandboxPage(),
        ];

        foreach ($this->pages as $page) {
            if (method_exists($page, 'register')) {
                $page->register();
            }
        }

        (new ToolsHandlers())->register();
        (new AssetManager())->register();

        add_action('admin_menu', [$this, 'addMenus']);
        // NOTE: We intentionally do NOT call remove_submenu_page() here.
        // remove_submenu_page() deletes the page from the global $submenu
        // array, which wp-admin/admin.php also uses to resolve direct URL
        // requests (admin.php?page=...). Removing it breaks direct access
        // and produces "Sorry, you are not allowed to access this page"
        // for the two header "Live Sandbox" buttons even though the user
        // has permission. Instead we keep the submenu entry registered
        // (so the page stays routable) and simply hide its row visually.
        add_action('admin_head', [$this, 'hideHiddenPages']);
    }

    public function addMenus()
    {
        add_menu_page(
            __('SI Simple Forms', 'si-simple-forms'),
            __('SI Simple Forms', 'si-simple-forms'),
            'manage_options',
            'si-simple-forms',
            [$this->pages['dashboard'], 'render'],
            'dashicons-email-alt2',
            30
        );

        // Overrides the auto-generated first submenu (which otherwise duplicates the top-level label) with "Dashboard".
        add_submenu_page(
            'si-simple-forms',
            __('Dashboard', 'si-simple-forms'),
            __('Dashboard', 'si-simple-forms'),
            'manage_options',
            'si-simple-forms',
            [$this->pages['dashboard'], 'render']
        );

        $subpages = [
            ['si-simple-forms-contact-builder', __('Contact Form Builder', 'si-simple-forms'), 'contactBuilder'],
            ['si-simple-forms-my-contact-forms', __('My Contact Forms', 'si-simple-forms'), 'myContactForms'],
            ['si-simple-forms-subscribe-builder', __('Subscription Forms', 'si-simple-forms'), 'subscribeBuilder'],
            ['si-simple-forms-my-subscription-forms', __('My Subscription Forms', 'si-simple-forms'), 'mySubscriptionForms'],
            ['si-simple-forms-messages', __('Messages', 'si-simple-forms'), 'messages'],
            ['si-simple-forms-subscribers', __('Subscribers', 'si-simple-forms'), 'subscribers'],
            ['si-simple-forms-settings', __('Settings', 'si-simple-forms'), 'settings'],
            ['si-simple-forms-how-to', __('How to Guide', 'si-simple-forms'), 'howTo'],
            ['si-simple-forms-sandbox', __('Live Sandbox', 'si-simple-forms'), 'sandbox'],
        ];

        foreach ($subpages as list($slug, $label, $pageKey)) {
            add_submenu_page(
                'si-simple-forms',
                $label,
                $label,
                'manage_options',
                $slug,
                [$this->pages[$pageKey], 'render']
            );
        }
    }

    /**
     * Keeps the locked menu structure (sandbox stays routable but invisible).
     * Hidden via CSS instead of remove_submenu_page() so direct links
     * (the two header "Live Sandbox" buttons) keep working.
     */
    public function hideHiddenPages()
    {
        echo '<style>#adminmenu a[href$="page=si-simple-forms-sandbox"],#adminmenu a[href*="page=si-simple-forms-sandbox&"]{display:none;}</style>';
    }
}