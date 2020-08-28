<?php

namespace Platformsh\Client\Tests\Model;


use Platformsh\Client\Model\Account;
use Platformsh\Client\Model\Project;
use Platformsh\Client\Model\Result;
use Platformsh\Client\Tests\PlatformshTestBase;
use Platformsh\Client\Model\Subscription;

class SubscriptionTest extends PlatformshTestBase
{
    /** @var Subscription */
    protected $subscription;

    public function setUp()
    {
        parent::setUp();
        $this->subscription = $this->client->getSubscription(1234);
    }

    public function testGetSubscription()
    {
        $this->assertInstanceOf(
            Subscription::class,
            $this->subscription,
            'The subscription is an instance of Subscription class'
        );
        self::assertEquals(
            $this->subscriptionData['plan'],
            $this->subscription->plan,
            'Subscription plan loaded successfully'
        );
        $nonSunscription = $this->client->getSubscription(4321);
        $this->assertNull($nonSunscription, 'Trying to load non-existing subscription results in null.');
    }

    public function testGetSubscriptions()
    {
        $subscriptions = $this->client->getSubscriptions();
        $this->assertEquals(
            $this->subscriptionCollection['count'],
            $subscriptions->count(),
            'Count the total number of subscriptions.'
        );
        $this->assertEquals(
            count($this->subscriptionCollection['subscriptions']),
            $subscriptions->countFetched(),
            'Count the fetched subscriptions.'
        );
        foreach ($subscriptions as $subscription) {
            $this->assertInstanceOf(
                Subscription::class,
                $subscription,
                'The subscription is an instance of Subscription class'
            );
        }
    }

    public function testGetOwner()
    {
        $owner = $this->subscription->getOwner();
        $this->assertInstanceOf(Account::class, $owner, 'Subscription owner is an instance of Account class');
        $this->assertEquals($this->userData['display_name'], $owner->display_name, 'Owner info loaded successfully');

    }

    public function testGetProject()
    {
        $project = $this->subscription->getProject();
        $this->assertInstanceOf(Project::class, $project, 'Subscription project is an instance of Project class');
        $this->assertEquals($this->testProject['endpoint'], $project->endpoint, 'Project info loaded successfully');
    }

    public function testSubscriptionEstimate()
    {
        $estimate = $this->client->getSubscriptionEstimate('standard', 50, 3, 3);
        $this->assertEquals('114 €', $estimate['total']);
    }

    public function testSubscriptionStatuses()
    {
        $this->assertFalse($this->subscription->isPending(), 'The subscription is not pending');
        $this->assertFalse($this->subscription->isActive(), 'The subscription is not active');
        $this->assertEquals(
            Subscription::STATUS_DELETED,
            $this->subscription->getStatus(),
            'The subscription is deleted'
        );
    }

    public function testSubscriptionCreationAndDeletion()
    {
        $s = $this->client->createSubscription('us.platform.sh');
        $this->assertEquals(1234, $s->id, 'Subscription created.');

        // @todo: We really need proper feedback for entity deletion.
        $result = $s->delete();
        $this->assertInstanceOf(Result::class, $result, 'Deletion produces an instance of Result class');
        $this->assertEmpty($result->getData(), 'Result of deletion contains no data');
    }
}

