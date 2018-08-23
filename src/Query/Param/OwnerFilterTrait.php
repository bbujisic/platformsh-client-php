<?php

namespace Platformsh\Client\Query\Param;

trait OwnerFilterTrait
{
    /**
     * Restrict the query to an owner's ID.
     *
     * @param array|string|null $owner
     */
    public function setOwner($owner): self
    {
        $this->setFilter('owner', $owner);

        return $this;
    }
}