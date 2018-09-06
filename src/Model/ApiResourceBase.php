<?php

namespace Platformsh\Client\Model;

use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Exception\RequestException;
use Platformsh\Client\DataStructure\ApiCollection;
use Platformsh\Client\PlatformClient;
use Platformsh\Client\Query\QueryInterface;
use Platformsh\Client\Exception\ApiResponseException;
use Platformsh\Client\Exception\OperationUnavailableException;
use Platformsh\Client\DataStructure\Collection;

/**
 * The base class for API resources.
 */
abstract class ApiResourceBase implements \ArrayAccess
{

    const COLLECTION_NAME = NULL;
    const COLLECTION_PATH = NULL;

    /** @var array */
    protected static $required = [];

    /** @var PlatformClient */
    protected $client;

    /** @var string */
    protected $baseUrl;

    /** @var array */
    protected $data;

    /** @var bool */
    protected $isFull = false;

    /**
     * Resource constructor.
     *
     * @param array           $data    The raw data for the resource
     *                                 (as deserialized from JSON).
     * @param string          $baseUrl The absolute URL to the resource or its
     *                                 collection.
     * @param ClientInterface $client  A suitably configured Guzzle client.
     * @param bool            $full    Whether the data is a complete
     *                                 representation of the resource.
     */
    public function __construct(array $data, string $baseUrl, PlatformClient $client, bool $full = false)
    {
        $this->setData($data);
        $this->client = $client;
        $this->baseUrl = $baseUrl;
        $this->isFull = $full;
    }

    /**
     * @param string $baseUrl
     */
    public function setBaseUrl($baseUrl)
    {
        $this->baseUrl = $baseUrl;
    }

    /**
     * @inheritdoc
     */
    public function offsetExists($offset)
    {
        return $this->hasProperty($offset);
    }

