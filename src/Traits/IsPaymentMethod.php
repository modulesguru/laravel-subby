<?php

namespace Bpuig\Subby\Traits;

use Bpuig\Subby\Models\PlanSubscription;
use Bpuig\Subby\Models\PlanSubscriptionSchedule;

trait IsPaymentMethod
{
    private ?PlanSubscriptionSchedule $planSubscriptionSchedule = null;
    private ?PlanSubscription $planSubscription;
    private float $amount;
    private string $currency;

    /**
     * Set subscription to charge payment
     *
     * @param PlanSubscription $planSubscription
     * @return $this
     */
    public function subscription(PlanSubscription $planSubscription): static
    {
        $this->planSubscription = $planSubscription;

        return $this;
    }

    /**
     * Set schedule to collect payment
     * @param PlanSubscriptionSchedule|null $planSubscriptionSchedule
     * @return $this
     */
    public function schedule(?PlanSubscriptionSchedule $planSubscriptionSchedule = null): static
    {
        $this->planSubscriptionSchedule = $planSubscriptionSchedule;

        return $this;
    }

    /**
     * Set the amount to charge
     * @param $amount
     * @return $this
     */
    public function amount($amount = null): static
    {
        $this->amount = $amount;

        return $this;
    }

    /**
     * Set transaction currency
     * @param string|null $currency
     * @return $this
     */
    public function currency(?string $currency = null): static
    {
        $this->currency = $currency;

        return $this;
    }

    public function execute(): void
    {
        if ($this->planSubscriptionSchedule) {
            $this->executeSchedule();
        } else {
            $this->executeRenewal();
        }
    }

    /**
     * Execute the strategy
     * Try charging via default payment method and then change plan
     * @throws \Exception
     */
    private function executeSchedule(): void
    {
        try {
            $this->charge();
        } catch (\Exception $exception) {
            $this->planSubscriptionSchedule->fail();
            throw new \Exception($exception->getMessage(), $exception->getCode());
        }

        $this->planSubscriptionSchedule->changeSubscriptionPlan(true, true);
    }

    /**
     * Execute the strategy
     * Try charging via default payment method and then renew subscription
     */
    private function executeRenewal(): void
    {
        try {
            $this->charge();
        } catch (\Exception $exception) {
            throw new \Exception($exception->getMessage(), $exception->getCode());
        }

        $this->planSubscription->renew();
    }
}
