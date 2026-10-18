<?php
namespace SI\SimpleForms\Integrations;

defined('ABSPATH') || exit;

use SI\SimpleForms\Integrations\MailPoet\MailPoetService;
use SI\SimpleForms\Integrations\FluentCRM\FluentCRMService;
use SI\SimpleForms\Integrations\Mailchimp\MailchimpService;

final class IntegrationManager
{
    private static $instance = null;
    private $services = [];

    public static function instance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function init()
    {
        $this->services['MailPoet']  = new MailPoetService();
        $this->services['FluentCRM'] = new FluentCRMService();
        $this->services['Mailchimp'] = new MailchimpService();

        add_action('si_simple_forms_subscribe_created', [$this, 'handleSubscription'], 10, 2);
    }

    public function handleSubscription($subscriberData, $formConfig)
    {
        $serviceName = $formConfig['emailService'] ?? 'MailPoet';
        if (isset($this->services[$serviceName])) {
            return $this->services[$serviceName]->process($subscriberData, $formConfig);
        }
        return false;
    }
}