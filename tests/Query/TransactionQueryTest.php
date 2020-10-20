<?php

namespace Platformsh\Client\Tests\Query;

use Platformsh\Client\Query\TransactionQuery;
use Platformsh\Client\Tests\PlatformshTestBase;

class TransactionQueryTest extends PlatformshTestBase
{
    /** @var UsageRecordQuery */
    private $query;

    public function setUp()
    {
        $this->query = new TransactionQuery();
    }

    public function testSetters()
    {
        $this->query
            ->updatedAfter(new \DateTime('1990-01-01T00:00:00+00:00'));

        $params = $this->query->getParams();

        $this->assertEquals('1990-01-01T00:00:00+00:00', $params['filter']['updated']['value'], 'The updated after filter value set.');
        $this->assertEquals('>=', $params['filter']['updated']['operator'], 'The updated after filter operator set.');

        $this->query->updatedAfter();

        $this->assertEmpty($this->query->getParams(), 'All filters unset.');

    }

}
