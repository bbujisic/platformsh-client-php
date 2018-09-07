<?php

namespace Platformsh\Client\Model;

use Platformsh\Client\Model\Accounts\AccountsApiResourceBase;

/**
 * Account information for a Platform.sh user.
 *
 * @property-read string $id
 * @property-read string $created_at
 * @property-read string $updated_at
 * @property-read bool   $has_key
 * @property-read string $display_name
 * @property-read string $email
 */
class Account extends AccountsApiResourceBase
{
    const COLLECTION_PATH = 'users';
}
