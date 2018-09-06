# Environments API

## Examples

```php
<?php

// Initialize the client.
$client = new Platformsh\Client\PlatformClient\PlatformClient();
// Authenticate.
$client->getConnector()->setApiToken($myToken, 'exchange');

// Get a project in order to get an environment
$projectId = 'myProjectId';
$project = $platformshClient->getProject($projectId);

// Get an environment
$environmentName = 'development';
$environment = $project->getEnvironment($environmentName);

// Branch an environment
// The activity takes some time to finish.
$newEnvName = 'My Feature Branch';
$activity = $environment->branch($newEnvName); 

// Activate an environment
$activity = $environment->activate();

// Deactivate an environment
$activity = $environment->deactivate();

// Delete an environment
$result = $environment->delete();

// Get environment variables
$vars = $environment->getVariables();
$var = $environment-getVariable($variableName);

// Set variables
$environment->setVariable($variableName, $variableValue);

// Get routes in environment
$routes = $environment->getRoutes();

// Manage user access to an environment
$userAccessArray = $environment->getUsers();
$userAccess = $environment->getUser($userUuid);
$result = $environment->addUser($userUuid, $userRole);

// Trigger redeployment
$activity = $environment->redeploy();

// Get activities
$allActivities = $environment->getActivities();
// Filter activities by type and latest date of activity
$query = new \Platformsh\Client\Query\ActivityQuery();
$query
  ->setStartsAt($unixTimestamp)
  ->setType('environment.redeploy');
$filteredActivities = $environment->getActivities($query);

// Get a single activity with a known ID
$activity = $environment->getActivity($activityId);

```