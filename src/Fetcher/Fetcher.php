<?php

namespace Platformsh\Client\Fetcher;

use GuzzleHttp\Psr7\Request;
use Psr\Http\Message\RequestInterface;
use GuzzleHttp\ClientInterface;
use Platformsh\Client\Exception\ApiResponseException;

/**
 * Fetches the data from the Platform.sh API.
 */
class Fetcher
{

    protected $apiResourceClassName;
    protected $baseUrl;
    protected $request;
    protected $client;
    protected $options;

    public function __construct($apiResourceClassName, $baseUrl, $client, $options)
    {
        $this->apiResourceClassName = $apiResourceClassName;
        $this->baseUrl = $baseUrl;
        $this->request = new Request('get', $baseUrl);
        $this->client = $client;
        $this->options = $options;
    }

    /**
     * Gets a resource from the Platform.sh API and returns an instance of its class.
     */
    public function fetch()
    {
        $data = $this->getData($this->options);

        if (!class_exists($this->apiResourceClassName)) {
            throw new \Exception(sprintf('The class %s does not exist', $this->apiResourceClassName));
        }

        return new $this->apiResourceClassName($data, $this->baseUrl, $this->client, true);

    }

    /**
     * Gets a resource from the Platform.sh API and returns it as a raw array.
     */
    public function fetchRaw()
    {
        return $this->getData($this->options);
    }

    protected function getData(array $options): array
    {
        // START: Code duplication with ApiResourceBase::send()
        $response = null;
        try {
            $response = $this->client->send($this->request, $options);
            $body = $response->getBody()->getContents();
            $data = [];
            if ($body) {
                $response->getBody()->seek(0);
                $body = $response->getBody()->getContents();
                $data = \GuzzleHttp\json_decode($body, true);
            }

            return (array)$data;
        } catch (BadResponseException $e) {
            throw ApiResponseException::create($e->getRequest(), $e->getResponse());
        } catch (\InvalidArgumentException $e) {
            throw ApiResponseException::create($request, $response);
        }
        // END: Code duplication with ApiResourceBase::send()
    }
}