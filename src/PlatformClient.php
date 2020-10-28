<?php

namespace Platformsh\Client;

use GuzzleHttp\Exception\BadResponseException;
use Platformsh\Client\Connection\Connector;
use Platformsh\Client\Connection\ConnectorInterface;
use Platformsh\Client\DataStructure\Collection;
use Platformsh\Client\Exception\ApiResponseException;
use Platformsh\Client\Model\Account;
use Platformsh\Client\Model\Billing\PlanRecord;
use Platformsh\Client\Model\Billing\UsageRecord;
use Platformsh\Client\Model\Project;
use Platformsh\Client\Model\Region;
use Platformsh\Client\Model\Result;
use Platformsh\Client\Model\SshKey;
use Platformsh\Client\Model\Subscription;
use Platformsh\Client\Query\PlanRecordQuery;
use Platformsh\Client\Query\RegionQuery;
use Platformsh\Client\Query\SubscriptionQuery;
use Platformsh\Client\Query\TrialQuery;
use Platformsh\Client\Query\TransactionQuery;
use Platformsh\Client\Query\UsageRecordQuery;
use Platformsh\Client\Model\Trial;
use Platformsh\Client\Model\Transaction;

class PlatformClient
{

    /** @var ConnectorInterface */
    protected $connector;

    /** @var string */
    protected $accountsEndpoint;

    /** @var array */
    protected $accountInfo;

    /**
     * @param ConnectorInterface $connector
     */
    public function __construct(ConnectorInterface $connector = null)
    {
        $this->connector = $connector ?: new Connector();
        $this->accountsEndpoint = $this->connector->getAccountsEndpoint();
    }

    /**
     * @return ConnectorInterface
     */
    public function getConnector()
    {
        return $this->connector;
    }

    /**
     * Get a single project by its ID.
     */
    public function getProject(string $id, string $hostname = null, bool $https = true): ?Project
    {
        // Look for a project directly if the hostname is known.
        if ($hostname !== null) {
            $scheme = $https ? 'https' : 'http';
            $id = urlencode($id);

            return Project::getDirect($this, "$scheme://$hostname/api/projects/$id");
        }

        // Otherwise, use the project locator.
        return Project::get($this, $id);
    }

    /**
     * Get the logged-in user's projects.
     *
     * @param bool $reset
     *
     * @return Project[]
     *
     * @deprecated
     *   Use getSubscriptions instead.
     */
    public function getProjects($reset = false)
    {
        $data = $this->getAccountInfo($reset);
        $projects = [];
        foreach ($data['projects'] as $project) {
            // Each project has its own endpoint on a Platform.sh region.
            $projects[] = new Project($project, $project['endpoint'], $this);
        }

        return $projects;
    }

    /**
     * Get account information for the logged-in user.
     */
    public function getAccountInfo($reset = false): array
    {
        if (!isset($this->accountInfo) || $reset) {
            $this->accountInfo = $this->getConnector()->sendToAccounts('me');
        }

        return $this->accountInfo;
    }

  /**
   * Get an account by its UUID.
   *
   * @param string $uuid
   *
   * @return Account|false
   */
    public function getAccount($uuid) {
      return Account::get($this, $uuid);
    }


    /**
     * Get the SSH keys for a given UUID. Defaults to logged in account.
     *
     * @param bool $reset
     *
     * @return SshKey[]
     */
    public function getSshKeys(string $uuid = null, bool $reset = false): array
    {
        if (!$uuid) {
            $uuid = $this->getAccountInfo($reset)['id'];
            $data = $this->getAccountInfo($reset)['ssh_keys'];
        } else {
            $data = $this->connector->sendToAccounts('users/'.urlencode($uuid).'/ssh_keys');
        }

        $keys = [];
        foreach ($data as $datum) {
            $keys[] = new SshKey($datum, 'users/'.urlencode($uuid).'/ssh_keys', $this);
        }

        return $keys;
    }

    /**
     * Get a single SSH key by its ID.
     *
     * @param int $id
     */
    public function getSshKey(int $id): ?SshKey
    {
        return SshKey::get($this, $id);
    }

    /**
     * Add an SSH public key to the logged-in user's account.
     *
     * @param string $value The SSH key value.
     * @param string $title A title for the key (optional).
     *
     */
    // @todo: Fix the API to allow uuid's, then add an extra parameter here.
    public function addSshKey(string $value, string $title = null): Result
    {
        $values = $this->cleanRequest(['value' => $value, 'title' => $title]);

        return SshKey::create($this, $values);
    }

