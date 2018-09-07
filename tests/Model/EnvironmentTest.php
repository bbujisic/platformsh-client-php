<?php

namespace Platformsh\Client\Tests\Model;

use Platformsh\Client\Model\Activity;
use Platformsh\Client\Model\Deployment\EnvironmentDeployment;
use Platformsh\Client\Model\Git\Commit;
use Platformsh\Client\Tests\PlatformshTestBase;
use Platformsh\Client\Model\Environment;


class EnvironmentTest extends PlatformshTestBase
{

    /** @var Environment */
    private $environment;

    public function setUp()
    {
        parent::setUp(); // TODO: Change the autogenerated stub
        $this->environment = $this->client->getProject('test', 'example.com')->getEnvironment('development');
    }

    public function testGetCurrentDeploymentTest(){
        $this->assertInstanceOf(EnvironmentDeployment::class, $this->environment->getCurrentDeployment(), 'Get current deployment.');
    }
    public function testGetHeadCommit() {
        $this->assertInstanceOf(Commit::class, $this->environment->getHeadCommit(), 'Get head commit.');
    }

    public function testGetSshUrl() {
        $sshUrls = $this->environment->getSshUrls();
        $this->assertTrue(is_array($sshUrls), 'Environment::getSshUrls() returns an array.');

        // covers Environment::convertSsh(), too.
        $expectedUrl = str_replace('ssh://', '', $this->data['env_d']['_links']['pf:ssh:app']['href']);
        $sshUrl = $this->environment->getSshUrl('app');
        $this->assertEquals($expectedUrl, $sshUrl, 'Environment::getSshUrl() returns expected URL.');

        // covers Environment::constructLegacySshUrl()
        $legacySshUrl = $this->environment->getSshUrl();
        $this->assertEquals($expectedUrl, $legacySshUrl, 'Environment::constructLegacySshUrl() returns expected URL.');
    }
    public function testBranch() {
        $activity = $this->environment->branch('My New Environment');
        $this->assertInstanceOf(Activity::class, $activity, 'Environment::branch() returns an activity.');
    }
//    public function testSanitizeId() {}
//    public function testValidateId() {}
//    public function testDelete() {}
//    public function testIsActive() {}
//    public function testActivate() {}
//    public function testDeactivate() {}
//    public function testMerge() {}
//    public function testSynchronize() {}
//    public function testBackup() {}
//    public function testGetActivity() {}
//    public function testGetActivities() {}
//    public function testGetVariables() {}
//    public function testGetVariable() {}
//    public function testSetVariable() {}
//    public function testGetRoutes() {}
//    public function testGetRouteUrls() {}
//    public function testInitialize() {}
//    public function testGetUser() {}
//    public function testGetUsers() {}
//    public function testAddUser() {}
//    public function testRedeploy() {}

}