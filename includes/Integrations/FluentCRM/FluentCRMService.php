<?php
namespace SI\SimpleForms\Integrations\FluentCRM;

defined('ABSPATH') || exit;

final class FluentCRMService
{
    public function isAvailable() {
        return defined('FLUENTCRM');
    }

    public function process($subscriberData, $formConfig) {
        // Future implementation module for FluentCRM
        return false;
    }
}