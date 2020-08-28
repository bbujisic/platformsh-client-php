<?php

namespace Platformsh\Client\Query;

use Platformsh\Client\Query\Param\OwnerFilterTrait;

class TrialQuery extends Query
{

    use OwnerFilterTrait;

    /**
     * Restrict the query to a date/time period.
     *
     * @param \DateTime|null $time Updated time.
     */
    public function updatedAfter(\DateTime $time = null): self
    {
        if (!$time) {
            $filter = null;
        }
        else {
            $filter = [
                'value' => $time->format('c'),
                'operator' => '>=',
            ];
        }

        $this->setFilter('updated', $filter);

        return $this;
    }
}
