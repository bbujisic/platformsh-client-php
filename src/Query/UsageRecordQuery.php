<?php

namespace Platformsh\Client\Query;

use Platformsh\Client\Query\Param\OwnerFilterTrait;
use Platformsh\Client\Query\Param\VendorFilterTrait;

class UsageRecordQuery extends Query
{

    use OwnerFilterTrait;
    use VendorFilterTrait;

    /**
     * Restrict the query to a date/time period.
     *
     * @param \DateTime|null $start
     * @param \DateTime|null $end
     */
    public function setPeriod(\DateTime $start = null, \DateTime $end = null): self
    {
        $this->setFilter('start', ($start !== null ? $start->format('c') : null));
        $this->setFilter('end', ($end !== null ? $end->format('c') : null));

        return $this;
    }

    /**
     * Restrict the query to a single subscription.
     *
     * @param int|null $subscriptionId
     */
    public function setSubscriptionId(int $subscriptionId = null): self
    {
        $this->setFilter('subscription_id', $subscriptionId);

        return $this;
    }

    /**
     * Restrict the query to a single usage group.
     *
     * @param string|null $usageGroup
     */
    public function setUsageGroup(string $usageGroup = null): self
    {
        if ($usageGroup) {
            $this->validateList('usage_group', $usageGroup, ['storage', 'environments', 'user_licenses']);
        }
        $this->setFilter('usage_group', $usageGroup);

        return $this;
    }

    /**
     * Restrict the query to a single usage group.
     *
     * @param string|null $usageGroup
     */
    public function setPlanRecord(int $planRecord = null): self
    {
        $this->setFilter('plan_record', $planRecord);

        return $this;
    }

}
