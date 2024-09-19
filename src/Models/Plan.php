<?php

declare(strict_types=1);

namespace DanPalmieri\SubscriptionEngine\Models;

use DanPalmieri\SubscriptionEngine\Exceptions\DuplicateException;
use DanPalmieri\SubscriptionEngine\Traits\HasFeatures;
use DanPalmieri\SubscriptionEngine\Traits\HasGracePeriod;
use DanPalmieri\SubscriptionEngine\Traits\HasPricing;
use DanPalmieri\SubscriptionEngine\Traits\HasSubscriptionPeriod;
use DanPalmieri\SubscriptionEngine\Traits\HasTrialPeriod;
use DanPalmieri\SubscriptionEngine\Traits\MorphsSchedules;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUlids;

/**
 * Class Plan
 * @package DanPalmieri\SubscriptionEngine\Models
 */
class Plan extends Model
{
    use SoftDeletes, HasFeatures, HasPricing, HasTrialPeriod, HasSubscriptionPeriod, HasGracePeriod, MorphsSchedules, HasUlids;

    /**
     * {@inheritdoc}
     */
    protected $fillable = [
        'tag',
        'name',
        'description',
        'is_active',
        'price',
        'signup_fee',
        'currency',
        'trial_period',
        'trial_interval',
        'trial_mode',
        'grace_period',
        'grace_interval',
        'invoice_period',
        'invoice_interval',
        'tier',
    ];

    /**
     * {@inheritdoc}
     */
    protected $casts = [
        'tag' => 'string',
        'is_active' => 'boolean',
        'price' => 'float',
        'signup_fee' => 'float',
        'currency' => 'string',
        'trial_period' => 'integer',
        'trial_interval' => 'string',
        'trial_mode' => 'string',
        'grace_period' => 'integer',
        'grace_interval' => 'string',
        'invoice_period' => 'integer',
        'invoice_interval' => 'string',
        'tier' => 'integer',
        'deleted_at' => 'datetime',
    ];

    /**
     * Create a new Eloquent model instance.
     *
     * @param array $attributes
     */
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $this->setTable(config('subscription_engine.tables.plans'));
    }

    public function getRouteKeyName(): string
    {
        return 'ulid';
    }

    public function uniqueIds(): array
    {
        return ['ulid'];
    }

    /**
     * Get validation rules
     * @return string[]
     */
    public function getRules(): array
    {
        return [
            'tag' => 'required|max:150|unique:' . config('subscription_engine.tables.plans') . ',tag',
            'name' => 'required|string|max:150',
            'description' => 'nullable|string|max:32768',
            'is_active' => 'sometimes|boolean',
            'price' => 'required|numeric',
            'signup_fee' => 'required|numeric',
            'currency' => 'required|alpha|size:3',
            'trial_period' => 'sometimes|integer|max:100000',
            'trial_interval' => 'sometimes|in:hour,day,week,month',
            'trial_mode' => 'required|in:inside,outside',
            'grace_period' => 'sometimes|integer|max:100000',
            'grace_interval' => 'sometimes|in:hour,day,week,month',
            'invoice_period' => 'sometimes|integer|max:100000',
            'invoice_interval' => 'sometimes|in:hour,day,week,month',
            'tier' => 'nullable|integer|max:100000'
        ];
    }

    public static function create(array $attributes = [])
    {
        if (static::where('tag', $attributes['tag'])->first()) {
            throw new DuplicateException();
        }

        return static::query()->create($attributes);
    }

    /**
     * Get plan by the given tag.
     *
     * @param string $tag
     * @return null|$this
     */
    static public function getByTag(string $tag): ?Plan
    {
        return static::where('tag', $tag)->first();
    }

    /**
     * The plan may have many combinations.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function combinations(): HasMany
    {
        return $this->hasMany(config('subscription_engine.models.plan_combination'), 'plan_id', 'id');
    }

    /**
     * The plan may have many features.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function features(): HasMany
    {
        return $this->hasMany(config('subscription_engine.models.plan_feature'), 'plan_id', 'id');
    }

    /**
     * The plan may have many subscriptions.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(config('subscription_engine.models.plan_subscription'), 'plan_id', 'id');
    }

    /**
     * Activate the plan.
     *
     * @return $this
     */
    public function activate()
    {
        $this->update(['is_active' => true]);

        return $this;
    }

    /**
     * Deactivate the plan.
     *
     * @return $this
     */
    public function deactivate()
    {
        $this->update(['is_active' => false]);

        return $this;
    }
}