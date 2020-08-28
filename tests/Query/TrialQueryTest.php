<?php

namespace Platformsh\Client\Tests\Query;

use Platformsh\Client\Query\TrialQuery;
use Platformsh\Client\Tests\PlatformshTestBase;

class TrialQueryTest extends PlatformshTestBase
{
    /** @var UsageRecordQuery */
    private $query;

    public function setUp()
    {
        $this->query = new TrialQuery();
    }

    public function testSetters()
    {
        $owner = 'tester-testowsky';
        $vendor = 'test';
        $subscriptionId = 1234;
        $planRecord = 234;
        $this->query
            ->setOwner($owner)
            ->updatedAfter(new \DateTime('1990-01-01T00:00:00+00:00'));

        $params = $this->query->getParams();

        $this->assertEquals('1990-01-01T00:00:00+00:00', $params['filter']['updated']['value'], 'The updated after filter value set.');
        $this->assertEquals('>=', $params['filter']['updated']['operator'], 'The updated after filter operator set.');
        $this->assertEquals($owner, $params['filter']['owner'], 'Owner filter set.');

        $this->query->setOwner();
        $this->query->updatedAfter();

        $this->assertEmpty($this->query->getParams(), 'All filters unset.');

    }

}
