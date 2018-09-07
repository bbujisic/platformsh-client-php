<?php

namespace Platformsh\Client\Model\Accounts;

use Platformsh\Client\DataStructure\Collection;
use Platformsh\Client\Model\ApiResourceBase;
use Platformsh\Client\PlatformClient;

abstract class AccountsApiResourceBase extends ApiResourceBase
{

    const COLLECTION_NAME = null;
    const COLLECTION_PATH = null;

    /**
     * Get a resource by its ID.
     *
     * @param PlatformClient $client A parent entity in the API. Use PlatformClient for root level resources.
     * @param string|int     $id     The ID of the resource.
     *
     * @return static|null The resource object, or false if the resource is not found.
     */
    public static function get(PlatformClient $client, $id): ?ApiResourceBase
    {
        $uri = $client->getConnector()->getAccountsEndpoint().static::COLLECTION_PATH.'/'.$id;

        return parent::getDirect($client, $uri);
    }

    /**
     * Get a collection of resources.
     *
     * @param PlatformClient $client A suitably configured Platform client.
     * @param QueryInterface $query  An instance of query interface. It will be used to build a guzzle query.
     *
     * @return Collection;
     */
    public static function getCollection(PlatformClient $client, ?QueryInterface $query = null)
    {
        return new Collection(static::class, $client, $query);
    }
}