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
        $this->mockUserAPI();
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
    ];

    private function mockUserAPI()
    {
        $this->connectorProphet->sendToAccounts('me')->willReturn($this->userData);
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