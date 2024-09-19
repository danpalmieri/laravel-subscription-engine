<?php

namespace DanPalmieri\SubscriptionEngine\Traits;

trait MorphsSchedules
{
    /**
     * Get all schedules.
     */
    public function schedules()
    {
        return $this->morphMany(config('subscription_engine.models.plan_subscription_schedule'), 'scheduleable');
    }
}
