<?php

namespace Platformsh\Client\Tests\Model;


use Platformsh\Client\Model\Activity;
use Platformsh\Client\Model\Certificate;
use Platformsh\Client\Model\ProjectAccess;
use Platformsh\Client\Model\Result;
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

    public function testGetUsers() {
        $projectAccess = $this->project->getUsers();
        $this->assertTrue(is_array($projectAccess), 'Project getUsers returns an array');
        $this->assertInstanceOf(ProjectAccess::class, $projectAccess[0], 'Project getUsers returns instances of ProjectAccess');
    }

    public function testGetUser() {
        $projectAccess = $this->project->getUser('my_uuid');
        $this->assertInstanceOf(ProjectAccess::class, $projectAccess, 'Project getUser returns instance of ProjectAccess');
    }

    public function testAddUser() {
        $result = $this->project->addUser('me@example.com', 'admin');
        $this->assertInstanceOf(Result::class, $result, 'Project::addUser() returns an instance of Result');
    }

//    public function testGetLink() {}
//    public function testOperationAvailable() {}
//    public function testGetEnvironments() {}
//    public function testGetEnvironment() {}
//    public function testGetDomains() {}
//    public function testGetDomain() {}
//    public function testAddDomain() {}
//    public function testGetIntegrations() {}
//    public function testGetIntegration() {}
//    public function testAddIntegration() {}
    public function testGetActivities()
    {
        $activities = $this->project->getActivities();
        foreach ($activities as $activity) {
            $this->assertInstanceOf(Activity::class, $activity, 'Project::getActivities returns an instance of Activity');
        }
    }

    public function testGetActivity()
    {
        $this->assertInstanceOf(Activity::class, $this->project->getActivity('my-activity'), 'Project::getActivity returns an instance of Activity');
        $this->assertNull($this->project->getActivity('no-activity'), 'Project::getActivity returns null if ID does not exist');
    }
//    public function testIsSuspended() {}
//    public function testGetVariables() {}
//    public function testGetVariable() {}
//    public function testSetVariable() {}
    public function testGetCertificates() {
        $certs = $this->project->getCertificates();
        foreach ($certs as $cert) {
            $this->assertInstanceOf(Certificate::class, $cert, 'Project::getCertificates returns an array of Certificates');
        }
    }
    public function testGetCertificate() {
        $this->assertInstanceOf(Certificate::class, $this->project->getCertificate('my-certificate'), 'Project::getCertificate returns an instance of Certificate');
        $this->assertNull($this->project->getCertificate('no-certificate'), 'Project::getCertificate returns null if ID does not exist');

    }
//    public function testAddCertificate() {}
//    public function testGetProjectBaseFromUrl() {}
//    public function testClearBuildCache() {}

}



