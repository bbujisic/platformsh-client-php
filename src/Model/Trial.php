<?php

namespace Platformsh\Client\Model;

use Platformsh\Client\Model\Accounts\AccountsApiResourceBase;

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
class Trial extends AccountsApiResourceBase
{
    const COLLECTION_NAME = 'trials';
    const COLLECTION_PATH = 'trials';

    /**
     * Prevent deletion.
     */
    public function delete()
    {
        throw new \BadMethodCallException("Trials cannot be deleted.");
    }
}
