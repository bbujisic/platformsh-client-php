<?php

namespace Platformsh\Client\Model;

use Platformsh\Client\Model\Accounts\AccountsApiResourceBase;

/**
 * Represents a Platform.sh transaction.
 *
 * @property-read int    $id
 * @property-read string $owner
 * @property-read int    $order_id
 * @property-read string $payment_method
 * @property-read string $message
 * @property-read string $status
 * @property-read string $remote_status
 * @property-read int    $amount
 * @property-read string $currency
 * @property-read string $created
 * @property-read string $updated
 */
class Transaction extends AccountsApiResourceBase
{
    const COLLECTION_NAME = 'transactions';
    const COLLECTION_PATH = 'transactions';

    /**
     * Prevent deletion.
     */
    public function delete()
    {
        throw new \BadMethodCallException("Transactions cannot be deleted.");
    }
}
