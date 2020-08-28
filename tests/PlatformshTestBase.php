<?php

namespace Platformsh\Client\Tests;

use Platformsh\Client\Connection\ConnectorInterface;
use Platformsh\Client\PlatformClient;
use Prophecy\Argument;


abstract class PlatformshTestBase extends \PHPUnit\Framework\TestCase
{
    public $connectorProphet;

    /** @var PlatformClient */
    public $client;

    // All resources will go here as part of an effort to reduce the chaos in the class.
    public $data;

    public $testProject = [
        "id" => "test",
        'title' => 'Test project',
        'endpoint' => 'https://example.com/api/projects/test',
        'subscription' => [
            'license_uri' => 'https://accounts.example.com/api/licenses/1234'
        ]
    ];

    public $testProjectAccess = [
        'id' => 'my_uuid',
        'user' => 'my_uuid',
        'role' => 'admin'
    ];

    public function setUp() {
        $this->connectorProphet = $this->prophesize(ConnectorInterface::class);
        // Mock getAccountsEndpont
        $this->connectorProphet->getAccountsEndpoint()->willReturn('https://accounts.example.com/api/');

        $this->mockProjectAPIs();
        $this->mockUserAndSshAPI();
        $this->mockSubscriptionAPIs();
        $this->mockRegionAPI();
        $this->mockBillingAPI();
        $this->mockTrialsAPI();

        $this->client = new PlatformClient($this->connectorProphet->reveal());

    }

    private function mockProjectAPIs() {
        $c = $this->connectorProphet;

        $this->data['project_activities'] = [
            [
                'id' => 'my-activity',
                'type' => 'environment.push'
            ],
            [
                'id'=>'my-another-activity',
                'type'=>'project.domain.create'
            ]
        ];

        $this->data['project_certificates'] = [
            [
                'id' => 'my-certificate',
                'expires_at' => '2018-01-01T00:00:00+00:00',
                'domains' => [
                    'www.example.com',
                    'example.com',
                ],
            ],
            [
                'id' => 'my-other-certificate',
                'expires_at' => '2019-01-01T00:00:00+00:00',
                'domains' => [
                    'www.example.com',
                    'example.com',
                ],
            ],
        ];

        $this->data['env_d'] = [
            'id' => 'development',
            'name' =>'development',
            'parent'=>'master',
            'status'=>'active',
            'head_commit'=>'aaaabbbbcccc',
            '_links' => [
                'self' => ['href'=>'https://example.com/api/projects/test/environments/development'],
                'pf:ssh:app' => ['href'=> 'ssh://aaaabbbb-development-cccc--app@ssh.example.com'],
                'ssh' => ['href'=> 'ssh://aaaabbbb-development-cccc--app@ssh.example.com'],
                '#branch' => ['href'=> '/api/projects/test/environments/development/branch']
            ]
        ];
        $this->data['deployment_current'] = [
            'id' => 'current',
            'services'=> [],
            'routes' => [],
            'webapps' => [],
            'workers' => []
        ];
        $this->data['head_commit'] = [
            'id' => 'aaaabbbbcccc',
            'sha' => 'aaaabbbbcccc',
            'author'=> [],
            'committer' => [],
            'message' => 'Hello world.',
        ];

        $this->data['branch_response'] = [
            'status' => 'OK',
            'code' => 200,
            '_embedded' => [
                'activities' => [['id' => 'activity_id', 'type' => 'environment_branch']],
            ]
        ];

        // Mock existing and non-existing Region API project
        $c->sendToUri('https://example.com/api/projects/test')->willReturn($this->testProject);
        $c->sendToUri('https://example.com/api/projects/no-project')->willThrow(new \Exception('not found', 404));
        $c->sendToUri('https://example.com/api/projects/test/access', Argument::cetera())->willReturn([$this->testProjectAccess]);
        $c->sendToUri('https://example.com/api/projects/test/access/my_uuid')->willReturn($this->testProjectAccess);
        $c->sendToUri('https://example.com/api/projects/test/access', 'post', Argument::cetera())->willReturn(['status'=>'created', 'code'=>201]);

        $c->sendToUri('https://example.com/api/projects/test/activities', Argument::cetera())->willReturn($this->data['project_activities']);
        $c->sendToUri('https://example.com/api/projects/test/activities/my-activity', Argument::cetera())->willReturn($this->data['project_activities'][0]);
        $c->sendToUri('https://example.com/api/projects/test/activities/no-activity', Argument::cetera())->willThrow(new \Exception('not found', 404));

        $c->sendToUri('https://example.com/api/projects/test/certificates', Argument::cetera())->willReturn($this->data['project_certificates']);
        $c->sendToUri('https://example.com/api/projects/test/certificates/my-certificate', Argument::cetera())->willReturn($this->data['project_certificates'][0]);
        $c->sendToUri('https://example.com/api/projects/test/certificates/no-certificate', Argument::cetera())->willThrow(new \Exception('not found', 404));

        $c->sendToUri('https://example.com/api/projects/test/environments/development')->willReturn($this->data['env_d']);
        $c->sendToUri('https://example.com/api/projects/test/environments/development/deployments/current')->willReturn($this->data['deployment_current']);
        $c->sendToUri('https://example.com/api/projects/test/git/commits/aaaabbbbcccc')->willReturn($this->data['head_commit']);
        $c->sendToUri('https://example.com/api/projects/test/environments/development/branch', 'post', Argument::cetera())->willReturn($this->data['branch_response']);

        // Mock existing and non-existing project in Accounts Project Locator
        // @todo: change to /locator endpoints
        $this->connectorProphet->sendToAccounts('projects/test')->willReturn($this->testProject);
        $this->connectorProphet->sendToAccounts('projects/no-project')->willThrow(new \Exception('not found', 404));
    }

