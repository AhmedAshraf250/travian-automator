<?php

namespace App\Enums;

/**
 * Represents the lifecycle state of a troop training queue record.
 */
enum TroopQueueStatus: string
{
    case Pending = 'pending';
    case Training = 'training';
    case Done = 'done';
}
