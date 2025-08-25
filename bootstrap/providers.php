<?php

return [
    App\Account\AccountServiceProvider::class,
    App\Auth\AuthServiceProdivder::class,
    App\Core\Providers\AppServiceProvider::class,
    App\Environment\EnvironmentServiceProvider::class,
    App\Project\ProjectServiceProvider::class,
    App\Organization\OrganizationServiceProvider::class,
    App\Secret\SecretServiceProvider::class,
    App\Billing\BillingServiceProvider::class,
    App\Integration\Integrations\Drata\DrataServiceProvider::class,
    App\Integration\Integrations\Vanta\VantaServiceProvider::class,
    App\Integration\Integrations\Slack\SlackServiceProvider::class,
];
