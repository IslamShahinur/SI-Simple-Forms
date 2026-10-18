<?php
namespace SI\SimpleForms\Integrations\MailPoet;

defined('ABSPATH') || exit;

use SI\SimpleForms\Core\Logger;

final class MailPoetService
{
    public function isAvailable()
    {
        return class_exists('\MailPoet\API\API');
    }

    public function process($subscriberData, $formConfig)
    {
        if (!$this->isAvailable()) {
            return false;
        }

        try {
            $mailpoetApi = \MailPoet\API\API::MP('v1');
            $email = $subscriberData['email'];
            $listName = $formConfig['mailpoetList'] ?? 'Newsletter';
            $tags = $formConfig['subscriberTags'] ?? ['Website'];

            $subscriber = [
                'email' => $email,
                'first_name' => $subscriberData['name'] ?? '',
            ];

            // Automatic integration with MailPoet lists & tags
            $lists = $mailpoetApi->getLists();
            $targetListId = null;
            foreach ($lists as $l) {
                if ($l['name'] === $listName) {
                    $targetListId = $l['id'];
                    break;
                }
            }

            if ($targetListId) {
                $mailpoetApi->addSubscriber($subscriber, [$targetListId]);
            }
            return true;
        } catch (\Exception $e) {
            Logger::log('MailPoet Sync', $e->getMessage());
            return false;
        }
    }
}