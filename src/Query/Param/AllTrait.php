<?php

namespace Platformsh\Client\Query\Param;

trait AllTrait
{
    /**
     * By default, API returns only resources owned by the authenticated client.
     * Use this method to include other resources available to the client.
     *
     * @param string|null $status
     */
    public function includeAll(): self
    {
        $this->params['all'] = true;

        return $this;
    }
}