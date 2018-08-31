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
}