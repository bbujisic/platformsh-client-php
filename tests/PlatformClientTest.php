<?php

namespace Platformsh\Client\Tests;

use Platformsh\Client\DataStructure\Collection;
use Platformsh\Client\Model\Billing\PlanRecord;
use Platformsh\Client\Model\Billing\UsageRecord;
use Platformsh\Client\Model\Project;
use \Platformsh\Client\PlatformClient;
use Platformsh\Client\Query\PlanRecordQuery;
use Platformsh\Client\Query\UsageRecordQuery;

class PlatformClientTest extends PlatformshTestBase
{

    public function testGetProject()
    {
        $this->assertNull(
            $this->client->getProject('no-project', 'example.com'),
            'Load a non-existing project with a known hostname.'
        );
        $this->assertNull(
            $this->client->getProject('no-project'),
            'Load a non-existing project without a known hostname.'
        );

        $project = $this->client->getProject('test', 'example.com');
        $this->assertEquals(
            $this->testProject['title'],
            $project->title,
            'Load a project with a known host name.'
        );

        $project = $this->client->getProject('test');
        $this->assertEquals(
            $this->testProject['title'],
            $project->title,
            'Load a project without a known host name.'
        );
    }

    public function testGetAccountInfo() {
        $account = $this->client->getAccountInfo();
        $this->assertEquals('my_uuid', $account['id'], 'Account info successfully loaded');
    }

    public function testGetProjects() {
        $projects = $this->client->getProjects();
        foreach ($projects as $project) {
            $this->assertInstanceOf(Project::class, $project, 'PlatformClient::getProjects loads an array of Projects.');

        }
    }

    public function testGetPlanRecords() {
        $prophet = $this->prophesize(PlanRecordQuery::class);
        $prophet->getParams()->willReturn([]);
        $collection = $this->client->getPlanRecords();

        $this->assertInstanceOf(Collection::class, $collection, 'PlatformClient::getPlanRecords returns Collection.');
        foreach ($collection as $item) {
            $this->assertInstanceOf(PlanRecord::class, $item, 'PlatformClient::getPlanRecords returns collection of PlanRecords.');
        }
    }

    public function testGetUsageRecords() {
        $prophet = $this->prophesize(UsageRecordQuery::class);
        $prophet->getParams()->willReturn([]);
        $collection = $this->client->getUsageRecords();

        $this->assertInstanceOf(Collection::class, $collection, 'PlatformClient::getUsageRecords returns Collection.');
        foreach ($collection as $item) {
            $this->assertInstanceOf(UsageRecord::class, $item, 'PlatformClient::getUsageRecords returns collection of UsageRecords.');
        }
    }

}
