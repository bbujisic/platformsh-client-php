<?php

namespace Platformsh\Client\Model;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Psr7\Request;
use Platformsh\Client\DataStructure\ApiCollection;
use Platformsh\Client\PlatformClient;
use Platformsh\Client\Query\QueryInterface;
use Psr\Http\Message\RequestInterface;
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

    /** @var ClientInterface */
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
    public function __construct(array $data, $baseUrl = null, ClientInterface $client = null, $full = false)
    {
        $this->setData($data);
        $this->client = $client ?: new Client();
        $this->baseUrl = (string) $baseUrl;
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
            $data = $client->getConnector()->send($path);

            // @todo: next level: remove url and guzzle client from the constructor. PlatformClient should be enough.
            $url = $client->getConnector()->getAccountsEndpoint().$path;
            return new static($data, $url, $client->getConnector()->getClient(), true);
        } catch (BadResponseException $e) {
            $response = $e->getResponse();
            // The API may throw either 404 (not found) or 422 (the requested entity id does not exist).
            if ($response && in_array($response->getStatusCode(), [404, 422])) {
                return false;
            }
            throw $e;
        }
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
     *
     * @return Result
     */
    public static function create(PlatformClient $client, array $body)
    {
        if ($errors = static::checkNew($body)) {
            $message = "Cannot create resource due to validation error(s): " . implode('; ', $errors);
            throw new \InvalidArgumentException($message);
        }

        $url = $client->getConnector()->getAccountsEndpoint().static::COLLECTION_PATH;
        $request = new Request('post', $url, [], \GuzzleHttp\json_encode($body));

        $data = $client->getConnector()->sendRequest($request);

        return new Result($data, $url, $client->getConnector()->getClient(), get_called_class());
    }

    /**
     * Send a Guzzle request.
     *
     * Using this method allows exceptions to be standardized.
     *
     * @param RequestInterface $request
     * @param ClientInterface  $client
     * @param array            $options
     *
     * @internal
     * @deprecated
     *
     * @return array
     */
    public static function send(RequestInterface $request, ClientInterface $client, array $options = [])
    {
        // @todo: delete me!!!!
        $response = null;
        try {
            $response = $client->send($request, $options);
            $body = $response->getBody()->getContents();
            $data = [];
            if ($body) {
                $response->getBody()->seek(0);
                $body = $response->getBody()->getContents();
                $data = \GuzzleHttp\json_decode($body, true);
            }

            return (array) $data;
        } catch (BadResponseException $e) {
            throw ApiResponseException::create($e->getRequest(), $e->getResponse());
        } catch (\InvalidArgumentException $e) {
            throw ApiResponseException::create($request, $response);
        }
    }

    /**
     * A simple helper function to send an HTTP request.
     *
     * @param string $url
     * @param string $method
     * @param array  $options
     *
     * @return array
     */
    protected function sendRequest($url, $method = 'get', array $options = [])
    {
        return $this->send(
          new Request($method, $url),
          $this->client,
          $options
        );
    }

    /**
     * Get a subresource from a hal link.
     */
    protected function getLinkedResource(string $rel, string $class): ApiResourceBase
    {
        $url = $this->getLink($rel);

        // This is really sad. Subscription has ClientInterface in its context, but not PlatformClient,
        // nor Connector. Therefore, it needs some code duplication. Ideally, this method should not be aware of
        // Guzzle and get the project simply by `$class::get($this->client, $url)`
        // @todo: Refactor after changing model constructors to accept the entire PlatformClient, not Guzzle!
        if ($body = $this->client->get($url)->getBody()->getContents()) {
            $data = \GuzzleHttp\json_decode($body, true);

            return new $class($data, $url, $this->client);
        }

        return false;
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
     * Create an array of resource instances from a collection's JSON data.
     *
     * @param array           $data    The deserialized JSON from the
     *                                 collection (i.e. a list of resources,
     *                                 each of which is an array of data).
     * @param string          $baseUrl The URL to the collection.
     * @param ClientInterface $client  A suitably configured Guzzle client.
     *
     * @deprecated
     *
     * @return ResourceCollection
     */
    public static function wrapCollection(array $data, $baseUrl, ClientInterface $client, $page = 1)
    {
        $collection_name = static::COLLECTION_NAME;

        if ($collection_name && isset($data[$collection_name])) {
            $items = $data[$collection_name];
            $count = $data['count'];
        } else {
            $items = $data;
            $count = count($data);
        }

        $collection = new ResourceCollection($count, $page);

        foreach ($items as $item) {
            $collection->push(new static($item, $baseUrl, $client));
        }

        return $collection;
    }

    /**
     * Execute an operation on the resource.
     *
     * @param string $op
     * @param string $method
     * @param array  $body
     *
     * // HUH?!
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
        $request= new Request($method, $this->getLink("#$op"));
        $data = $this->send($request, $this->client, $options);

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
        $data = $this->sendRequest($this->getUri(), 'delete');

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
        $request = new Request('get', $this->getUri());
        $this->setData(self::send($request, $this->client, $options));
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
