<?php

namespace Platformsh\Client\Tests\Model;


use Platformsh\Client\DataStructure\Collection;
use Platformsh\Client\Tests\PlatformshTestBase;
use Platformsh\Client\Model\Region;

class RegionTest extends PlatformshTestBase
{

    /** @var Region */
    private $region;

    public function setUp()
    {
        parent::setUp();
        $this->region = $this->client->getRegion('region-1.example.com');
    }

    public function testRegionGetters()
    {
        $noRegion = $this->client->getRegion('no-region.example.com');
        $this->assertNull($noRegion, 'Requesting a non-existing region returns null');

        $this->assertInstanceOf(
            Region::class,
            $this->region,
            'Requesting an existing region returns an instance of Region class'
        );

        $regions = $this->client->getRegions();
        $this->assertInstanceOf(
            Collection::class,
            $regions,
            'Requesting multiple regions returns an instance of Collection class'
        );
        foreach ($regions as $region) {
            $this->assertInstanceOf(Region::class, $region, 'The collection elements are instances of a Region class');
        }
    }

    public function testPropertyGetters()
    {
        $regionData = $this->regionsCollection['regions'][0];
        $this->assertEquals($regionData['id'], $this->region->id, 'Region ID getter works');
        $this->assertEquals($regionData['label'], $this->region->label, 'Region label getter works');
        $this->assertTrue($this->region->available, 'Region available getter works');
        $this->assertFalse($this->region->private, 'Region private getter works');
    }

    public function testOperationAvailable()
    {
        $this->assertTrue($this->region->operationAvailable('edit'), 'Operation available');
        $this->assertFalse($this->region->operationAvailable('do-something-ridiculous'), 'Operation unavailable');
    }

    public function testDeletion()
    {
        $this->expectException(\BadMethodCallException::class);
        $this->region->delete();
    }

}
