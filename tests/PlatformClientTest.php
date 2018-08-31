<?php

namespace Platformsh\Client\Tests;

use \Platformsh\Client\PlatformClient;

class PlatformClientTest extends PlatformshTestBase
{

    /**
     * @covers PlatformClient::getProject
     * @covers \Platformsh\Client\Model\Project::get
     * @covers \Platformsh\Client\Model\Project::getDirect
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
}
