<?php

namespace Platformsh\Client\Model\Billing;

use GuzzleHttp\ClientInterface;
use Platformsh\Client\Model\Accounts\AccountsApiResourceBase;
use Platformsh\Client\Model\ApiResourceBase;

/**
 * Represents a Platform.sh plan record.
 *
 * @property-read int    $id              The ID of the record.
 * @property-read int    $subscription_id The ID of the subscription.
 * @property-read string $usage_group     The machine name of the usage group.
 * @property-read int    $quantity        The quantity of the usage.
 * @property-read string $start           The start date of the usage record (ISO 8601).
 * @property-read string $end             The end date of the usage record (ISO 8601).
 * @property-read int    $plan_record     The plan record ID.
 */
class UsageRecord extends AccountsApiResourceBase
{

    const COLLECTION_NAME = 'usage';
    const COLLECTION_PATH = 'records/usage';

}
