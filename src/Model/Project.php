<?php

namespace Platformsh\Client\Model;

use GuzzleHttp\Exception\ClientException;
use function GuzzleHttp\Psr7\uri_for;
use Platformsh\Client\PlatformClient;
use Platformsh\Client\Query\ActivityQuery;

/**
 * A Platform.sh project.
 *
 * @property-read string $id
 * @property-read string $title
 * @property-read string $created_at
 * @property-read string $updated_at
 * @property-read string $owner
 * @property-read string $desription
 * @property-read string $status
 * @property-read string $default_domain
 *
 */
class Project extends ApiResourceBase
{
    /**
     * {@inheritdoc}
     */
    public static function get(PlatformClient $client, $id): ?self
    {
        // Use the project locator to find the project.
        if ($location = self::locate($client, $id)) {
            // Request a project resource from the regional API.
            return self::getDirect($client, $location);
        }

        return null;
    }

    /**
     * Locate a project by ID.
     */
    public static function locate(PlatformClient $client, $id): ?string
    {
        try {
            $result = $client->getConnector()->sendToAccounts('projects/'.rawurlencode($id));
        } catch (\Exception $e) {
            // The API may throw 400 (bad request), 403 (forbidden) or 404 (not found).
            if (!in_array($e->getCode(), [400, 403, 404])) {
                throw $e;
            }
        }

        return isset($result['endpoint']) ? $result['endpoint'] : null;
    }

    /**
     * Prevent deletion.
     *
     * @internal
     */
    public function delete()
    {
        throw new \BadMethodCallException("Projects should not be deleted directly. Delete the subscription instead.");
    }

    /**
     * Get the subscription ID for the project.
     *
     * @todo when APIs are unified, this can be a property
     *
     * @return int
     */
    public function getSubscriptionId()
    {
        if ($this->hasProperty('subscription_id', false)) {
            return $this->getProperty('subscription_id');
        }

        if (isset($this->data['subscription']['license_uri'])) {
            return basename($this->data['subscription']['license_uri']);
        }

        throw new \RuntimeException('Subscription ID not found');
    }

    /**
     * Get the Git URL for the project.
     *
     * @return string
     */
    public function getGitUrl()
    {
        // The collection doesn't provide a Git URL, but it does provide the
        // right host, so the URL can be calculated.
        if (!$this->hasProperty('repository', false)) {
            $host = parse_url($this->getUri(), PHP_URL_HOST);

            return "{$this->id}@git.{$host}:{$this->id}.git";
        }
        $repository = $this->getProperty('repository');

        return $repository['url'];
    }

    /**
     * Get the users associated with a project.
     *
     * @return ProjectAccess[]
     */
    public function getUsers(): array
    {
        return $this->getLinkedResources('access', ProjectAccess::class);
    }

    /**
     * Get a user associated with a project.
     *
     * @return ProjectAccess|null
     */
    public function getUser(string $id): ?ProjectAccess
    {
        return $this->getLinkedResource('access', ProjectAccess::class, $id);
    }

    /**
     * Add a new user to a project.
     *
     * @param string $user   The user's UUID or email address (see $byUuid).
     * @param string $role   One of ProjectAccess::$roles.
     * @param bool   $byUuid Set true if $user is a UUID, or false (default) if
     *                       $user is an email address.
     *
     * Note that for legacy reasons, the default for $byUuid is false for
     * Project::addUser(), but true for Environment::addUser().
     *
     * @return Result
     */
    public function addUser($user, $role, $byUuid = false): Result
    {
        $property = $byUuid ? 'user' : 'email';
        $body = [$property => $user, 'role' => $role];

        return ProjectAccess::create($this->client, $body, $this->getLink('access'));
    }

    /**
     * @inheritdoc
     *
     * The accounts API does not (yet) return HAL links. This is a collection
     * of workarounds for that issue.
     */
    public function getLink($rel, $absolute = true)
    {
        if ($this->hasLink($rel)) {
            return parent::getLink($rel, $absolute);
        }

        if ($rel === 'self') {
            return $this->getProperty('endpoint');
        }

        if ($rel === '#ui') {
            return $this->getProperty('uri');
        }

        if ($rel === '#manage-variables') {
            return $this->getUri() . '/variables';
        }

        return $this->getUri() . '/' . ltrim($rel, '#');
    }

    /**
     * @inheritdoc
     */
    public function operationAvailable($op)
    {
        if (!parent::operationAvailable($op)) {
            $this->ensureFull();
        }

        return parent::operationAvailable($op);
    }

    /**
     * Get a list of environments for the project.
     *
     * @return Environment[]
     */
    public function getEnvironments(): array
    {
        return $this->getLinkedResources('environments', Environment::class);
    }

    /**
     * Get a single environment of the project.
     */
    public function getEnvironment(string $id): ?Environment
    {
        return $this->getLinkedResource('environments', Environment::class, $id);
    }

    /**
     * Get a list of domains for the project.
     *
     * @return Domain[]
     */
    public function getDomains(): ?array
    {
        return $this->getLinkedResources('domains', Domain::class);
    }

    /**
     * Get a single domain of the project.
     *
     * @param string $name
     */
    public function getDomain($name): ?Domain
    {
        return $this->getLinkedResource('domains', Domain::class, $name);
    }

