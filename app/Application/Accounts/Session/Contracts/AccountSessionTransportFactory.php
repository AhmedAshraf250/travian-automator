<?php

namespace App\Application\Accounts\Session\Contracts;

use App\Models\Account;

/**
 * Creates the raw isolated HTTP transport for one Travian account.
 */
interface AccountSessionTransportFactory
{
    public function for(Account $account): AccountSession;
}
