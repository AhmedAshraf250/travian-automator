<?php

namespace App\Application\Accounts\Session\Contracts;

use App\Models\Account;

/**
 * Creates isolated transport sessions for Travian accounts.
 */
interface AccountSessionFactory
{
    /**
     * Create a session instance dedicated to the provided account.
     */
    public function for(Account $account): AccountSession;
}
