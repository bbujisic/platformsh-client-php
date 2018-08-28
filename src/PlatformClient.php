<?php

namespace Platformsh\Client;

use GuzzleHttp\Exception\BadResponseException;
use Platformsh\Client\Connection\Connector;
use Platformsh\Client\Connection\ConnectorInterface;
use Platformsh\Client\DataStructure\Collection;
use Platformsh\Client\Exception\ApiResponseException;
use Platformsh\Client\Model\Billing\PlanRecord;
use Platformsh\Client\Model\Project;
use Platformsh\Client\Model\Region;
use Platformsh\Client\Model\Result;
use Platformsh\Client\Model\SshKey;
use Platformsh\Client\Model\Subscription;
use Platformsh\Client\Query\PlanRecordQuery;
use Platformsh\Client\Query\RegionQuery;
use Platformsh\Client\Query\SubscriptionQuery;

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
            return Project::getDirect($this, "$scheme://$hostname/api/projects");
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
        $client = $this->connector->getClient();
        $projects = [];
        foreach ($data['projects'] as $project) {
            // Each project has its own endpoint on a Platform.sh region.
            $projects[] = new Project($project, $project['endpoint'], $client);
        }

        return $projects;
    }

    /**
     * Get account information for the logged-in user.
     *
     * @param bool $reset
     *
     * @return array
     */
    public function getAccountInfo($reset = false)
    {
        if (!isset($this->accountInfo) || $reset) {
            $url = $this->accountsEndpoint . 'me';
            try {
                $this->accountInfo = $this->simpleGet($url);
            }
            catch (BadResponseException $e) {
                throw ApiResponseException::create($e->getRequest(), $e->getResponse(), $e->getPrevious());
            }
        }

        return $this->accountInfo;
    }

    /**
     * Get a URL and return the JSON-decoded response.
     *
     * @param string $url
     * @param array  $options
     *
     * @return array
     */
    private function simpleGet($url, array $options = [])
    {
        return (array) \GuzzleHttp\json_decode(
          $this->getConnector()
               ->getClient()
               ->request('get', $url, $options)
               ->getBody()
               ->getContents(),
          true
        );
    }

    /**
     * Get the logged-in user's SSH keys.
     *
     * @param bool $reset
     *
     * @return SshKey[]
     */
    public function getSshKeys($reset = false)
    {
        $data = $this->getAccountInfo($reset);

        return SshKey::wrapCollection($data['ssh_keys'], $this->accountsEndpoint, $this->connector->getClient());
    }

    /**
     * Get a single SSH key by its ID.
     *
     * @param string|int $id
     *
     * @return SshKey|false
     */
    public function getSshKey($id)
    {
        $url = $this->accountsEndpoint . 'ssh_keys';

        return SshKey::get($id, $url, $this->connector->getClient());
    }

    /**
     * Add an SSH public key to the logged-in user's account.
     *
     * @param string $value The SSH key value.
     * @param string $title A title for the key (optional).
     *
     * @return Result
     */
    public function addSshKey($value, $title = null)
    {
        $values = $this->cleanRequest(['value' => $value, 'title' => $title]);
        $url = $this->accountsEndpoint . 'ssh_keys';

        return SshKey::create($values, $url, $this->connector->getClient());
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
    public function createSubscription($region, $plan = 'development', $title = null, $storage = null, $environments = null, array $activationCallback = null): Subscription
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

        return Subscription::create($this, $values);
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
        try {
            return $this->simpleGet($this->accountsEndpoint . 'estimate', $options);
        } catch (BadResponseException $e) {
            throw ApiResponseException::create($e->getRequest(), $e->getResponse(), $e->getPrevious());
        }
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
     * Get plan records.
     *
     * @param PlanRecordQuery|null $query A query to restrict the returned plans.
     *
     * @return PlanRecord[]
     */
    public function getPlanRecords(PlanRecordQuery $query = null)
    {
        $url = $this->accountsEndpoint . 'records/plan';
        $options = [];

        if ($query) {
            $options['query'] = $query->getParams();
        }

        return PlanRecord::getCollection($url, 0, $options, $this->connector->getClient());
    }
}
