<?php

return [
    App\Account\AccountServiceProvider::class,
    App\Auth\AuthServiceProdivder::class,
    App\Core\Providers\AppServiceProvider::class,
    App\Environment\EnvironmentServiceProvider::class,
    App\Project\ProjectServiceProvider::class,
    App\Team\TeamServiceProvider::class,
    App\Billing\BillingServiceProvider::class,
    App\Integrations\Drata\DrataServiceProvider::class,
    App\Integrations\Vanta\VantaServiceProvider::class,
];
