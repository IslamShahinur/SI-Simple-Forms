# SI Simple Forms — User Guide
======================================================================================================================================================================================================================
**Version:** 1.1.0
**For:** Site owners and editors using the plugin day-to-day
======================================================================================================================================================================================================================
## 1. What this plugin does
======================================================================================================================================================================================================================
SI Simple Forms lets you build two kinds of forms without touching code:

- **Contact forms** — visitors send you a message (name, email, subject, message, etc.). Submissions land in your **Messages** inbox inside WordPress.
- **Subscribe forms** — visitors join your mailing list. Entries land in your **Subscribers** directory, and can optionally sync straight into **MailPoet**.

Both form types share the same anti-spam protection (Cloudflare Turnstile + an invisible honeypot field) and are placed on any page or post with a shortcode.
======================================================================================================================================================================================================================
## 2. Finding your way around
======================================================================================================================================================================================================================
After activating the plugin, look for **SI Simple Forms** in your WordPress admin sidebar (a mail icon). The menu contains:

| Menu item | What it's for |
|---|---|
| **Dashboard** | At-a-glance totals: messages, subscribers, MailPoet status, system health. |
| **Contact Form Builder** | Build/edit your contact form. |
| **My Contact Forms** | List of all contact forms you've created, with their shortcodes. |
| **Subscription Forms** | Build/edit your subscribe form. |
| **My Subscription Forms** | List of all subscribe forms, with their shortcodes and a "Sync to MailPoet" action. |
| **Messages** | Every contact-form submission, newest first. |
| **Subscribers** | Every subscribe-form entry, newest first. You can also add a subscriber manually here. |
| **Settings** | Cloudflare Turnstile keys, sender identity, data retention, REST API, custom CSS. |
| **How to Guide** | In-admin quick reference. |

