<?php

declare(strict_types=1);

namespace DanPalmieri\SubscriptionEngine;

use Illuminate\Support\ServiceProvider;

class SubscriptionEngineServiceProvider extends ServiceProvider
{

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register(): void
    {

    }

    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot(): void
    {
        $this->publishConfig();
        $this->publishMigrations();
    }

    /**
     * Publish package config.
     *
     * @return void
     */
    protected function publishConfig()
    {
        $this->publishes([
            __DIR__ . '/../config/config.php' => config_path('subscription_engine.php')
        ], 'subscription_engine.config');
    }


    /**
     * Publish package migrations.
     *
     * @return void
     */
    protected function publishMigrations()
    {
        $this->publishes([
            __DIR__ . '/../database/migrations/create_plans_table.php' => database_path('migrations/' . date('Y_m_d_His', time()) . '_create_plans_table.php'),
            __DIR__ . '/../database/migrations/create_features_table.php' => database_path('migrations/' . date('Y_m_d_His', time() + 1) . '_create_features_table.php'),
            __DIR__ . '/../database/migrations/create_plan_subscriptions_table.php' => database_path('migrations/' . date('Y_m_d_His', time() + 2) . '_create_plan_subscriptions_table.php'),
            __DIR__ . '/../database/migrations/create_plan_subscription_features_table.php' => database_path('migrations/' . date('Y_m_d_His', time() + 3) . '_create_plan_subscription_features_table.php'),
            __DIR__ . '/../database/migrations/create_plan_subscription_usage_table.php' => database_path('migrations/' . date('Y_m_d_His', time() + 4) . '_create_plan_subscription_usage_table.php'),
            __DIR__ . '/../database/migrations/create_plan_subscription_schedules_table.php' => database_path('migrations/' . date('Y_m_d_His', time() + 5) . '_create_plan_subscription_schedules_table.php'),
            __DIR__ . '/../database/migrations/create_plan_combinations_table.php' => database_path('migrations/' . date('Y_m_d_His', time() + 6) . '_create_plan_combinations_table.php')
        ], 'subscription_engine.migrations');

        $this->publishes([
            __DIR__ . '/../database/migrations/v1.0.0/alter_plan_combinations_table.php' => database_path('migrations/' . date('Y_m_d_His', time()) . '_alter_plan_combinations_table.php'),
        ], 'subscription_engine.migrations.v1');
    }

}
