<?php

namespace App\Enums;

/**
 * Represents the execution status of a logged activity.
 */
enum ActivityLogStatus: string
{
    case Pending = 'pending';
    case Running = 'running';
    case Done = 'done';
    case Failed = 'failed';
}
