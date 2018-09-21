<?php

namespace Platformsh\Client\Tests\Query;

use Platformsh\Client\Query\UsageRecordQuery;
use Platformsh\Client\Tests\PlatformshTestBase;

class UsageRecordQueryTest extends PlatformshTestBase
{
    /** @var UsageRecordQuery */
    private $query;

    public function setUp()
    {
        $this->query = new UsageRecordQuery();
    }

    public function testSetters()
    {


        $owner = 'tester-testowsky';
        $vendor = 'test';
        $subscriptionId = 1234;
        $this->query->setPeriod(new \DateTime('1990-01-01T00:00:00+00:00'), new \DateTime('1990-01-02T00:00:00+00:00'));
        $this->query->setOwner($owner);
        $this->query->setVendor($vendor);
        $this->query->setSubscriptionId($subscriptionId);

        $this->query->setUsageGroup('storage');
        $this->query->setUsageGroup('environments');
        $this->query->setUsageGroup('user_licenses');

        $this->expectException('Exception');
        $this->query->setUsageGroup('forbidden-usage-group');

        $params = $this->query->getParams();

        $this->assertEquals('1990-01-01T00:00:00+00:00', $params['filter']['start'], 'Start filter set.');
        $this->assertEquals('1990-01-02T00:00:00+00:00', $params['filter']['end'], 'End filter set.');
        $this->assertEquals($owner, $params['filter']['owner'], 'Owner filter set.');
        $this->assertEquals($vendor, $params['filter']['vendor'], 'Vendor filter set.');
        $this->assertEquals($subscriptionId, $params['filter']['subscription_id'], 'Subscription ID filter set.');
        $this->assertEquals('user_licenses', $params['filter']['usage_group'], 'Usage group filter set.');

        $this->query->setPeriod();
        $this->query->setOwner();
        $this->query->setVendor();
        $this->query->setSubscriptionId();
        $this->query->setUsageGroup();

        $this->assertEmpty($this->query->getParams(), 'All filters unset.');

    }

}