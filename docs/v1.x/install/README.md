# Installation

Install the package via composer:

```shell
composer require bpuig/laravel-subby
```

Publish the configuration:

```shell
php artisan vendor:publish --tag=subby.config
```

Publish migrations:

```shell
php artisan vendor:publish --tag=subby.migrations
```

Migrate:

```shell
php artisan migrate
```

# Upgrade from v6.x to v7.x

This package need to be upgraded version by version to apply database changes. See [migrate v7.x](migrate-v7.md) for breaking
changes.


# Attach Subscriptions to model

**Laravel Subby** has been specially made for Eloquent. To add Subscription functionality to your User model just use
the `\DanPalmieri\SubscriptionEngine\Traits\HasSubscriptions` trait like this:

```php
namespace App\Models;

use DanPalmieri\SubscriptionEngine\Traits\HasSubscriptions;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use HasSubscriptions;
}
```

That's it, we only have to use that trait in our User model or any other model! Now your users may subscribe to plans.
Then you can import package's models wherever you need them or extend them in your own models.
