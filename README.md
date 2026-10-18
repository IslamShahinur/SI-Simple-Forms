=== SI Simple Forms ===
Contributors: Shahinur Islam
Donate link: https://shahinurislam.com/
Tags: contact form, subscribe form, mailpoet, turnstile, spam protection
Requires at least: 6.8
Tested up to: 7.1
Requires PHP: 7.3
Stable tag: 1.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A simple, lightweight contact form and subscribe form builder with Cloudflare Turnstile anti-spam and MailPoet integration.

== Description ==

SI Simple Forms is a clean, lightweight contact form and newsletter subscribe form builder for WordPress. It's built on native WordPress hooks and a small custom-table storage layer — no page-builder dependency, no bloated frontend framework.

**Key features:**

* **Drag-and-drop Contact Form Builder** — live preview, reorderable fields, per-field settings (label, placeholder, required, width, help text, CSS class), and a Styling tab (theme, layout, border radius, input style, button style).
* **Drag-and-drop Subscribe Form Builder** — a Form Elements palette (phone, company, GDPR consent, dropdown, date, heading, divider, custom HTML) plus an Email Marketing Provider panel with MailPoet list/tag targeting.
* **Cloudflare Turnstile anti-spam** — free, privacy-friendly spam protection, alongside a built-in honeypot field.
* **MailPoet integration** — sync captured subscribers straight into a MailPoet list.
* **Shortcode-driven** — `[si_contact_form]` and `[si_subscribe_form]` render exactly what you build in the builder, anywhere on your site.
* **Built-in Inbox & Subscriber directory** — every submission and subscriber is stored in the WordPress database and manageable from the admin, with CSV export.
* **Privacy & Data Retention controls** — configurable auto-delete windows for stored messages and subscribers, plus an explicit delete-data-on-uninstall option.
* **Live Sandbox & PHP Code Inspector tools** — preview your live forms and run a quick syntax health check across the plugin's own files, right from the admin.
* **REST API health endpoint** and optional rate limiting for form submissions.

= Privacy notice =

This plugin stores the data visitors submit through your forms (name, email, and any other fields you add) in two custom database tables so you can view and manage submissions from your WordPress admin. No data is sent to any third party by default. If you enable Cloudflare Turnstile, form submissions are verified against Cloudflare's API (see [Cloudflare's privacy policy](https://www.cloudflare.com/privacypolicy/)). If you enable MailPoet sync, subscriber data is passed to your own MailPoet installation. You control how long submitted data is retained via Settings → Privacy & Data Retention.

== External Services ==

SI Simple Forms optionally integrates Cloudflare Turnstile to provide spam and automated-bot protection for forms. When Cloudflare Turnstile is enabled by the site administrator, the plugin loads the Turnstile JavaScript from Cloudflare 
and sends the Turnstile response to Cloudflare for verification.

Service:
Cloudflare Turnstile
https://www.cloudflare.com/products/turnstile/

Privacy Policy:
https://www.cloudflare.com/privacypolicy/

Terms of Service:
https://www.cloudflare.com/terms/

== Installation ==

1. Upload the `si-simple-forms` directory to the `/wp-content/plugins/` directory, or install the plugin directly through the WordPress Plugins screen.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Go to **SI Simple Forms** in your WordPress admin sidebar to create your first contact or subscribe form.
4. Place the generated shortcode (e.g. `[si_contact_form id="1"]`) on any page or post.
5. (Optional) Go to **SI Simple Forms → Settings** to add your Cloudflare Turnstile keys and configure sender identity, data retention, and other options.

== Frequently Asked Questions ==

= Do I need a Cloudflare account to use this plugin? =

No. Cloudflare Turnstile is optional. The plugin also ships with a built-in honeypot field that works with no setup and no external account.

= Does this plugin require MailPoet? =

No. MailPoet integration is optional and only activates if the MailPoet plugin is installed and active on your site.

= Where are form submissions stored? =

In two dedicated database tables created by the plugin (for messages and subscribers). You can view, search, and export them from the SI Simple Forms admin, and configure automatic deletion after a set number of days under Settings → Privacy & Data Retention.

= What happens to my data if I uninstall the plugin? =

By default, your data is kept. If you want the plugin's custom tables and settings removed on uninstall, enable "Delete plugin data on uninstall" under Settings before deleting the plugin.

= Can I style the forms to match my theme? =

Yes. Each builder has a Styling tab for theme, layout, border radius, input style, and button style, plus a global custom CSS field under Settings for further tweaks.

== Changelog ==

= 1.1.0 =
* Initial release on WordPress.org.
* Contact Form Builder and Subscribe Form Builder with live preview and drag-and-drop fields.
* Cloudflare Turnstile and honeypot anti-spam protection.
* MailPoet subscriber sync.
* Inbox, Subscriber directory, CSV export, and Privacy & Data Retention controls.
* Live Sandbox and PHP Code Inspector admin tools.
* REST API health endpoint with optional rate limiting.

== Upgrade Notice ==

= 1.1.0 =
Initial public release.
