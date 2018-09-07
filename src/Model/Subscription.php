<?php

namespace Platformsh\Client\Model;

use GuzzleHttp\ClientInterface;
use Platformsh\Client\Model\Accounts\AccountsApiResourceBase;
use Platformsh\Client\PlatformClient;

/**
 * Represents a Platform.sh subscription.
 *
 * @property-read int    $id
 * @property-read string $status
 * @property-read string $owner
 * @property-read array  $owner_info
 * @property-read string $vendor
 * @property-read string $plan
 * @property-read int    $environments  Available environments.
 * @property-read int    $storage       Available storage (in MiB).
 * @property-read int    $user_licenses Number of users.
 * @property-read string $project_id
 * @property-read string $project_title
 * @property-read string $project_region
 * @property-read string $project_region_label
 * @property-read string $project_ui
 * @property-read array  $project_options
 * @property-read string $enterprise_tag
 * @property-read array  $services
 * @property-read string $support_tier
 */
class Subscription extends AccountsApiResourceBase
{

    public static $availablePlans = ['development', 'standard', 'medium', 'large'];
    public static $availableRegions = ['eu.platform.sh', 'us.platform.sh'];
    protected static $required = ['project_region'];

    const STATUS_ACTIVE = 'active';
    const STATUS_REQUESTED = 'requested';
    const STATUS_PROVISIONING = 'provisioning';
    const STATUS_FAILED = 'provisioning Failure';
    const STATUS_SUSPENDED = 'suspended';
    const STATUS_DELETED = 'deleted';

    // @todo: Move these constants to methods, so that they can be documented in an appropriate interface.
    const COLLECTION_NAME = 'subscriptions';
    const COLLECTION_PATH = 'subscriptions';

    /**
     * Wait for the subscription's project to be provisioned.
     *
     * @param callable  $onPoll   A function that will be called every time the
     *                            subscription is refreshed. It will be passed
     *                            one argument: the Subscription object.
     * @param int       $interval The polling interval, in seconds.
     */
    public function wait(callable $onPoll = null, $interval = 2)
    {
        while ($this->isPending()) {
            sleep($interval > 1 ? $interval : 1);
            $this->refresh();
            if ($onPoll !== null) {
                $onPoll($this);
            }
        }
    }

    /**
     * @inheritdoc
     */
    protected static function checkProperty($property, $value)
    {
        $errors = [];
        if ($property === 'storage' && $value < 1024) {
            $errors[] = "Storage must be at least 1024 MiB";
        }
        elseif ($property === 'activation_callback') {
            if (!isset($value['uri'])) {
                $errors[] = "A 'uri' key is required in the activation callback";
            }
            elseif (!filter_var($value['uri'], FILTER_VALIDATE_URL)) {
                $errors[] = 'Invalid URI in activation callback';
            }
        }
        return $errors;
    }

    /**
     * Check whether the subscription is pending (requested or provisioning).
     *
     * @return bool
     */
    public function isPending()
    {
        $status = $this->getStatus();
        return $status === self::STATUS_PROVISIONING || $status === self::STATUS_REQUESTED;
    }

    /**
     * Find whether the subscription is active.
     *
     * @return bool
     */
    public function isActive()
    {
        return $this->getStatus() === self::STATUS_ACTIVE;
    }

    /**
     * Get the subscription status.
     *
     * This could be one of Subscription::STATUS_ACTIVE,
     * Subscription::STATUS_REQUESTED, Subscription::STATUS_PROVISIONING,
     * Subscription::STATUS_SUSPENDED, or Subscription::STATUS_DELETED.
     *
     * @return string
     */
    public function getStatus()
    {
        return $this->getProperty('status');
    }

    /**
     * Get the account for the project's owner.
     */
    public function getOwner(): ?Account
    {
        if (!$this->hasLink('owner')) {
            throw new \BadMethodCallException('Access denied to the subscription owner resource.');
        }

        return $this->getLinkedResource('owner', Account::class);
    }

    /**
     * Get the project associated with this subscription.
     *
     * @return Project|false
     */
    public function getProject()
    {
        if (!$this->hasLink('project') && !$this->isActive()) {
            throw new \BadMethodCallException('Inactive subscriptions do not have projects.');
        }

        return $this->getLinkedResource('project', Project::class);
    }

    /**
     * @inheritdoc
     */
    protected function setData(array $data)
    {
        $data = isset($data['subscriptions'][0]) ? $data['subscriptions'][0] : $data;
        $this->data = $data;
    }

    /**
     * @inheritdoc
     */
    public function operationAvailable($op)
    {
        if ($op === 'edit') {
            return true;
        }
        return parent::operationAvailable($op);
    }

    /**
     * @inheritdoc
     */
    public function getLink($rel, $absolute = false)
    {
        if ($rel === '#edit') {
            return $this->getUri($absolute);
        }
        return parent::getLink($rel, $absolute);
    }
}
