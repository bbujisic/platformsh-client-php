<?php

namespace Platformsh\Client\Tests\Model;


use Platformsh\Client\Tests\PlatformshTestBase;
use Platformsh\Client\Model\Project;

class ProjectTest extends PlatformshTestBase
{
    /** @var Project */
    protected $project;

    public function setUp()
    {
        parent::setUp();
        $this->project = $this->client->getProject('test', 'example.com');
    }


    public function testProjectDeletion()
    {
        $this->expectException(\BadMethodCallException::class);
        $this->project->delete();
    }

    public function testPropertyGetters()
    {
        $this->assertEquals(1234, $this->project->getSubscriptionId(), 'Get subscription ID');
        $this->assertEquals('test@git.example.com:test.git', $this->project->getGitUrl(), 'Get Git URL');
    }


}



