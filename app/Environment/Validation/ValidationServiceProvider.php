<?php

namespace App\Environment\Validation;

use App\Environment\Validation\Enums\EnvironmentVariableRuleType;
use App\Environment\Validation\Factories\KeyRuleProviderCollectionFactory;
use App\Environment\Validation\Rules\BooleanKeyRule;
use App\Environment\Validation\Rules\EmailKeyRule;
use App\Environment\Validation\Rules\EnumKeyRule;
use App\Environment\Validation\Rules\IntegerKeyRule;
use App\Environment\Validation\Rules\StringKeyRule;
use App\Environment\Validation\Rules\UrlKeyRule;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\ServiceProvider;

class ValidationServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->registerKeyRuleProviders();
    }

    /**
     * Register the default set of KeyRuleProvider classes
     * for each EnvironmentVariableRuleType.
     *
     * This maps enum types (e.g. STRING, INTEGER) to their
     * corresponding rule handler classes used during validation.
     */
    private function registerKeyRuleProviders(): void
    {
        $this->app->singleton(KeyRuleProviderCollectionFactory::class, function () {
            $factory = new KeyRuleProviderCollectionFactory;
            $factory->register(EnvironmentVariableRuleType::BOOLEAN, BooleanKeyRule::class);
            $factory->register(EnvironmentVariableRuleType::EMAIL, EmailKeyRule::class);
            $factory->register(EnvironmentVariableRuleType::ENUM, EnumKeyRule::class);
            $factory->register(EnvironmentVariableRuleType::INTEGER, IntegerKeyRule::class);
            $factory->register(EnvironmentVariableRuleType::STRING, StringKeyRule::class);
            $factory->register(EnvironmentVariableRuleType::URL, UrlKeyRule::class);

            return $factory;
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        Relation::enforceMorphMap([
            'rule' => 'App\Environment\Validation\Models\EnvironmentVariableRule',
        ]);
    }
}
