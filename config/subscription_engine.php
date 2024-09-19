<?php

declare(strict_types=1);

return [
    'main_subscription_tag' => 'main',
    'fallback_plan_tag' => null,
    
    'tables' => [
        'plans' => 'plans',
        'plan_combinations' => 'plan_combinations',
        'plan_subscriptions' => 'plan_subscriptions',
        'plan_subscription_schedules' => 'plan_subscription_schedules',
    ],
    
    'models' => [
        'plan' => \DanPalmieri\SubscriptionEngine\Models\Plan::class,
        'plan_combination' => \DanPalmieri\SubscriptionEngine\Models\PlanCombination::class,
        'plan_subscription' => \DanPalmieri\SubscriptionEngine\Models\PlanSubscription::class,
        'plan_subscription_schedule' => \DanPalmieri\SubscriptionEngine\Models\PlanSubscriptionSchedule::class,
    ],

    'services' => [
        'payment_methods' => [
            'free' => \DanPalmieri\SubscriptionEngine\Services\PaymentMethods\Free::class
        ]
    ]
];
