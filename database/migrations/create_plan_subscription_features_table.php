<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create(config('subscription_engine.tables.plan_subscription_features'), function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('plan_subscription_id');
            $table->unsignedBigInteger('plan_feature_id')->nullable();
            $table->string('tag');
            $table->string('name');
            $table->string('description')->nullable();
            $table->string('value');
            $table->unsignedSmallInteger('resettable_period')->default(0);
            $table->string('resettable_interval')->default('month');
            $table->unsignedMediumInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['plan_subscription_id', 'tag']);

            $table->foreign('plan_subscription_id')->references('id')->on(config('subscription_engine.tables.plan_subscriptions'))
                ->onDelete('cascade')->onUpdate('cascade');

            $table->foreign('plan_feature_id')->references('id')->on(config('subscription_engine.tables.plan_features'))
                ->onDelete('set null')->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists(config('subscription_engine.tables.plan_subscription_features'));
    }
};