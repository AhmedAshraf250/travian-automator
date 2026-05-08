<?php

namespace App\Enums;

/**
 * Represents the current automation status of an account.
 */
enum AccountStatus: string
{
    case Active = 'active';
    case Paused = 'paused';
    case Error = 'error';
    case Syncing = 'syncing';
}
