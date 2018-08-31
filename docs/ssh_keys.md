# SSH Key API

## Examples

```php
<?php

// Initialize the client.
$client = new Platformsh\Client\PlatformClient\PlatformClient();
// Authenticate.
$client->getConnector()->setApiToken($myToken, 'exchange');

// Get a list of your own ssh keys.
$sshKeys = $client->getSshKeys();
// Get a list of ssh keys owned by a certain user.
$sshKeys = $client->getSshKeys($userUUID);
// Get an individual ssh key by its ID
$sshKey = $client->getSshKey($keyId);

// Delete an ssh key
$sshKey->delete();

// Create an ssh key
$client->addSshKey($keyValue, $keyTitle);
```