<?php

namespace DanPalmieri\SubscriptionEngine\Exceptions;

class UsageDenied extends LaravelSubscriptionEngineException
{
    public function __construct($featureTag = '')
    {
        $message = "Usage of '{$featureTag}' has been denied.";

        parent::__construct($message);
    }
}
