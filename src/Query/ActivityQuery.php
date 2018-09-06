<?php

namespace Platformsh\Client\Query;


use Platformsh\Client\Model\Activity;

class ActivityQuery extends Query
{

    /**
     * Restrict the activities to a type.
     */
    public function setType(string $type = null): self
    {
        $this->params['type'] = $type;

        return $this;
    }

    /**
     * Restrict the activities for the maximum created date. (huh?!)
     *
     * @param int|null $timestamp A UNIX timestamp
     */
    public function setStartsAt(int $timestamp = null): self
    {
        $this->params['starts_at'] = Activity::formatStartsAt($timestamp);

        return $this;
    }

}