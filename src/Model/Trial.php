<?php

namespace Platformsh\Client\Model;

use GuzzleHttp\ClientInterface;

/**
 * Represents a Platform.sh trial.
 *
 * @property-read int    $id
 * @property-read string $owner
 * @property-read string $model
 * @property-read string $type
 * @property-read string $status
 * @property-read array  $spend
 * @property-read string $created
 * @property-read string $updated
 * @property-read string $expiration
 */
class Trial extends Resource
{

    /**
     * @inheritdoc
     */
    public static function wrapCollection(array $data, $baseUrl, ClientInterface $client)
    {
        $data = isset($data['trials']) ? $data['trials'] : [];

        return parent::wrapCollection($data, $baseUrl, $client);
    }
}