At the top of the Dashboard, two **Live Sandbox** buttons let you preview exactly how your Contact Form and Subscribe Form currently look and behave, without leaving wp-admin.
======================================================================================================================================================================================================================
## 3. Building your first Contact Form
======================================================================================================================================================================================================================
1. Go to **SI Simple Forms → Contact Form Builder**.
2. Give it a **Title** (shown above the form, optional).
3. Add fields from the field palette (Name, Email, Phone, Subject, Message, custom text/textarea/select/date fields). Drag to reorder.
4. Click a field to edit its **Label**, **Placeholder**, whether it's **Required**, its **width** (full/half), and an optional CSS class.
5. Open the **Styling** tab to pick a theme, layout, border radius, input style, and button style — no CSS required.
6. Turn on **Privacy consent checkbox** if you want a "I agree to the Privacy & Data Retention policy" checkbox above the submit button (recommended if you're subject to GDPR/CCPA).
7. Click **Save**. You'll see a shortcode like:

   [si_contact_form id="1"]

======================================================================================================================================================================================================================
8. Paste that shortcode into any page, post, or widget area that supports shortcodes.
======================================================================================================================================================================================================================
You can build as many contact forms as you like — each gets its own numeric ID and shortcode, and appears in **My Contact Forms**.
======================================================================================================================================================================================================================
## 4. Building your first Subscribe Form
======================================================================================================================================================================================================================
1. Go to **SI Simple Forms → Subscription Forms**.
2. Set a **Title** and **Button text**.
3. From **Form Elements**, add any of: Phone, Company, GDPR consent checkbox, Dropdown, Date, Heading, Divider, or Custom HTML — in addition to the default Name/Email fields.
4. Open the **Email Marketing Provider** panel:
   - If **MailPoet** is installed and active on your site, choose the **list** (and optional tag) new subscribers should be added to.
5. Save. You'll get a shortcode like:

  
   [si_subscribe_form id="1"]
  
======================================================================================================================================================================================================================
6. Paste it into a page, post, footer widget, or sidebar.
======================================================================================================================================================================================================================
Every subscribe form submission is stored in **Subscribers** regardless of whether MailPoet sync is on — MailPoet sync is an *additional* action, not a replacement for the built-in subscriber list.

### Syncing existing subscribers to MailPoet

If you turned on MailPoet sync *after* you already had subscribers, go to **My Subscription Forms** and click **Sync to MailPoet** next to the relevant form. This pushes your existing subscriber list into MailPoet without waiting for new form submissions.
======================================================================================================================================================================================================================
## 5. Setting up spam protection
======================================================================================================================================================================================================================
Go to **SI Simple Forms → Settings**.

### Cloudflare Turnstile (recommended)

Turnstile is Cloudflare's free, privacy-friendly CAPTCHA replacement — most visitors never see a challenge at all.

1. Create a free Turnstile site at [the Cloudflare dashboard](https://dash.cloudflare.com/) (Turnstile section).
2. Copy your **Site Key** and **Secret Key** into Settings → Cloudflare Turnstile.
3. Save. Turnstile now runs automatically on every SI Simple Forms form on your site.

If you don't configure Turnstile, forms are still protected by an invisible **honeypot** field (no setup needed) — a hidden field that only bots fill in, which silently rejects their submission.
======================================================================================================================================================================================================================
## 6. Managing submissions
======================================================================================================================================================================================================================
### Messages (contact form inbox)

- **SI Simple Forms → Messages** lists every submission: name, email, subject, message, and time received.
- Click **Delete** to remove a single message permanently.
- Click **Export CSV** to download all messages as a spreadsheet.

### Subscribers

- **SI Simple Forms → Subscribers** lists every subscribe-form entry.
- **Add subscriber** lets you manually add someone (e.g. from an offline signup).
- **Delete** removes a single subscriber.
- **Export CSV** downloads the full list.

### Bulk export/import (Settings → Tools)

From Settings, you can download a full backup ZIP of your messages and subscribers.
======================================================================================================================================================================================================================
## 7. Privacy & Data Retention
======================================================================================================================================================================================================================
Under **Settings → Privacy & Data Retention** you control:

- **Message retention** — automatically delete messages older than a set number of days (default 180), or keep forever.
- **Subscriber retention** — same, for subscribers (default: never auto-delete).
- **Auto-delete toggle** — turn the automatic cleanup on/off. When on, a daily background task removes anything past its retention window.
- **Delete plugin data on uninstall** — if enabled, deleting the plugin (not just deactivating it) also drops its database tables and settings. Off by default, so your data is never lost by accident.

If you enable the **Privacy consent checkbox** on your Contact Form, it links to a "Privacy & Data Retention" section so visitors know how their data is handled.
======================================================================================================================================================================================================================
## 8. Using the Live Sandbox
======================================================================================================================================================================================================================
**SI Simple Forms → Dashboard** has two buttons at the top: **Contact Form Live Sandbox** and **Subscription Form Live Sandbox**. Clicking either opens a full, working preview of that form exactly as your visitors will see it — useful for checking styling changes or testing a submission end-to-end without publishing the shortcode first.
======================================================================================================================================================================================================================
## 9. Troubleshooting
======================================================================================================================================================================================================================
**My form isn't showing up on the page.**
Double-check the shortcode ID matches the form you built (check **My Contact Forms** / **My Subscription Forms** for the correct shortcode) and that the page/post/widget actually renders shortcodes (some page builders require a dedicated "shortcode" block).

**Submissions aren't being blocked as spam, but I configured Turnstile.**
Make sure both the **Site Key** and **Secret Key** are filled in under Settings — Turnstile only activates when both are present. Also confirm the domain in your Cloudflare Turnstile site configuration matches your live site's domain.

**MailPoet sync isn't working.**
Confirm MailPoet is installed *and active*. The Dashboard's MailPoet status card will say "Not Installed" if MailPoet isn't detected.

**I don't see my old messages/subscribers after updating.**
The plugin never deletes existing data during an update. If something looks missing, check your retention settings under Settings — an aggressive retention window may have cleaned up older entries.
======================================================================================================================================================================================================================
## 10. Support
======================================================================================================================================================================================================================
For questions, bug reports, or feature requests, use the plugin's support forum on WordPress.org, or visit [plugins.shahinurislam.com](https://plugins.shahinurislam.com/).

