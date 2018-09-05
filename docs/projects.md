# Projects API

A project is the actual Platform.sh product. Project API is a part of the Platform.sh regional API.

## Getting a project

In order to consume the Project API, the client must know the region of the project.

Optimally, if the region is known, the project can be loaded by:

```php
<?php
$projectId = 'myProjectId';
$projectRegion = 'eu-2.platform.sh';
$project = $platformshClient->getProject($projectId, $projectRegion);
```

If you don't know the region of the project, the API will send first request to the Project Locator API, which will return the region, then it will send a second request to the actual regional API.

```php
<?php
$projectId = 'myProjectId';
$project = $platformshClient->getProject($projectId);
```

Finally, if you were already working with Subscription objects, they have a handy `getProject()` method:

```php
<?php
$project = $subscription->getProject();
```

## Managing project users

To add a user with a known email address:
```php
<?php
$email = 'j.doe@example.com';
$role = 'admin';
$project->addUser($email, $role);
```

To add a user with a known UUID:
```php
<?php
$uuid = 'my-users-uuid';
$role = 'admin';
$project->addUser($uuid, $role, true);
```

There are two allowed roles: `admin` and `viewer`. Admins have full administrative privileges throughout the project. Privileges for viewers can be granularly set per each environment.

To get an array of access rules for the project use `getUsers()` method. Then on each returned instance of ProjectAccess, invoke `getAccount()` to get the actual User objects.

```php
<?php
$projectAccessRecords = $project->getUsers();
foreach ($projectAccessRecords as $projectAccessRecord) {
    $user = $projectAccessRecord->getAccount();
}
```

Invoke a `delete()` method on an instance of ProjectAccess to revoke the user access.

```php
<?php
$uuid = 'my-users-uuid';
$projectAccessRecord = $project->getUser($uuid);
$projectAccessRecord->delete();
```