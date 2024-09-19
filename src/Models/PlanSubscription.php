<?php

declare(strict_types=1);

namespace DanPalmieri\SubscriptionEngine\Models;

use BadMethodCallException;
use DanPalmieri\SubscriptionEngine\Exceptions\UsageDenied;
use DanPalmieri\SubscriptionEngine\Services\Period;
use DanPalmieri\SubscriptionEngine\Traits\BelongsToPlan;
use DanPalmieri\SubscriptionEngine\Traits\HasFeatures;
use DanPalmieri\SubscriptionEngine\Traits\HasGracePeriod;
use DanPalmieri\SubscriptionEngine\Traits\HasGracePeriodUsage;
use DanPalmieri\SubscriptionEngine\Traits\HasPricing;
use DanPalmieri\SubscriptionEngine\Traits\HasSubscriptionPeriodUsage;
use DanPalmieri\SubscriptionEngine\Traits\HasTrialPeriodUsage;
use DanPalmieri\SubscriptionEngine\Traits\HasSchedules;
use Carbon\Carbon;
use DB;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Validation\Rule;
use LogicException;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use UnexpectedValueException;

class PlanSubscription extends Model
{
    use BelongsToPlan, HasUlids, HasSchedules, HasPricing, HasTrialPeriodUsage, HasSubscriptionPeriodUsage, HasGracePeriod, HasGracePeriodUsage;

    /**
     * {@inheritdoc}
     */
    protected $fillable = [
        'tag',
        'subscriber_id',
        'subscriber_type',
        'plan_id',
        'name',
        'description',
        'price',
        'currency',
        'trial_period',
        'trial_interval',
        'grace_period',
        'grace_interval',
        'invoice_period',
        'invoice_interval',
        'payment_method',
        'tier',
        'trial_ends_at',
        'starts_at',
        'ends_at',
        'cancels_at',
        'canceled_at',
    ];

    /**
     * {@inheritdoc}
     */
    protected $casts = [
        'tag' => 'string',
        'subscriber_type' => 'string',
        'price' => 'float',
        'currency' => 'string',
        'trial_period' => 'integer',
        'trial_interval' => 'string',
        'grace_period' => 'integer',
        'grace_interval' => 'string',
        'invoice_period' => 'integer',
        'invoice_interval' => 'string',
        'payment_method' => 'string',
        'tier' => 'integer',
        'trial_ends_at' => 'datetime',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'cancels_at' => 'datetime',
        'canceled_at' => 'datetime'
    ];

