<?php

namespace Platformsh\Client\Query;

interface QueryInterface
{
    /**
     * Get the URL query parameters.
     */
    public function getParams(): array;

}
