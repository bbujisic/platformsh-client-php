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
```