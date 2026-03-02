<?php

return [
    App\Account\AccountServiceProvider::class,
    App\Auth\AuthServiceProdivder::class,
    App\Billing\BillingServiceProvider::class,
    App\Blog\BlogServiceProvider::class,
    \App\Crypto\CryptoServiceProvider::class,
    App\Core\Providers\AppServiceProvider::class,
    App\Core\Providers\FeatureFlagServiceProvider::class,
    App\Integration\IntegrationServiceProvider::class,
    App\Environment\EnvironmentServiceProvider::class,
    App\Integration\Integrations\Drata\DrataServiceProvider::class,
    App\Integration\Integrations\Slack\SlackServiceProvider::class,
    App\Integration\Integrations\Vanta\VantaServiceProvider::class,
    App\Organization\OrganizationServiceProvider::class,
    App\Organization\OrganizationAuditWebhookServiceProvider::class,
    App\Project\ProjectServiceProvider::class,
    App\Core\Providers\FilamentPanelProvider::class,
    App\Messaging\MessagingServiceProvider::class,
];
