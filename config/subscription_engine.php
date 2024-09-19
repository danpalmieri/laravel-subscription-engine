<?php

declare(strict_types=1);

return [
    'main_subscription_tag' => 'main',
    'fallback_plan_tag' => null,
    
    'tables' => [
        'plans' => 'plans',
        'plan_combinations' => 'plan_combinations',
        'plan_features' => 'plan_features',
        'plan_subscriptions' => 'plan_subscriptions',
        'plan_subscription_features' => 'plan_subscription_features',
        'plan_subscription_schedules' => 'plan_subscription_schedules',
        'plan_subscription_usage' => 'plan_subscription_usage',
    ],
    
    'models' => [
        'plan' => \DanPalmieri\SubscriptionEngine\Models\Plan::class,
        'plan_combination' => \DanPalmieri\SubscriptionEngine\Models\PlanCombination::class,
        'plan_feature' => \DanPalmieri\SubscriptionEngine\Models\PlanFeature::class,
        'plan_subscription' => \DanPalmieri\SubscriptionEngine\Models\PlanSubscription::class,
        'plan_subscription_feature' => \DanPalmieri\SubscriptionEngine\Models\PlanSubscriptionFeature::class,
        'plan_subscription_schedule' => \DanPalmieri\SubscriptionEngine\Models\PlanSubscriptionSchedule::class,
        'plan_subscription_usage' => \DanPalmieri\SubscriptionEngine\Models\PlanSubscriptionUsage::class,
    ],

    'services' => [
        'payment_methods' => [
            'free' => \DanPalmieri\SubscriptionEngine\Services\PaymentMethods\Free::class
        ]
    ]
];