    /**
     * Add a domain to the project.
     *
     * @param string $name
     * @param array  $ssl
     *
     * @return Result
     */
    // @todo: document better: what is ssl array and how to use it?
    public function addDomain($name, array $ssl = []): Result
    {
        $body = ['name' => $name];
        if (!empty($ssl)) {
            $body['ssl'] = $ssl;
        }

        return Domain::create($this->client, $body, $this->getLink('domains'));
    }

    /**
     * Get a list of integrations for the project.
     *
     * @return Integration[]
     */
    public function getIntegrations(): ?array
    {
        return $this->getLinkedResources('integrations', Integration::class);
    }

    /**
     * Get a single integration of the project.
     *
     * @param string $id
     */
    public function getIntegration(string $id): ?Integration
    {
        return $this->getLinkedResource('integrations', Integration::class, $id);
    }

    /**
     * Add an integration to the project.
     *
     * @param string $type
     * @param array $data
     */
    // @todo: document it, maybe refactor it for easier consumption and validation.
    public function addIntegration(string $type, array $data = []): Result
    {
        $body = ['type' => $type] + $data;

        return Integration::create($this->client, $body, $this->getLink('integrations'));
    }

    /**
     * Get a single project activity.
     *
     * @param string $id Activity id
     */
    public function getActivity(string $id): ?Activity
    {
        $uri = $this->getUri().'/activities/'.urlencode($id);

        return Activity::getDirect($this->client, $uri);

    }

    /**
     * Get a list of project activities.
     *
     * @return Activity[]
     */
    public function getActivities(ActivityQuery $query = null): array
    {
        return $this->getLinkedResources('activities', Activity::class, $query);
    }

    /**
     * Returns whether the project is suspended.
     *
     * @return bool
     */
    public function isSuspended()
    {
        return isset($this->data['status'])
          ? $this->data['status'] === 'suspended'
          : (bool) $this->getProperty('subscription')['suspended'];
    }


    /**
     * Get a list of variables.
     *
     * @return ProjectLevelVariable[]
     */
    public function getVariables(): array
    {
        return $this->getLinkedResources('#manage-variables', ProjectLevelVariable::class);
    }

    /**
     * Set a variable.
     *
     * @param string $name
     *   The name of the variable to set.
     * @param mixed  $value
     *   The value of the variable to set.  If non-scalar it will be JSON-encoded automatically.
     * @param bool $json
     *   Whether this variable's value is JSON-encoded.
     * @param bool $visibleBuild
     *   Whether this this variable should be exposed during the build phase.
     * @param bool $visibleRuntime
     *   Whether this variable should be exposed during deploy and runtime.
     * @param bool $sensitive
     *   Whether this variable's value should be readable via the API.
     *
     * @return Result
     */
    public function setVariable(
        $name,
        $value,
        $json = false,
        $visibleBuild = true,
        $visibleRuntime = true,
        $sensitive = false
    ) {
        // If $value isn't a scalar, assume it's supposed to be JSON.
        if (!is_scalar($value)) {
            $value = json_encode($value);
            $json = true;
        }
        $values = [
            'value' => (string)$value,
            'is_json' => $json,
            'visible_build' => $visibleBuild,
            'visible_runtime' => $visibleRuntime];
        if ($sensitive) {
            $values['is_sensitive'] = $sensitive;
        }

        $existing = $this->getVariable($name);
        if ($existing) {
            return $existing->update($values);
        }

        $values['name'] = $name;

        return ProjectLevelVariable::create($this->client, $values, $this->getLink('#manage-variables'));
    }

    /**
     * Get a single variable.
     *
     * @param string $id
     *   The name of the variable to retrieve.
     * @return ProjectLevelVariable|null
     *   The variable requested, or null if it is not defined.
     */
    public function getVariable(string $id): ?ProjectLevelVariable
    {
        return $this->getLinkedResource('#manage-variables', ProjectLevelVariable::class, $id);
    }

    /**
     * Get a list of certificates associated with this project.
     *
     * @return Certificate[]
     */
    public function getCertificates()
    {
        return $this->getLinkedResources('certificates', Certificate::class);
    }

    /**
     * Get a single certificate.
     */
    public function getCertificate(string $id): ?Certificate
    {
        return $this->getLinkedResource('certificates', Certificate::class, $id);
    }

    /**
     * Add a certificate to the project.
     *
     * @param string $certificate
     * @param string $key
     * @param array  $chain
     *
     * @return Result
     */
    public function addCertificate($certificate, $key, array $chain = []): Result
    {
        $options = ['key' => $key, 'certificate' => $certificate, 'chain' => $chain];

        return Certificate::create($this->client, $options, $this->getUri() . '/certificates');
    }

    /**
     * Find the project base URL from another project resource's URL.
     *
     * @param string $url
     *
     * @return string
     */
    public static function getProjectBaseFromUrl($url)
    {
        if (preg_match('#/api/projects/([^/]+)#', $url, $matches)) {
            return uri_for($url)->withPath('/api/projects/' . $matches[1])->__toString();
        }

        throw new \RuntimeException('Failed to find project ID from URL: ' . $url);
    }

    /**
     * Clear the project's build cache.
     *
     * @return Result
     */
    public function clearBuildCache()
    {
        return $this->runOperation('clear-build-cache');
    }
}