    /**
     * Create a new Eloquent model instance.
     *
     * @param array $attributes
     */
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $this->setTable(config('subscription_engine.tables.plan_subscriptions'));
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
            'tag' => [
                'required',
                'alpha_dash',
                'max:150',
                Rule::unique(config('subscription_engine.tables.plan_subscriptions'))->where(function ($query) {
                    return $query->where('id', '!=', $this->id)->where('subscriber_type', $this->subscriber_type)
                        ->where('subscriber_id', $this->subscriber_id);
                }),
            ],
            'subscriber_id' => 'required|integer',
            'subscriber_type' => 'required|string|max:150',
            'plan_id' => 'required|exists:' . config('subscription_engine.tables.plans') . ',id',
            'name' => 'required|string|max:150',
            'description' => 'nullable|string|max:32768',
            'price' => 'required|numeric',
            'currency' => 'required|alpha|size:3',
            'trial_period' => 'sometimes|integer|max:100000',
            'trial_interval' => 'sometimes|in:hour,day,week,month',
            'grace_period' => 'sometimes|integer|max:100000',
            'grace_interval' => 'sometimes|in:hour,day,week,month',
            'invoice_period' => 'sometimes|integer|max:100000',
            'invoice_interval' => 'sometimes|in:hour,day,week,month',
            'payment_method' => 'nullable|string',
            'tier' => 'nullable|integer|max:100000',
            'trial_ends_at' => 'nullable|date',
            'starts_at' => 'required|date',
            'ends_at' => 'required|date',
            'cancels_at' => 'nullable|date',
            'canceled_at' => 'nullable|date',
        ];
    }

    /**
     * Get the owning subscriber.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo
     */
    public function subscriber(): MorphTo
    {
        return $this->morphTo('subscriber', 'subscriber_type', 'subscriber_id', 'id');
    }

    /**
     * Cancel subscription.
     * When a fallback plan is set in config, subscription will never be cancelled but changed to that plan.
     * @param bool $immediately
     * @param bool $ignoreFallback
     * @return $this
     * @throws \Exception
     */
    public function cancel(bool $immediately = false, bool $ignoreFallback = false): PlanSubscription
    {
        if (!$ignoreFallback && config('subscription_engine.fallback_plan_tag')) { // Do not cancel if a fallback plan is set
            $plan = Plan::getByTag(config('subscription_engine.fallback_plan_tag'));
            if (!$plan) {
                throw new UnexpectedValueException('Fallback plan ' . config('subscription_engine.fallback_plan_tag') . ' does not exist.');
            }
            $this->changePlan($plan);
        } else {
            $this->canceled_at = Carbon::now();

            // If cancel is immediate, set end date
            if ($immediately) {
                // Cancel trial
                if ($this->isOnTrial()) $this->trial_ends_at = $this->canceled_at;

                // Cancel subscription
                $this->cancels_at = $this->canceled_at;
                $this->ends_at = $this->canceled_at;
            } else {
                // If cancel is not immediate, it will be cancelled at trial or period end
                $this->cancels_at = ($this->isOnTrial()) ? $this->trial_ends_at : $this->ends_at;
            }

            $this->save();
        }

        return $this;
    }

    /**
     * Uncancel subscription
     *
     * This action undoes all cancel flags
     *
     * @return $this
     */
    public function uncancel(): PlanSubscription
    {
        $this->canceled_at = null;
        $this->cancels_at = null;

        $this->save();

        return $this;
    }

    /**
     * Change subscription plan.
     *
     * @param Plan|PlanCombination $planCombination Plan or PlanCombination model instance of the desired change
     * @param bool $clearUsage Clear subscription usage
     * @param bool $syncInvoicing Synchronize billing frequency or leave it unchanged
     * @return $this
     * @throws \Exception
     */
    public function changePlan(Plan|PlanCombination $planCombination, bool $clearUsage = true, bool $syncInvoicing = true): PlanSubscription
    {
        // Synchronize subscription data with plan
        $this->syncPlan($planCombination, $syncInvoicing, true);

        return $this;
    }

    /**
     * Synchronize subscription data with plan
     * @param Plan|PlanCombination|null $planCombination Plan or Plan Combination to be synchronized
     * @param bool $syncInvoicing Synchronize billing frequency or leave it unchanged
     * @param bool $syncFeatures
     * @return PlanSubscription
     */
    public function syncPlan(Plan|PlanCombination $planCombination = null, bool $syncInvoicing = true, bool $syncFeatures = false): PlanSubscription
    {
        if ($planCombination instanceof PlanCombination) {
            // If it's a Plan Combination, use parent plan
            $plan = $planCombination->plan;
        } elseif ($planCombination instanceof Plan) {
            // If it's a Plan use it
            $plan = $planCombination;
        } else {
            // If neither plan combination is provided, just resync subscription parent plan data
            $plan = $planCombination = $this->plan;
        }

        $this->plan_id = $plan->id;
        $this->price = $planCombination->price;
        $this->currency = $planCombination->currency;
        $this->tier = $plan->tier;
        $this->grace_interval = $plan->grace_interval;
        $this->grace_period = $plan->grace_period;

        if ($syncInvoicing) {
            // Set same invoicing as selected plan
            $this->invoice_interval = $planCombination->invoice_interval;
            $this->invoice_period = $planCombination->invoice_period;
        }

        $this->save();

        return $this;
    }

    /**
     * Renew subscription period.
     *
     * @param int $periods Number of periods to renew
     * @return $this
     * @throws \Exception
     */
    public function renew(int $periods = 1): PlanSubscription
    {
        if ($this->isCanceled()) {
            throw new LogicException('Unable to renew canceled subscription.');
        }

        DB::transaction(function () use ($periods) {
            // End trial
            if ($this->isOnTrial()) {
                $this->trial_ends_at = Carbon::now();
            }

            $isNew = !$this->starts_at; // Has never started subscription, so is new

            if ($isNew) {
                $period = new Period($this->invoice_interval, $this->invoice_period * $periods, Carbon::now());

                // If is new, period will need to have start and end date
                $this->starts_at = $period->getStartDate();
                $this->ends_at = $period->getEndDate();

                // Adjust ending dates depending on trial modes
                if ($this->plan->trial_mode === 'inside') {
                    // If trial time is considered time of subscription
                    // we renew subscription and substract from period used days
                    $this->ends_at->subDays($this->getTrialPeriodUsageIn('day'));
                } else if ($this->plan->trial_mode === 'outside') {
                    // Don't penalize early buyers
                    $this->ends_at->addDays($this->getTrialPeriodRemainingUsageIn('day'));
                }
            } else {
                // If it's not a new subscription, there are two options about renewal
                // 1. Period was ended sometime ago and did not renew: Set again start and end date, so there
                // is no confusion about if the user had subscription active during the months that was inactive
                // 2. Period is ongoing: Set end date to calculated end period
                $startDate = $this->hasEnded() ? Carbon::now() : $this->ends_at;
                $period = new Period($this->invoice_interval, $this->invoice_period * $periods, $startDate);

                if ($this->hasEnded()) $this->starts_at = $period->getStartDate();
                $this->ends_at = $period->getEndDate();
            }

            $this->save();
        });

        return $this;
    }

    /**
     * Get bookings of the given subscriber.
     *
     * @param \Illuminate\Database\Eloquent\Builder $builder
     * @param \Illuminate\Database\Eloquent\Model $subscriber
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOfSubscriber(Builder $builder, Model $subscriber): Builder
    {
        return $builder->where('subscriber_type', $subscriber->getMorphClass())->where('subscriber_id', $subscriber->getKey());
    }

    /**
     * Scope subscriptions with ending trial.
     *
     * @param \Illuminate\Database\Eloquent\Builder $builder
     * @param int $dayRange
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeFindEndingTrial(Builder $builder, int $dayRange = 3): Builder
    {
        $from = Carbon::now();
        $to = Carbon::now()->addDays($dayRange);

        return $builder->whereBetween('trial_ends_at', [$from, $to]);
    }

    /**
     * Scope subscriptions with ended trial.
     *
     * @param \Illuminate\Database\Eloquent\Builder $builder
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeFindEndedTrial(Builder $builder): Builder
    {
        return $builder->where('trial_ends_at', '<=', now());
    }

    /**
     * Scope subscriptions with ending periods.
     *
     * @param \Illuminate\Database\Eloquent\Builder $builder
     * @param int $dayRange
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeFindEndingPeriod(Builder $builder, int $dayRange = 3): Builder
    {
        $from = Carbon::now();
        $to = Carbon::now()->addDays($dayRange);

        return $builder->whereBetween('ends_at', [$from, $to]);
    }

    /**
     * Scope subscriptions with ended periods.
     *
     * @param \Illuminate\Database\Eloquent\Builder $builder
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeFindEndedPeriod(Builder $builder): Builder
    {
        return $builder->where('ends_at', '<=', now());
    }

    /**
     * Scope subscriptions that are payment pending
     *
     * @param \Illuminate\Database\Eloquent\Builder $builder
     * @param Carbon|null $date Moment in time when to check
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeFindPendingPayment(Builder $builder, ?Carbon $date = null): Builder
    {
        if (!$date) {
            $date = Carbon::now();
        }

        return $builder->where(function (Builder $query) use ($date) {
            return $query->whereNull('canceled_at')
                ->orWhere('canceled_at', '>', $date);
        })
            ->where(function (Builder $query) use ($date) {
                return $query->whereNull('ends_at')
                    ->orWhere('ends_at', '<', $date);
            });
    }

    /**
     * Scope subscriptions by tag.
     *
     * @param \Illuminate\Database\Eloquent\Builder $builder
     * @param string $tag
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeGetByTag(Builder $builder, string $tag): Builder
    {
        return $builder->where('tag', $tag);
    }

}