    /**
     * Magic getter, allowing resource properties to be accessed.
     *
     * Properties can be documented in implementing classes' docblocks.
     *
     * @param string $name
     *
     * @return mixed
     */
    public function __get($name)
    {
        return $this->getProperty($name, false);
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    public function __isset($name) {
        return $this->hasProperty($name);
    }

    /**
     * @inheritdoc
     */
    public function offsetGet($offset)
    {
        return $this->getProperty($offset, false);
    }

    /**
     * Prevent setting magic properties.
     *
     * @param string $name
     * @param mixed  $value
     *
     * @throws \BadMethodCallException
     */
    public function __set($name, $value)
    {
        throw new \BadMethodCallException('Properties are read-only');
    }

    /**
     * @inheritdoc
     *
     * @throws \BadMethodCallException
     */
    public function offsetSet($offset, $value)
    {
        throw new \BadMethodCallException('Properties are read-only');
    }

    /**
     * @inheritdoc
     *
     * @throws \BadMethodCallException
     */
    public function offsetUnset($offset)
    {
        throw new \BadMethodCallException('Properties are read-only');
    }

    /**
     * Get all of the API data for this resource.
     *
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * Ensure that this is a full representation of the resource (not a stub).
     */
    public function ensureFull()
    {
        if (!$this->isFull) {
            $this->refresh();
        }
    }

    /**
     * Get a resource by its ID.
     *
     * @param PlatformClient $client  A suitably configured Platform client.
     * @param string|int     $id      The ID of the resource.
     *
     * @return static|false The resource object, or false if the resource is not found.
     */
    public static function get(PlatformClient $client, $id)
    {
        $path = static::COLLECTION_PATH.'/'.$id;
        try {
            $data = $client->getConnector()->sendToAccounts($path);
            // @todo: next level: remove url once you figure out what to do with the foundation resources?
            $url = $client->getConnector()->getAccountsEndpoint().$path;

            return new static($data, $url, $client, true);
        } catch (\Exception $e) {
            // The API may throw either 404 (not found) or 422 (the requested entity id does not exist).
            if (!in_array($e->getCode(), [404, 422])) {
                throw $e;
            }
        }
        return null;
    }

    /**
     * Get a collection of resources.
     *
     * @param PlatformClient $client  A suitably configured Platform client.
     * @param QueryInterface $query   An instance of query interface. It will be used to build a guzzle query.
     *
     * @return Collection;
     */
    public static function getCollection(PlatformClient $client, ?QueryInterface $query = null)
    {
        return new Collection(static::class, $client, $query);
    }

    /**
     * Create a resource.
     *
     * @param PlatformClient  $client
     * @param array           $body
     * @param string          $uri    If not provided, a default uri will be generated from entity collection path.
     *
     * @return Result
     */
    public static function create(PlatformClient $client, array $body, string $uri = null)
    {
        if ($errors = static::checkNew($body)) {
            $message = "Cannot create resource due to validation error(s): " . implode('; ', $errors);
            throw new \InvalidArgumentException($message);
        }

        $uri = ($uri ?: $client->getConnector()->getAccountsEndpoint().static::COLLECTION_PATH);

        $data = $client->getConnector()->sendToUri(
            $uri,
            'post',
            [
                'headers' => ['Content-Type' => 'application/json'],
                'body' => \GuzzleHttp\json_encode($body),
            ]
        );

        return new Result($data, $uri, $client, get_called_class());
    }

    /**
     * Get a subresource from a hal link.
     */
    protected function getLinkedResource(string $rel, string $class, $id = null): ?ApiResourceBase
    {
        $url = $this->getLink($rel).($id ? '/'.urlencode($id) : '');

        try {
            $data = $this->client->getConnector()->sendToUri($url);

            return new $class($data, $url, $this->client);
        } catch (RequestException $e) {
            $response = $e->getResponse();
            // The API may throw either 404 (not found) or 422 (the requested entity id does not exist).
            if ($response && in_array($response->getStatusCode(), [404, 422])) {
                return null;
            }
            throw $e;
        }
    }

    /**
     * Get a subresource from a hal link.
     *
     * @return ApiResourceBase[]
     */
    protected function getLinkedResources(string $rel, string $class, QueryInterface $query = null): ?array
    {
        $out = [];
        $url = $this->getLink($rel);

        $options = [];
        if($query) {
            $options['query'] = $query->getParams();
        }

        if ($data = $this->client->getConnector()->sendToUri($url, 'get', $options)) {
            foreach ($data as $datum) {
                $out[] = new $class($datum, $url, $this->client);
            }
        }

        return $out;
    }

    /**
     * Get the required properties for creating a new resource.
     *
     * @return array
     */
    public static function getRequired()
    {
        return static::$required;
    }

    /**
     * Validate a new resource.
     *
     * @param array $data
     *
     * @return string[] An array of validation errors.
     */
    protected static function checkNew(array $data)
    {
        $errors = [];
        if ($missing = array_diff(static::getRequired(), array_keys($data))) {
            $errors[] = 'Missing: ' . implode(', ', $missing);
        }
        foreach ($data as $key => $value) {
            $errors += static::checkProperty($key, $value);
        }
        return $errors;
    }

    /**
     * Validate a property of the resource, for creating or updating.
     *
     * @param string $property
     * @param mixed  $value
     *
     * @return string[] An array of validation errors.
     */
    protected static function checkProperty($property, $value)
    {
        return [];
    }

    /**
     * Execute an operation on the resource.
     *
     * @param string $op
     * @param string $method
     * @param array  $body
     *
     * @return Result
     */
    protected function runOperation($op, $method = 'post', array $body = [])
    {
        if (!$this->operationAvailable($op)) {
            throw new OperationUnavailableException("Operation not available: $op");
        }
        $options = [];
        if (!empty($body)) {
            $options['json'] = $body;
        }
        $data = $this->client->getConnector()->sendToUri($this->getLink("#$op"), $method, $options);

        return new Result($data, $this->baseUrl, $this->client, get_called_class());
    }

    /**
     * Run a long-running operation.
     *
     * @param string $op
     * @param string $method
     * @param array  $body
     *
     * @return Activity
     */
    protected function runLongOperation($op, $method = 'post', array $body = [])
    {
        $result = $this->runOperation($op, $method, $body);
        $activities = $result->getActivities();
        if (count($activities) !== 1) {
            trigger_error(sprintf("Expected one activity, found %d", count($activities)), E_USER_WARNING);
        }

        return reset($activities);
    }

    /**
     * Check whether a property exists in the resource.
     *
     * @param string $property
     * @param bool $lazyLoad
     *
     * @return bool
     */
    public function hasProperty($property, $lazyLoad = true)
    {
        if (!$this->isProperty($property)) {
            return false;
        }
        if (!array_key_exists($property, $this->data) && $lazyLoad) {
            $this->ensureFull();
        }

        return array_key_exists($property, $this->data);
    }

    /**
     * Get a property of the resource.
     *
     * @param string $property
     * @param bool   $required
     * @param bool   $lazyLoad
     *
     * @throws \InvalidArgumentException If $required is true and the property
     *                                   is not found.
     *
     * @return mixed|null
     *   The property value, or null if the property does not exist (and
     *   $required is false).
     */
    public function getProperty($property, $required = true, $lazyLoad = true)
    {
        if (!$this->hasProperty($property, $lazyLoad)) {
            if ($required) {
                throw new \InvalidArgumentException("Property not found: $property");
            }
            return null;
        }

        return $this->data[$property];
    }

    /**
     * Delete the resource.
     *
     * @return Result
     */
    public function delete()
    {
        $data = $this->client->getConnector()->sendToUri($this->getUri(), 'delete');

        return new Result($data, $this->getUri(), $this->client, get_called_class());
    }

    /**
     * Update the resource.
     *
     * This updates the resource's internal data with the API response.
     *
     * @param array $values
     *
     * @return Result
     */
    // @todo: probably broken
    public function update(array $values)
    {
        if ($errors = $this->checkUpdate($values)) {
            $message = "Cannot update resource due to validation error(s): " . implode('; ', $errors);
            throw new \InvalidArgumentException($message);
        }
        $data = $this->runOperation('edit', 'patch', $values)->getData();
        if (isset($data['_embedded']['entity'])) {
            $this->setData($data['_embedded']['entity']);
            $this->isFull = true;
        }

        return new Result($data, $this->baseUrl, $this->client, get_called_class());
    }

    /**
     * Validate values for update.
     *
     * @param array $values
     *
     * @return string[] An array of validation errors.
     */
    protected static function checkUpdate(array $values)
    {
        $errors = [];
        foreach ($values as $key => $value) {
            $errors += static::checkProperty($key, $value);
        }
        return $errors;
    }

    /**
     * Get the resource's URI.
     *
     * @param bool $absolute
     *
     * @return string
     */
    public function getUri($absolute = true)
    {
        return $this->getLink('self', $absolute);
    }

    /**
     * Refresh the resource.
     *
     * @param array $options
     */
    public function refresh(array $options = [])
    {
        $this->setData($this->client->getConnector()->sendToUri($this->getUri(), 'get', $options));
        $this->isFull = true;
    }

    /**
     * @param array $data
     */
    protected function setData(array $data)
    {
        $this->data = $data;
    }

    /**
     * Check whether an operation is available on the resource.
     *
     * @param string $op
     *
     * @return bool
     */
    public function operationAvailable($op)
    {
        return isset($this->data['_links']["#$op"]['href']);
    }

    /**
     * Check whether the resource has a link.
     *
     * @param $rel
     *
     * @return bool
     */
    public function hasLink($rel)
    {
        return isset($this->data['_links'][$rel]['href']);
    }

    /**
     * Get a link for a given resource relation.
     *
     * @param string $rel
     * @param bool   $absolute
     *
     * @return string
     */
    public function getLink($rel, $absolute = true)
    {
        if (!$this->hasLink($rel)) {
            throw new \InvalidArgumentException("Link not found: $rel");
        }
        $url = $this->data['_links'][$rel]['href'];
        if ($absolute && strpos($url, '//') === false) {
            $url = $this->makeAbsoluteUrl($url);
        }
        return $url;
    }

    /**
     * Make a URL absolute, based on the base URL.
     *
     * @param string $relativeUrl
     * @param string $baseUrl
     *
     * @return string
     */
    protected function makeAbsoluteUrl($relativeUrl, $baseUrl = null)
    {
        $baseUrl = $baseUrl ?: $this->baseUrl;
        if (empty($baseUrl)) {
            throw new \RuntimeException('No base URL');
        }
        $base = \GuzzleHttp\Psr7\uri_for($baseUrl);

        return $base->withPath($relativeUrl)->__toString();
    }

    /**
     * Get a list of this resource's property names.
     *
     * @return string[]
     */
    public function getPropertyNames()
    {
        $keys = array_filter(array_keys($this->data), [$this, 'isProperty']);
        return $keys;
    }

    /**
     * Get an array of this resource's properties and their values.
     *
     * @param bool $lazyLoad
     *
     * @return array
     */
    public function getProperties($lazyLoad = true)
    {
        if ($lazyLoad) {
            $this->ensureFull();
        }
        $keys = $this->getPropertyNames();

        return array_intersect_key($this->data, array_flip($keys));
    }

    /**
     * @param string $key
     *
     * @return bool
     */
    protected function isProperty($key)
    {
        return $key !== '_links' && $key !== '_embedded';
    }
}
