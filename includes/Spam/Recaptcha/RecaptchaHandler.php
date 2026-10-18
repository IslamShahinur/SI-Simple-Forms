<?php
namespace SI\SimpleForms\Spam\Recaptcha;

defined('ABSPATH') || exit;

final class RecaptchaHandler
{
    public function registerHooks() {}
    public function isEnabled() { return false; }
    public function renderWidget() { return ''; }
    public function verify($postData) { return true; }
}