    /**
     * Filter a request array to remove null values.
     *
     * @param array $request
     *
     * @return array
     */
    protected function cleanRequest(array $request)
    {
        return array_filter($request, function ($element) {
            return $element !== null;
        });
    }

    /**
     * Create a new Platform.sh subscription.
     *
     * @param string $region             The region ID. See getRegions().
     * @param string $plan               The plan. See Subscription::$availablePlans.
     * @param string $title              The project title.
     * @param int    $storage            The storage of each environment, in MiB.
     * @param int    $environments       The number of available environments.
     * @param array  $activationCallback An activation callback for the subscription.
     *
     * @see PlatformClient::getRegions()
     * @see Subscription::wait()
     *
     * @return Subscription
     *   A subscription, representing a project. Use Subscription::wait() or
     *   similar code to wait for the subscription's project to be provisioned
     *   and activated.
     */
    public function createSubscription($region, $plan = 'development', $title = null, $storage = null, $environments = null, array $activationCallback = null): ?Subscription
    {
        $url = $this->accountsEndpoint . 'subscriptions';
        $values = $this->cleanRequest([
          'project_region' => $region,
          'plan' => $plan,
          'project_title' => $title,
          'storage' => $storage,
          'environments' => $environments,
          'activation_callback' => $activationCallback,
        ]);

        if ($result = Subscription::create($this, $values)) {
            return new Subscription($result->getData(), $url, $this);
        }
        return null;
    }

    /**
     * Get a list of your Platform.sh subscriptions.
     *
     * @return Subscription[]
     */
    public function getSubscriptions(?SubscriptionQuery $query = null): Collection
    {
        return Subscription::getCollection($this, $query);
    }

    /**
     * Get a subscription by its ID.
     */
    public function getSubscription(int $id): ?Subscription
    {
        return Subscription::get($this, $id);
    }

    /**
     * Estimate the cost of a subscription.
     *
     * @param string $plan         The plan (see Subscription::$availablePlans).
     * @param int    $storage      The allowed storage per environment (in GiB).
     * @param int    $environments The number of environments.
     * @param int    $users        The number of users.
     *
     * @return array An array containing at least 'total' (a formatted price).
     */
    public function getSubscriptionEstimate(string $plan, int $storage, int $environments, int $users): array
    {
        $options = [];
        $options['query'] = [
            'plan' => $plan,
            'storage' => $storage,
            'environments' => $environments,
            'user_licenses' => $users,
        ];

        return $this->getConnector()->sendToAccounts('estimate', 'get', $options);
    }

    /**
     * Get a list of available regions.
     *
     * @return Region[]
     */
    public function getRegions(RegionQuery $query = null): Collection
    {
        return Region::getCollection($this, $query);
    }

    /**
     * Get a region by its ID.
     */
    public function getRegion(string $id): ?Region
    {
        return Region::get($this, $id);
    }

    /**
     * Get plan records.
     *
     * @param PlanRecordQuery|null $query A query to restrict the returned plans.
     *
     * @return PlanRecord[]
     */
    public function getPlanRecords(PlanRecordQuery $query = null)
    {
        return PlanRecord::getCollection($this, $query);
    }

    /**
     * Get usage records.
     *
     * @param UsageRecordQuery|null $query A query to restrict the returned plans.
     *
     * @return UsageRecord[]
     */
    public function getUsageRecords(UsageRecordQuery $query = null): Collection
    {
        return UsageRecord::getCollection($this, $query);
    }

    /**
     * Get a list of Platform.sh trials.
     *
     * @param TrialQuery|null $query
     *
     * @return Collection
     */
    public function getTrials(TrialQuery $query = null)
    {
        return Trial::getCollection($this, $query);
    }

    /**
     * Get a trial by its ID.
     *
     * @param int $id
     *
     * @return Trial|false
     */
    public function getTrial($id)
    {
        return Trial::get($this, $id);
    }

    /**
     * Get a list of Platform.sh transactions.
     *
     * @param TransactionQuery|null $query
     *
     * @return Collection
     */
    public function getTransactions(TransactionQuery $query = null)
    {
        return Transaction::getCollection($this, $query);
    }

    /**
     * Get a transaction by its ID.
     *
     * @param int $id
     *
     * @return Transaction|false
     */
    public function getTransaction($id)
    {
        return Transaction::get($this, $id);
    }



}
