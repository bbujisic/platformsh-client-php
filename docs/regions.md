# Regions API

## Examples

```php
<?php

// Initialize the client.
$client = new Platformsh\Client\PlatformClient\PlatformClient();
// Authenticate.
$client->getConnector()->setApiToken($myToken, 'exchange');


// Get a single region.
$region = $client->getRegion("eu-2.platform.sh");


// Get all regions.
$regions = $client->getRegions();


// Get a set of regions based on filter criteria.
$query = (new \Platformsh\Client\Query\RegionQuery())
  ->setZone('north america') // e.g. "north america", "europe", "australia"
  ->setAvailable(true)
  ->setPrivate(false)
  ->setProvider('aws');      // e.g. "aws", "azure", "orange"
  
$regions = $client->getRegions($query);
```