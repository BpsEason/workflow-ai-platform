<?php

return [

    /*
     * The HTML <title> tag for the generated documentation. If this is empty,
     * Scribe will automatically generate a title for you.
     */
    'title' => 'Workflow AI Platform API Documentation',

    /*
     * The name of the "group" (collection of endpoints) that will be used when
     * Scribe can't find a group for an endpoint.
     *
     * It will also be used in the navigation for API authentication.
     */
    'default_group' => 'Other',

    /*
     * The router to be used to parse your routes.
     * You can add a custom router by extending `\Scribe\Extractors\RouteMatchers\BaseRouteMatcher`
     * and adding the custom router to the 'matchers' array below.
     *
     * If you are having issues with custom route matchers, please see the
     * "Adding A Custom Route Matcher" section of the Scribe docs.
     */
    'router' => 'laravel',

    /*
     * The type of documentation output to generate.
     * - 'static' for a single HTML file (can be extended with custom Blade views)
     * - 'openapi' for an OpenAPI (Swagger) specification file
     * - 'postman' for a Postman collection
     */
    'type' => 'static',

    /*
     * The location where the generated documentation will be stored.
     * - For 'static' type, this is a directory path.
     * - For 'openapi' or 'postman' type, this is a file path.
     */
    'output_path' => 'public/docs',

    /*
     * The list of routes to include in the documentation.
     * You can use wildcards (e.g. 'api/*') to include all routes in a specific group.
     * Example: ['api/*', 'web/v2/*']
     *
     * If you are having issues with these settings, please see the
     * "Route Filtering" section of the Scribe docs.
     */
    'routes' => [
        [
            'matches' => [
                'api/*', // 包含所有 /api 路由
            ],
            'include' => [
                'api/register',
                'api/login',
                'api/logout',
                'api/user',
                'api/documents/*',
                'api/voice/*',
            ],
            'exclude' => [
                // 排除不需要的路由，例如 Webhooks
            ],
            'apply_request_headers' => [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ],
        ],
    ],

    /*
     * Where Scribe should get the API "base URL" from.
     * The base URL will be displayed in the docs and used to make example requests.
     * Choose one of:
     * - 'laravel_config': gets the URL from Laravel's config (app.url)
     * - 'scribe_config': gets the URL from this config file's 'base_url' setting
     * - 'app_url': gets the URL from the APP_URL environment variable
     * - 'custom': use your own custom URL (specify in 'base_url' below)
     *
     * If you are having issues with this setting, please see the
     * "Base URL" section of the Scribe docs.
     */
    'base_url_source' => 'app_url',

    /*
     * If 'base_url_source' is 'custom', specify the URL here.
     */
    'base_url' => null,

    /*
     * Advanced: Specify whether to generate example requests, and if so, how.
     * - 'php': generate PHP example requests
     * - 'python': generate Python example requests
     * - 'curl': generate cURL example requests
     * - 'javascript': generate JavaScript example requests
     *
     * You can also specify an array of these (e.g. ['php', 'python']).
     * If you want to customize the generated examples, see the Scribe docs.
     */
    'example_requests' => ['curl', 'javascript'],

    /*
     * Advanced: Custom headers to add to the example requests.
     * These will be added in addition to any headers Scribe already adds (e.g., Content-Type).
     */
    'example_request_headers' => [
        'Authorization' => 'Bearer {YOUR_AUTH_TOKEN}',
    ],

    /*
     * Advanced: How to authenticate API requests.
     * This will be used to generate the "Authentication" section in the docs.
     * Choose one of:
     * - 'bearer': For Bearer tokens (e.g., Sanctum).
     * - 'query': For API keys in the query string.
     * - 'body': For API keys in the request body.
     * - 'basic': For Basic HTTP authentication.
     * - 'custom': Use your own custom authentication settings (specify in 'extra_description').
     *
     * If you are having issues with these settings, please see the
     * "Authentication" section of the Scribe docs.
     */
    'auth' => [
        'enabled' => true,
        'type' => 'bearer',
        'name' => 'Authorization', // header or query param name
        'in' => 'header', // header or query
        'placeholder' => '{YOUR_AUTH_TOKEN}', // placeholder for the example token
        'extra_description' => 'You can obtain an API token by logging in via the `/api/login` endpoint.',
    ],

    /*
     * Advanced: Custom "security schemes" to define in the OpenAPI spec.
     * This will only be used if 'type' is 'openapi'.
     */
    'openapi_security_schemes' => [
        'sanctum' => [
            'type' => 'http',
            'scheme' => 'bearer',
            'bearerFormat' => 'api_token',
        ],
    ],

    /*
     * Advanced: Custom "tags" to define in the OpenAPI spec.
     * This will only be used if 'type' is 'openapi'.
     */
    'openapi_tags' => [
        [
            'name' => 'Auth',
            'description' => 'User authentication and authorization endpoints.',
        ],
        [
            'name' => 'Document Management',
            'description' => 'Endpoints for uploading, searching, and managing documents.',
        ],
        [
            'name' => 'Voice Assistant',
            'description' => 'Endpoints for voice input processing and conversation history.',
        ],
    ],

    /*
     * Advanced: Strategies for generating example responses.
     * - 'responses_from_attributes': extract from @response/@responseFile tags
     * - 'responses_from_api_resource': generate from Laravel API Resources
     * - 'responses_from_database': generate by creating and fetching dummy models
     * - 'responses_from_factory': generate by using factories
     *
     * If you are having issues with these settings, please see the
     * "Example Responses" section of the Scribe docs.
     */
    'examples' => [
        'enabled' => [
            'responses_from_attributes' => true,
            'responses_from_api_resource' => false,
            'responses_from_database' => false,
            'responses_from_factory' => false,
        ],
        /*
         * Advanced: Custom strategies for example generation.
         * To define a custom strategy, create a class that implements
         * \Scribe\Extractors\ExampleResponses\BaseStrategy and add it here.
         */
        'strategies' => [
            // \App\Docs\MyCustomResponseStrategy::class,
        ],
    ],

    /*
     * Advanced: Where to get route information from.
     *
     * If you are having issues with these settings, please see the
     * "Router Filtering" section of the Scribe docs.
     */
    'router_groups' => [
        //
    ],

    /*
     * Advanced: Configuration for generating API descriptions from Markdown files.
     */
    'markdown' => [
        'enabled' => true,
        'path' => 'resources/docs',
    ],

    /*
     * Advanced: Configuration for Laravel Nova.
     */
    'laravel_nova' => [
        'enabled' => false,
        'path' => '/nova-api',
    ],

    /*
     * Advanced: Other settings.
     */
    'postman' => [
        'name' => 'Workflow AI Platform Postman Collection',
        'description' => 'A Postman collection for the Workflow AI Platform API.',
        'version' => '1.0.0',
    ],
    'openapi' => [
        'version' => '3.0.0',
        'format' => 'json',
        'base_path' => '/api',
    ],
];
