<?php
namespace SI\SimpleForms\Spam;

defined('ABSPATH') || exit;

use SI\SimpleForms\Spam\Turnstile\TurnstileHandler;
use SI\SimpleForms\Spam\Recaptcha\RecaptchaHandler;
use SI\SimpleForms\Spam\Honeypot\HoneypotHandler;

final class SpamProtectionManager
{
    private static $instance = null;
    private $handlers = [];

    public static function instance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function init()
    {
        // Register modules using standard WordPress hooks & native security guidelines
        $this->handlers['turnstile'] = new TurnstileHandler();
        $this->handlers['recaptcha'] = new RecaptchaHandler();
        $this->handlers['honeypot']  = new HoneypotHandler();

        foreach ($this->handlers as $handler) {
            $handler->registerHooks();
        }

        // Native filter hook so future 3rd-party anti-spam services can attach
        add_filter('si_simple_forms_validate_submission', [$this, 'verifySubmission'], 10, 2);
    }

    public function verifySubmission($isValid, $postData)
    {
        if (!$isValid) {
            return false;
        }

        // Filter through active anti-spam modules
        foreach ($this->handlers as $name => $handler) {
            if ($handler->isEnabled() && !$handler->verify($postData)) {
                /* translators: %s: name of the anti-spam handler (e.g. "turnstile", "honeypot"). */
                return new \WP_Error('spam_check_failed', sprintf(__('Spam check failed via %s', 'si-simple-forms'), $name));
            }
        }

        return true;
    }

    public function renderWidget($provider = 'turnstile')
    {
        if (isset($this->handlers[$provider]) && $this->handlers[$provider]->isEnabled()) {
            return $this->handlers[$provider]->renderWidget();
        }
        return '';
    }
}