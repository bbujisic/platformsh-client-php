<?php

namespace Platformsh\Client\Query;


use Platformsh\Client\Query\Param\OwnerFilterTrait;
use Platformsh\Client\Query\Param\AllTrait;
use Platformsh\Client\Query\Param\VendorFilterTrait;

class SubscriptionQuery extends Query
{

    use OwnerFilterTrait;
    use VendorFilterTrait;
    use AllTrait;

    /**
     * Restrict the query to a status.
     *
     * @param string|null $status
     */
    public function setStatus(?string $status): self
    {
        $this->validateList('status', $status, ['active', 'requested', 'provisioning', 'provisioning failure', 'suspended', 'deleted']);
        $this->setFilter('status', $status);

        return $this;
    }

    /**
     * Restrict the query to a support tier.
     *
     * @param string|null $supportTier
     */
    public function setSupportTier(?string $supportTier): self
    {
        $this->validateList('support_tier', $supportTier, ['trial', 'standard', 'premier', 'enterprise', 'internal']);
        $this->setFilter('support_tier', $supportTier);

        return $this;
    }
}