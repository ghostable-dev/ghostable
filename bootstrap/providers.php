<?php

return [
    App\Account\AccountServiceProvider::class,
    App\Auth\AuthServiceProdivder::class,
    App\Billing\BillingServiceProvider::class,
    App\Blog\BlogServiceProvider::class,
    App\Core\Providers\AppServiceProvider::class,
    App\Environment\EnvironmentServiceProvider::class,
    App\Integration\Integrations\Drata\DrataServiceProvider::class,
    App\Integration\Integrations\Slack\SlackServiceProvider::class,
    App\Integration\Integrations\Vanta\VantaServiceProvider::class,
    App\Organization\OrganizationServiceProvider::class,
    App\Project\ProjectServiceProvider::class,
    App\Core\Providers\FilamentPanelProvider::class,
    App\Secret\SecretServiceProvider::class,
    App\Messaging\MessagingServiceProvider::class,
];
