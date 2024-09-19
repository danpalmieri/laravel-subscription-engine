<?php

namespace DanPalmieri\SubscriptionEngine\Exceptions;

class InvalidPlanSubscription extends LaravelSubscriptionEngineException
{
    public function __construct($subscriptionTag = "")
    {
        $message = "Subscription '{$subscriptionTag}' not found.";

        parent::__construct($message);
    }
}
