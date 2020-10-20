<?php

namespace Platformsh\Client\Tests\Model;


use Platformsh\Client\DataStructure\Collection;
use Platformsh\Client\Model\Trial;
use Platformsh\Client\Tests\PlatformshTestBase;

class TrialTest extends PlatformshTestBase
{

    /** @var Trial */
    private $trial;

    public function setUp()
    {
        parent::setUp();
        $this->trial = $this->client->getTrial(111);
    }

    public function testTrialGetters()
    {
        $noTrial = $this->client->getTrial(123);
        $this->assertNull($noTrial, 'Requesting a non-existing trial returns null');

        $this->assertInstanceOf(
            Trial::class,
            $this->trial,
            'Requesting an existing trial returns an instance of Trial class'
        );

        $trials = $this->client->getTrials();
        $this->assertInstanceOf(
            Collection::class,
            $trials,
            'Requesting multiple trials returns an instance of Collection class'
        );
        foreach ($trials as $trial) {
            $this->assertInstanceOf(Trial::class, $trial, 'The collection elements are instances of a Trial class');
        }
    }

    public function testPropertyGetters()
    {
        $data = $this->data['trials']['trials'][0];
        $this->assertEquals($data['id'], $this->trial->id, 'Trial ID getter works');
        $this->assertEquals($data['owner'], $this->trial->owner, 'Trial owner getter works');
        $this->assertEquals($data['spend']['formatted'], $this->trial->spend['formatted'], 'Trial spend getter works');

    }

    public function testOperationAvailable()
    {
        $this->assertFalse($this->trial->operationAvailable('do-something-ridiculous'), 'Operation unavailable');
    }

    public function testDeletion()
    {
        $this->expectException(\BadMethodCallException::class);
        $this->trial->delete();
    }

}
