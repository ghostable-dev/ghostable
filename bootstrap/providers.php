<?php


return [
    App\Core\Providers\AppServiceProvider::class,
    
    //App\Core\Providers\FortifyServiceProvider::class,
    App\Account\Providers\AccountServiceProvider::class,
    
    
    App\Environment\Providers\EnvironmentServiceProvider::class,
    App\Team\TeamServiceProvider::class,
    App\Auth\AuthServiceProdivder::class,
];
