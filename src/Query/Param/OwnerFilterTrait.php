<?php

namespace Platformsh\Client\Query\Param;

trait OwnerFilterTrait
{
    /**
     * Restrict the query to an owner's ID.
     *
     * @param string|null $owner
     */
    public function setOwner($owner = null): self
    {
        $this->setFilter('owner', $owner);

        return $this;
    }
}