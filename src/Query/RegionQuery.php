<?php

namespace Platformsh\Client\Query;


class RegionQuery extends Query
{
    /**
     * Restrict the query to a geographical zone of the region.
     *
     * @param string|null $zone
     */
    public function setZone(?string $zone): self
    {
        $this->setFilter('zone', $zone);

        return $this;
    }

    /**
     * Restrict the query to a cloud hosting provider.
     *
     * @param string|null $provider
     */
    public function setProvider(?string $provider): self
    {
        $this->setFilter('provider', $provider);

        return $this;
    }

    /**
     * Restrict the query to enabled regions only.
     *
     * @param bool $available
     */
    public function setAvailable(bool $available = true): self
    {

        $this->setFilter('available', ($available ? "1" : "0"));

        return $this;
    }

    /**
     * Restrict the query to private regions only.
     *
     * @param bool $private
     */
    public function setPrivate(bool $private = true): self
    {

        $this->setFilter('private', ($private ? "1" : "0"));

        return $this;
    }


}