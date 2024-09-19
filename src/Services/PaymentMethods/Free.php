<?php

declare(strict_types=1);

namespace DanPalmieri\SubscriptionEngine\Services\PaymentMethods;

use DanPalmieri\SubscriptionEngine\Contracts\PaymentMethodService;
use DanPalmieri\SubscriptionEngine\Traits\IsPaymentMethod;

class Free implements PaymentMethodService
{
    use IsPaymentMethod;

    /**
     * Charge desired amount
     * @return void
     */
    public function charge()
    {
        // Nothing is charged, no exception is raised
    }
}
