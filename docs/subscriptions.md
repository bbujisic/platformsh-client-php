# Subscriptions API

## Examples

```php
<?php

// Initialize the client.
$client = new Platformsh\Client\PlatformClient\PlatformClient();
// Authenticate.
$client->getConnector()->setApiToken($myToken, 'exchange');


// Get a single subscription.
$subscription = $client->getSubscription($subscriotionId);


// Get own subscriptions.
$subscriptions = $client->getSubscriptions();


// Get a set of subscriptions based on filter criteria.
$query = (new \Platformsh\Client\Query\SubscriptionQuery())
  ->setStatus($status)      // e.g. $status = 'active'
  ->includeAll();           // include other users subscriptions
  
$subscriptions = $client->getSubscriptions($query);


// Create a new subscription in platform.sh.
// NB: this operation will create a new project, together with appropriate charges.
$region = 'eu-2.platform.sh';
$plan   = 'development';

$subscription = $client->createSubscription($region, $plan);
```