<?php

namespace Platformsh\Client\DataStructure;

use Platformsh\Client\PlatformClient;
use Platformsh\Client\Query\QueryInterface;

/**
 * An iterable structure which lazy loads a new set of results
 * once it reaches the end of the locally stored values.
 */
class Collection implements \Iterator, \Countable
{

    // Current position in the collection of items.
    private $position = 0;
    // The collection of items.
    private $collection = [];


    // Page management
    private $currentPage = 1;
    private $hasNextPage = true;
    // Total number of results.
    private $countRemote = 0;


    // Resource object name.
    private $resourceObjectName;
    // Guzzle options.
    private $options = [];
    /** @var \Platformsh\Client\PlatformClient */
    private $client;


    public function __construct(string $resourceObjectName, PlatformClient $client, QueryInterface $query = null)
    {
        $this->client = $client;
        $this->resourceObjectName = $resourceObjectName;

        if ($query) {
            $this->options['query'] = $query->getParams();
        }

        $this->fetch(1);
    }

    protected function fetch($page = 1)
    {
        // Bail out early.
        if (!$this->hasNextPage) {
            return false;
        }

        $class = $this->resourceObjectName;
        $options = $this->options;
        $options['query']['page'] = $page;

        $uri = $this->client->getConnector()->getAccountsEndpoint().$class::COLLECTION_PATH;
        $data = $this->client->getConnector()->sendToUri($uri, 'get', $options);

        foreach ($data[$class::COLLECTION_NAME] as $resourceItem) {
            $this->collection[] = new $class($resourceItem, $uri, $this->client);
        }

        $this->countRemote = $data['count'];
        $this->hasNextPage = (isset($data['_links']['next']) ? true : false);
        $this->currentPage = $page;

        return true;
    }


    /**
     * {@inheritdoc}
     */
    public function count(): int
    {
        return $this->countRemote;
    }

    /**
     * Count locally available items.
     */
    public function countFetched(): int
    {
        return count($this->collection);
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
        // If no items remain in collectiom, fetch a new batch.
        if (!isset($this->collection[$this->position])) {
            $this->fetch(++$this->currentPage);
        }

        return isset($this->collection[$this->position]);
    }

}
