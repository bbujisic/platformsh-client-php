<?php

namespace Platformsh\Client\Query;

use Platformsh\Client\Query\Param\OwnerFilterTrait;

class PlanRecordQuery extends Query
{

    use OwnerFilterTrait;

    /**
     * Restrict the query to a date/time period.
     *
     * @param \DateTime|null $start
     * @param \DateTime|null $end
     */
    public function setPeriod(\DateTime $start = null, \DateTime $end = null)
    {
        $this->setFilter('start', ($start !== null ? $start->format('c') : null));
        $this->setFilter('end', ($end !== null ? $end->format('c') : null));
    }

    /**
     * Restrict the query to a plan type, e.g. 'development', 'medium', etc.
     *
     * @param array|string|null $plan
     */
    public function setPlan($plan)
    {
        $this->setFilter('plan', $plan);
    }

}
