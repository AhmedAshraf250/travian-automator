<?php

namespace App\Enums;

/**
 * Defines the main activity log categories used by the automation system.
 */
enum ActivityType: string
{
    case Sync = 'sync';
    case Build = 'build';
    case Transfer = 'transfer';
    case Train = 'train';
    case Login = 'login';
    case Logout = 'logout';
    case Import = 'import';
    case Manual = 'manual';
}
