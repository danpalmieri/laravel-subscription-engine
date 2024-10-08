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
        Schema::create(config('subscription_engine.tables.plan_subscription_usage'), function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('plan_subscription_feature_id')->unique();
            $table->unsignedBigInteger('used');
            $table->timestamp('valid_until')->nullable();
            $table->timestamps();

            $table->foreign('plan_subscription_feature_id')->references('id')->on(config('subscription_engine.tables.plan_subscription_features'))
                ->onDelete('cascade')->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists(config('subscription_engine.tables.plan_subscription_usage'));
    }
};