    protected $userData = [
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
                'value' => 'this_is_obviously_not_a_good_ssh_key',
            ],
            [
                'key_id' => 2,
                'fingerprint' => 'bbbccc',
                'value' => 'ssh-rsa AAAAB3NzaC1yc2EAAAADAQABAAABAQDHZ9RDuT6/e8/Mmj7ufDAp+elYYONRUhjIPn+zHlzuWeyolFFcbIUdMeT+t0+nK1AvZxK4EPQ+BNtcAv2vBg3HpKuaje7MLESA/6iPW8b6FPbn/fgwEXOQJmT9o/SJe6S5c/80pzeQpesWUJsb8Cdkj7edd41uEtk5SaR4cNwpslYLF8gymUOcSre4yxzROSIcAEEvyTOKf+uc3HFZRuprOZ1TxqjtamQPouBKe9p95zlgX4XycJ2avNqu2Q0zfZrONkCO+IjtPI1WwsSG7OyM6JY/ciAp1kRWs3/pOzXogftqtF6z/1/kwnZ+9TUN5MuNeTRtCYBROI/HFPMcvBDz',
            ],
        ],
        'projects' => [
            ['id'=>'my-first-project', 'endpoint' => 'https://example.com/api/projects/my-first-project'],
            ['id'=>'my-second-project', 'endpoint' => 'https://example.com/api/projects/my-second-project',],
        ]
    ];

    private function mockUserAndSshAPI()
    {
        $this->connectorProphet->sendToAccounts('me')->willReturn($this->userData);
        $this->connectorProphet->sendToUri('https://accounts.example.com/api/users/my_uuid')->willReturn($this->userData);
        $this->connectorProphet->sendToUri('https://accounts.example.com/api/users/my_uuid')->willReturn($this->userData);
        $this->connectorProphet->sendToAccounts('users/my_uuid/ssh_keys')->willReturn($this->userData['ssh_keys']);
        $this->connectorProphet->sendToUri('https://accounts.example.com/api/ssh_keys/1')->willReturn($this->userData['ssh_keys'][0]);

    }

    protected $subscriptionData = [
        'id' => 1234,
        'status' => 'deleted',
        'owner' => 'my_uuid',
        'vendor' => null,
        'plan' => 'development',
        'environments' => 3,
        'storage' => 5,
        'user_licenses' => 1,
        'project_id' => 'test',
        'project_title' => 'Test project',
        'project_region' => 'eu.platform.sh',
        'project_region_label' => 'Europe (west)',
        'project_ui' => 'https://example.com/#/projects/test',
        '_links' =>
            [
                'self' => ['href' => 'https://accounts.example.com/api/subscriptions/1234'],
                'project' => ['href' => 'https://example.com/api/projects/test'],
                'owner' => ['href' => 'https://accounts.example.com/api/users/my_uuid'],
            ],
    ];

    protected $subscriptionCollection = [
        'count' => 4,
        'subscriptions' => [
            ['id'=>1],
            ['id'=>2]
        ],
        '_links' => [
            'self' => [
                'title'=>'Self',
                'href'=>'https://accounts.example.com/api/subscriptions',
            ]
        ]
    ];

    private function mockSubscriptionAPIs()
    {
        $this->connectorProphet->sendToUri('https://accounts.example.com/api/subscriptions/1234')->willReturn($this->subscriptionData);
        $this->connectorProphet->sendToUri('https://accounts.example.com/api/subscriptions/4321')->willThrow(new \Exception('not found', 404));
        $this->connectorProphet->sendToUri('https://accounts.example.com/api/subscriptions', 'get', Argument::cetera())->willReturn($this->subscriptionCollection);
        $this->connectorProphet->sendToUri('https://accounts.example.com/api/subscriptions', 'post', Argument::cetera())->willReturn($this->subscriptionData);
        $this->connectorProphet->sendToUri('https://accounts.example.com/api/subscriptions/1234', 'delete')->willReturn([]);

        $this->connectorProphet->sendToAccounts(
            'estimate',
            'get',
            ['query' => ['plan' => 'standard', 'storage' => 50, 'environments' => 3, 'user_licenses' => 3]]
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


    protected $regionsCollection = [
        'count' => 4,
        'regions' => [
            [
                'id'=>'region-1.example.com',
                'label'=>'Region One',
                'zone' => 'Europe',
                'available'=> '1',
                'private'=> '0',
                '_links'=> [
                    'self' => [
                        'href' => 'https://accounts.example.com/api/regions/region-1.example.com'
                    ]
                ]
                ],
            ['id'=>'region-2.example.com', 'label'=>'Region Two']
        ],
        '_links' => [
            'self' => [
                'title'=>'Self',
                'href'=>'https://accounts.example.com/api/regions',
            ]
        ]
    ];

    private function mockRegionAPI()
    {
        $this->connectorProphet->sendToUri('https://accounts.example.com/api/regions/region-1.example.com')->willReturn($this->regionsCollection['regions'][0]);
        $this->connectorProphet->sendToUri('https://accounts.example.com/api/regions/no-region.example.com')->willThrow(new \Exception('Unprocessable Entity', 422));
        $this->connectorProphet->sendToUri('https://accounts.example.com/api/regions', 'get', Argument::cetera())->willReturn($this->regionsCollection);
    }

    private function mockBillingAPI() {
        $this->data['records_plan'] = [
            'count' => 4,
            'plan' => [
                ['id' => '111'],
                ['id' => '222'],
            ],
            '_links' => [
                'self' => [
                    'title' => 'Self',
                    'href' => 'https://accounts.example.com/api/records/plan',
                ],
            ],
        ];
        $this->connectorProphet->sendToUri("https://accounts.example.com/api/records/plan", 'get', Argument::cetera())->willReturn($this->data['records_plan']);

        $this->data['records_usage'] = [
            'count' => 2,
            'usage' => [
                ['id' => '111'],
                ['id' => '222'],
            ],
            '_links' => [
                'self' => [
                    'title' => 'Self',
                    'href' => 'https://accounts.example.com/api/records/usage',
                ],
            ],
        ];
        $this->connectorProphet->sendToUri("https://accounts.example.com/api/records/usage", 'get', Argument::cetera())->willReturn($this->data['records_usage']);
    }

    private function mockTrialsAPI() {
        $this->data['trials'] = [
            'count' => 1,
            'trials' => [
                [
                    'id' => '111',
                    'owner' => 'my_uuid',
                    'model' => 'general',
                    'status' => 'Created',
                    'spend' => [
                        'formatted' => '30 €',
                        'amount' => 30,
                        'currency_code' => 'EUR'
                    ]
                ],
            ],
            '_links' => [
                'self' => [
                    'title' => 'Self',
                    'href' => 'https://accounts.example.com/api/v1/trials',
                ],
            ],
        ];

        $this->connectorProphet->sendToUri("https://accounts.example.com/api/trials", 'get', Argument::cetera())->willReturn($this->data['trials']);
        $this->connectorProphet->sendToUri("https://accounts.example.com/api/trials/111")->willReturn($this->data['trials']['trials'][0]);
        $this->connectorProphet->sendToUri('https://accounts.example.com/api/trials/123')->willThrow(new \Exception('not found', 404));
    }
}
