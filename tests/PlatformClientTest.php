<?php

namespace Platformsh\Client\Tests;

use \Platformsh\Client\PlatformClient;

class PlatformClientTest extends PlatformshTestBase
{

    /**
     * @covers \Platformsh\Client\PlatformClient::getProject()
     * @covers \Platformsh\Client\Model\Project::get()
     * @covers \Platformsh\Client\Model\Project::getDirect()
     */
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

    /**
     * @covers \Platformsh\Client\PlatformClient::getAccountInfo()
     */
    public function testGetAccountInfo() {
        $account = $this->client->getAccountInfo();
        $this->assertEquals('my_uuid', $account['id'], 'Account info successfully loaded');
    }

    /**
     * @covers \Platformsh\Client\PlatformClient::getSubscriptionEstimate()
     */
    public function testSubscriptionEstimate()
    {
        $estimate = $this->client->getSubscriptionEstimate('standard', 50, 3, 3);
        $this->assertEquals('114 €', $estimate['total']);
    }
}
