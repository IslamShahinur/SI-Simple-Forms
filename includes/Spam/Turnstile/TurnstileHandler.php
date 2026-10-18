<?php
namespace SI\SimpleForms\Spam\Turnstile;

defined('ABSPATH') || exit;

final class TurnstileHandler
{
    public function registerHooks()
    {
        add_action('wp_enqueue_scripts', [$this, 'enqueueScripts']);
    }

    public function isEnabled()
    {
        $siteKey = get_option('si_turnstile_site_key', '');
        $secretKey = get_option('si_turnstile_secret_key', '');
        return !empty($siteKey) && !empty($secretKey);
    }

    public function enqueueScripts()
    {
        if ($this->isEnabled()) {
            // phpcs:ignore PluginCheck.CodeAnalysis.EnqueuedResourceOffloading.OffloadedContent -- Cloudflare Turnstile is a remote challenge widget; it must be served from Cloudflare's own domain to function and cannot be self-hosted.
            wp_enqueue_script('cloudflare-turnstile', 'https://challenges.cloudflare.com/turnstile/v0/api.js', [], SI_SIMPLE_FORMS_VERSION, true);
        }
    }

    public function renderWidget()
    {
        $siteKey = esc_attr(get_option('si_turnstile_site_key', ''));
        if (empty($siteKey)) {
            return '';
        }
        return sprintf(
            '<div class="cf-turnstile" data-sitekey="%s" data-theme="auto"></div>',
            $siteKey
        );
    }

    public function verify($postData)
    {
        $response = $postData['cf-turnstile-response'] ?? '';
        $secretKey = get_option('si_turnstile_secret_key', '');

        if (empty($response) || empty($secretKey)) {
            return false;
        }

        $verifyUrl = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';
        $verifyReq = wp_remote_post($verifyUrl, [
            'body' => [
                'secret'   => $secretKey,
                'response' => $response,
                'remoteip' => isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])) : '',
            ],
        ]);

        if (is_wp_error($verifyReq)) {
            return false;
        }

        $body = json_decode(wp_remote_retrieve_body($verifyReq), true);
        return isset($body['success']) && $body['success'] === true;
    }
}