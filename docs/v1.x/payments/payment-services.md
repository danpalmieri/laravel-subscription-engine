# Payment services

[[toc]]

## Usage

A payment service is a class that processes the payment defined in the subscription column `payment_method`.

## Create a payment service

### Create the service

Create a new laravel class in your project and implement `DanPalmieri\SubscriptionEngine\Contracts\PaymentMethodService`. Looking at
the `Free`
service would be a good starting point.

```php
<?php

declare(strict_types=1);

namespace DanPalmieri\SubscriptionEngine\Services\PaymentMethods;

use DanPalmieri\SubscriptionEngine\Contracts\PaymentMethodService;

class Free implements PaymentMethodService
{
    /**
     * Charge desired amount
     * @return void
     */
    public function charge()
    {
        // Nothing is charged, no exception is raised
    }
}
```

#### Create your methods

In the following code, see an example of what could be a payment method service for a fictional credit card payment
processor.

```php
<?php

declare(strict_types=1);

namespace PaymentMethods;

use DanPalmieri\SubscriptionEngine\Contracts\PaymentMethodService;
use Bank\BankPackages\YourPaymentProcessor;
use DanPalmieri\SubscriptionEngine\Traits\IsPaymentMethod;

class CreditCard implements PaymentMethodService
{
    use IsPaymentMethod;

    private $amount;
    private $currency;
    private $creditCard;

    /**
     * You would need to retrieve whatever data you need from $this->subscription relationships
     * @return void
     */
    private function retrieveCreditCard() {
       $this->creditCard = $this->subscription->user->creditCards()->default;
    }
    
    /**
     * Charge desired amount with your favorite bank
     * @return void
     */
    public function charge()
    {
        $processor = new YourPaymentProcessor();
        $processor->setParameter('MERCHANT_CURRENCY', $this->currency);
        $processor->setParameter('MERCHANT_AMOUNT', $this->amount);
        $processor->setParameter('MERCHANT_CARD', $this->creditCard);
        $processor->pay();
    }
}
```

### Make the service available

In your config file, add a name and the path of your new payment method:

```php 
'services' => [
        'schedule' => \DanPalmieri\SubscriptionEngine\Services\ScheduleService::class,
        'renewal' => \DanPalmieri\SubscriptionEngine\Services\RenewalService::class,
        'payment_methods' => [
            'free' => \DanPalmieri\SubscriptionEngine\Services\PaymentMethods\Free::class,
            'credit_card' => \PaymentMethods\CreditCard::class,
        ]
]
```
