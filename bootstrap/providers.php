<?php

use App\Account\AccountServiceProvider;
use App\Auth\AuthServiceProdivder;
use App\Billing\BillingServiceProvider;
use App\Blog\BlogServiceProvider;
use App\Core\Providers\AppServiceProvider;
use App\Core\Providers\FeatureFlagServiceProvider;
use App\Core\Providers\FilamentPanelProvider;
use App\Crypto\CryptoServiceProvider;
use App\Environment\EnvironmentServiceProvider;
use App\Integration\Integrations\Drata\DrataServiceProvider;
use App\Integration\Integrations\Slack\SlackServiceProvider;
use App\Integration\Integrations\Vanta\VantaServiceProvider;
use App\Integration\IntegrationServiceProvider;
use App\Messaging\MessagingServiceProvider;
use App\Organization\OrganizationAuditWebhookServiceProvider;
use App\Organization\OrganizationServiceProvider;
use App\Project\ProjectServiceProvider;

return [
    AccountServiceProvider::class,
    AuthServiceProdivder::class,
    BillingServiceProvider::class,
    BlogServiceProvider::class,
    CryptoServiceProvider::class,
    AppServiceProvider::class,
    FeatureFlagServiceProvider::class,
    IntegrationServiceProvider::class,
    EnvironmentServiceProvider::class,
    DrataServiceProvider::class,
    SlackServiceProvider::class,
    VantaServiceProvider::class,
    OrganizationServiceProvider::class,
    OrganizationAuditWebhookServiceProvider::class,
    ProjectServiceProvider::class,
    FilamentPanelProvider::class,
    MessagingServiceProvider::class,
];
