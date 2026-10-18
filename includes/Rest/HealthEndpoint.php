<?php
namespace SI\SimpleForms\Rest;

defined('ABSPATH') || exit;

final class HealthEndpoint
{
    public function register()
    {
        add_action('rest_api_init', function () {
            register_rest_route('si-simple-forms/v1', '/health', [
                'methods'             => 'GET',
                'callback'            => [$this, 'checkHealth'],
                'permission_callback' => '__return_true',
            ]);
        });
    }

    public function checkHealth()
    {
        return rest_ensure_response([
            'status'  => 'ok',
            'version' => SI_SIMPLE_FORMS_VERSION,
            'time'    => current_time('mysql'),
        ]);
    }
}