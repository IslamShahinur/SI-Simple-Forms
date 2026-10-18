<?php
namespace SI\SimpleForms\Admin;

defined('ABSPATH') || exit;

final class HowToGuidePage
{
    public function register()
    {
    }

    public function render()
    {
        Layout::open('si-simple-forms-how-to', __('How to Guide', 'si-simple-forms'), __('Everything you need to build, place, style, secure, and manage your contact & subscription forms.', 'si-simple-forms'));

        $steps = [
            [
                'title' => __('1. Build your Contact Form', 'si-simple-forms'),
                'body' => __('Open Contact Form Builder. Give your form a name at the top (required for My Contact Forms), then use the Fields tab to add, remove, and drag-reorder fields. Selecting a Dropdown field opens an options editor where you can write custom option texts (e.g. Complaint, Suggestions). The Styling tab controls theme, layout, border radius, input style, and button style; the Email tab controls notification recipient, subject template, and success message; the Spam tab shows your always-on honeypot and Turnstile status.', 'si-simple-forms'),
            ],
            [
                'title' => __('2. Preview on Desktop, Tablet & Mobile', 'si-simple-forms'),
                'body' => __('Use the Desktop / Tablet / Mobile toggle in the builder header to preview the live form at real device widths before you publish. The live preview updates instantly as you edit fields, styling, and button text.', 'si-simple-forms'),
            ],
            [
                'title' => __('3. Save & manage forms in My Contact Forms', 'si-simple-forms'),
                'body' => __('Click Save Form to validate and store your form. Every saved form appears in My Contact Forms with a unique, permanent ID and an auto-generated shortcode such as [si_contact_form id="1"]. From there you can copy the shortcode with one click, edit the form, or delete it.', 'si-simple-forms'),
            ],
            [
                'title' => __('4. Build your Subscription Form', 'si-simple-forms'),
                'body' => __('Open Subscription Forms (builder). Add elements from the Form Elements palette (email, name, phone, company, GDPR consent, dropdown with custom options, date, heading, divider, custom HTML) and watch the live preview on top. The Email Marketing Provider & Integration panel now sits below the preview for a cleaner workflow.', 'si-simple-forms'),
            ],
            [
                'title' => __('5. MailPoet integration (automatic)', 'si-simple-forms'),
                'body' => __('MailPoet is detected automatically — no API keys, OAuth, or webhooks needed. When MailPoet is installed and selected you see its live status (Connected), version, your subscriber lists, subscriber status (Unconfirmed recommended), double opt-in, auto-create, and welcome email settings, plus a Sync Now button that pushes existing subscribers to MailPoet. If MailPoet is not installed the panel shows "MailPoet: Not Installed" and hides those controls.', 'si-simple-forms'),
            ],
            [
                'title' => __('6. Manage forms in My Subscription Forms', 'si-simple-forms'),
                'body' => __('Saving the subscription builder creates an entry in My Subscription Forms with its own shortcode ([si_subscribe_form id="1"], alias [si_subscription_form id="1"]). Copy, edit, or delete forms from the table at any time.', 'si-simple-forms'),
            ],
            [
                'title' => __('7. Place your forms anywhere', 'si-simple-forms'),
                'body' => __('Copy a shortcode from My Contact Forms or My Subscription Forms and paste it into any post, page, or widget area, or call do_shortcode() from a template file. Shortcodes always render exactly what you configured in the builders, including dropdown options and consent checkboxes.', 'si-simple-forms'),
            ],
            [
                'title' => __('8. Turn on spam protection', 'si-simple-forms'),
                'body' => __('Every form ships with an always-on honeypot trap. For stronger protection, add your Cloudflare Turnstile Site Key and Secret Key under Settings — the widget then appears automatically on both forms and is verified on submission.', 'si-simple-forms'),
            ],
            [
                'title' => __('9. Review submissions & export', 'si-simple-forms'),
                'body' => __('Messages and Subscribers store every submission safely in your WordPress database (wp_si_messages / wp_si_subscribers), independent of any third-party service. Search entries live and export either list to CSV at any time.', 'si-simple-forms'),
            ],
            [
                'title' => __('10. Preview with Live Sandboxes', 'si-simple-forms'),
                'body' => __('Use the header buttons "Contact Form Live Sandbox" and "Subscription Form Live Sandbox" to see exactly how each form renders on a real frontend page before publishing.', 'si-simple-forms'),
            ],
            [
                'title' => __('11. Privacy & Data Retention', 'si-simple-forms'),
                'body' => __('Both forms end with a required "Privacy & Data Retention" consent checkbox linking to Settings → Privacy & Data. There you control how long contact messages (default 180 Days) and subscribers (default Never Delete) are kept, enable automatic deletion of expired records on a daily schedule, and open the Privacy & Data Guide (PDF) via View Documentation.', 'si-simple-forms'),
            ],
            [
                'title' => __('12. Need help?', 'si-simple-forms'),
                'body' => __('Use the Need Support? links in the plugin header or sidebar footer to reach official support at plugins.shahinurislam.com/support, or return to the normal WordPress Dashboard anytime with the Exit to WordPress button in the header.', 'si-simple-forms'),
            ],
        ];

        foreach ($steps as $step) {
            echo '<div class="si-doc-block"><h2>' . esc_html($step['title']) . '</h2><p style="font-size:13px;color:#475569;line-height:1.6;margin:0;">' . esc_html($step['body']) . '</p></div>';
        }

        Layout::close();
    }
}