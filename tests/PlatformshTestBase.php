<?php

namespace Platformsh\Client\Tests;

use Platformsh\Client\Connection\ConnectorInterface;
use Platformsh\Client\PlatformClient;


abstract class PlatformshTestBase extends \PHPUnit\Framework\TestCase
{
    public $connectorProphet;

    /** @var PlatformClient */
    public $client;

    public $testProject = [
        "id" => "test",
        'title' => 'Test project',
        'endpoint' => 'https://example.com/api/projects/test',
        'subscription' => [
            'license_uri' => 'https://accounts.example.com/api/platform/licenses/1234'
        ]
    ];

    public function setUp() {
        $this->connectorProphet = $this->prophesize(ConnectorInterface::class);
        // Mock getAccountsEndpont
        $this->connectorProphet->getAccountsEndpoint()->willReturn('https://accounts.example.com/api');

        $this->mockProjectAPIs();
        $this->mockUserAndSshAPI();
        $this->mockSubscriptionAPIs();

        $this->client = new PlatformClient($this->connectorProphet->reveal());

    }

    private function mockProjectAPIs() {
        // Mock existing and non-existing Region API project
        $this->connectorProphet->sendToUri('https://example.com/api/projects/test')->willReturn($this->testProject);
        $this->connectorProphet->sendToUri('https://example.com/api/projects/no-project')->willThrow(new \Exception('not found', 404));

        // Mock existing and non-existing project in Accounts Project Locator
        // @todo: change to /locator endpoints
        $this->connectorProphet->sendToAccounts('projects/test')->willReturn($this->testProject);
        $this->connectorProphet->sendToAccounts('projects/no-project')->willThrow(new \Exception('not found', 404));
    }

    private $userData = [
        'id' => 'my_uuid',
        'uuid' => 'my_uuid',
        'username' => 'tester',
        'display_name' => 'Tester Testowsky',
        'status' => '1',
        'mail' => 'test@example.com',
        'ssh_keys' => [
            [
                'key_id' => 1,
                'fingerprint' => 'aaabbb',
                'value' => 'this_is_obviously_not_a_good_ssh_key'
            ],
            [
                'key_id' => 2,
                'fingerprint' => 'bbbccc',
                'value' => 'ssh-rsa AAAAB3NzaC1yc2EAAAADAQABAAABAQDHZ9RDuT6/e8/Mmj7ufDAp+elYYONRUhjIPn+zHlzuWeyolFFcbIUdMeT+t0+nK1AvZxK4EPQ+BNtcAv2vBg3HpKuaje7MLESA/6iPW8b6FPbn/fgwEXOQJmT9o/SJe6S5c/80pzeQpesWUJsb8Cdkj7edd41uEtk5SaR4cNwpslYLF8gymUOcSre4yxzROSIcAEEvyTOKf+uc3HFZRuprOZ1TxqjtamQPouBKe9p95zlgX4XycJ2avNqu2Q0zfZrONkCO+IjtPI1WwsSG7OyM6JY/ciAp1kRWs3/pOzXogftqtF6z/1/kwnZ+9TUN5MuNeTRtCYBROI/HFPMcvBDz'
            ]
        ]
    ];

    private function mockUserAndSshAPI()
    {
        $this->connectorProphet->sendToAccounts('me')->willReturn($this->userData);
        $this->connectorProphet->sendToAccounts("ssh_keys/1")->willReturn($this->userData['ssh_keys'][0]);

    }

    private function mockSubscriptionAPIs()
    {
        $this->connectorProphet->sendToAccounts(
            "estimate",
            "get",
            ["query" => ["plan" => "standard", "storage" => 50, "environments" => 3, "user_licenses" => 3]]
        )->willReturn(
            [
                'plan' => '40 €',
                'total' => '114 €',
                'user_licenses' => '20 €',
                'environments' => '0 €',
                'storage' => '54 €',
            ]
        );
    }

}