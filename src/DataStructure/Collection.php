<?php

namespace Platformsh\Client\DataStructure;

use Platformsh\Client\Fetcher\CollectionFetcher;

/**
 * An iterable structure which allows lazy loading of a new set of
 * results from a given fetcher class, once it reaches the end of
 * the stored values.
 */
class Collection implements \Iterator
{

    // Current position in the collection of items.
    private $position = 0;
    // The collection of items.
    private $collection = [];
    // The fetcher class handles API requests and returns prepared objects.
    private $fetcher;

    public function __construct(CollectionFetcher $fetcher)
    {
        $this->fetcher = $fetcher;
        $this->collection = $fetcher->fetch();
    }

    /**
     * {@inheritdoc}
     */
    public function rewind()
    {
        $this->position = 0;
    }

    /**
     * {@inheritdoc}
     */
    public function current()
    {
        return $this->collection[$this->position];
    }

    /**
     * {@inheritdoc}
     */
    public function key()
    {
        return $this->position;
    }

    /**
     * {@inheritdoc}
     */
    public function next()
    {
        ++$this->position;
    }

    /**
     * {@inheritdoc}
     */
    public function valid()
    {
        // If no items remain in collectiom, ask the fetcher for a new batch.
        if (!isset($this->collection[$this->position])) {
            if ($newCollection = $this->fetcher->fetch()) {
                $this->collection = array_merge($this->collection, $newCollection);
            }
        }

        return isset($this->collection[$this->position]);
    }

}
