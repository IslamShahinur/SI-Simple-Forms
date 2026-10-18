<?php
namespace SI\SimpleForms\Integrations\Mailchimp;

defined('ABSPATH') || exit;

final class MailchimpService
{
    public function isAvailable() {
        return get_option('si_mailchimp_api_key', '') !== '';
    }

    public function process($subscriberData, $formConfig) {
        // Future implementation module for Mailchimp API v3
        return false;
    }
}