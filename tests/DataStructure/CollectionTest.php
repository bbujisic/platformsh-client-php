<?php

namespace Platformsh\Client\Tests\DataStructure;

use Platformsh\Client\Connection\ConnectorInterface;
use Platformsh\Client\DataStructure\Collection;
use Platformsh\Client\Model\Subscription;
use Platformsh\Client\PlatformClient;
use Platformsh\Client\Query\Query;

class CollectionTest extends \PHPUnit\Framework\TestCase
{
    /** @var Collection */
    private $collection;

    public function setUp()
    {
        $prophet = $this->prophesize(ConnectorInterface::class);

        $prophet->getAccountsEndpoint()->willReturn("https://accounts.example.com/api/");
        $prophet->sendToUri("https://accounts.example.com/api/subscriptions", "get", ["query" => ["page" => 1]])
            ->willReturn($this->dummyCollections(1));
        $prophet->sendToUri("https://accounts.example.com/api/subscriptions", "get", ["query" => ["page" => 2]])
            ->willReturn($this->dummyCollections(2));


        $this->client = new PlatformClient($prophet->reveal());

        $queryProphet = $this->prophesize(Query::class);
        $queryProphet->getParams()->willReturn([]);
        $this->collection = new Collection(Subscription::class, $this->client, $queryProphet->reveal());
    }

    public function testConstructor()
    {
        $this->assertInstanceOf(Collection::class, $this->collection);
        $this->assertCount(7, $this->collection);
        $this->assertEquals(5, $this->collection->countFetched());
    }
    
    public function testLoop() {
        foreach ($this->collection as $item) {
            $this->assertInstanceOf(Subscription::class, $item);
        }


        $this->assertEquals($this->collection->countFetched(), 7);
        $this->assertEquals($this->collection->key(), 7);
    }


    private function dummyCollections($page)
    {
        $maxItemsPerPage = 5;
        $totalItems = 7;

        $links = ['self' => 'https://accounts.example.com/api/subscriptions?page='.$page];
        if ($page !== 1) {
            $links['previous'] = 'https://accounts.example.com/api/subscriptions?page='.($page - 1);
        }
        if ($maxItemsPerPage * $page < $totalItems) {
            $itemsPerPage = $maxItemsPerPage;
            $links['next'] = 'https://accounts.example.com/api/subscriptions?page='.($page + 1);
        } else {
            $hasNextPage = false;
            $itemsPerPage = $totalItems % $maxItemsPerPage;
        }

        return [
            'count' => $totalItems,
            'subscriptions' => array_fill(0, $itemsPerPage, ['id' => 'my_subscription']),
            '_links' => $links,
        ];
    }

}