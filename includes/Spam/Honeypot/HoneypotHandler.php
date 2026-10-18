<?php
namespace SI\SimpleForms\Spam\Honeypot;

defined('ABSPATH') || exit;

final class HoneypotHandler
{
    public function registerHooks() {}
    public function isEnabled() { return true; }
    public function renderWidget() {
        return '<div style="display:none !important;"><input type="text" name="si_hp_trap" value="" tabindex="-1" autocomplete="off" /></div>';
    }
    public function verify($postData) {
        return empty($postData['si_hp_trap']);
    }
}