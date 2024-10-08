<?php

declare(strict_types=1);

namespace DanPalmieri\SubscriptionEngine\Models;

use DanPalmieri\SubscriptionEngine\Traits\BelongsToPlan;
use DanPalmieri\SubscriptionEngine\Traits\MorphsSchedules;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUlids;

/**
 * Class Plan Combination
 * @package DanPalmieri\SubscriptionEngine\Models
 */
class PlanCombination extends Model
{
    use BelongsToPlan, MorphsSchedules, HasUlids;

    /**
     * {@inheritdoc}
     */
    protected $fillable = [
        'tag',
        'country',
        'currency',
        'price',
        'signup_fee',
        'invoice_period',
        'invoice_interval'
    ];

    /**
     * {@inheritdoc}
     */
    protected $casts = [
        'tag' => 'string',
        'country' => 'string',
        'currency' => 'string',
        'price' => 'float',
        'signup_fee' => 'float',
        'invoice_period' => 'integer',
        'invoice_interval' => 'string'
    ];

    /**
     * Create a new Eloquent model instance.
     *
     * @param array $attributes
     */
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $this->setTable(config('subscription_engine.tables.plan_combinations'));
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
            'plan_id' => 'required|exists:' . config('subscription_engine.tables.plans') . ',id',
            'tag' => [
                'required',
                'alpha_dash',
                'max:300',
                Rule::unique(config('subscription_engine.tables.plan_combinations'))->where(function ($query) {
                    return $query->where('id', '!=', $this->id);
                }),
            ],
            'country' => 'required|alpha|size:3',
            'price' => 'required|numeric',
            'signup_fee' => 'required|numeric',
            'currency' => 'required|alpha|size:3',
            'invoice_period' => 'sometimes|integer|max:100000',
            'invoice_interval' => 'sometimes|in:hour,day,week,month'
        ];
    }

    /**
     * Get plan combination by the given tag.
     *
     * @param string $tag
     * @return null|$this
     */
    static public function getByTag(string $tag): ?PlanCombination
    {
        return static::where('tag', $tag)->first();
    }
}
