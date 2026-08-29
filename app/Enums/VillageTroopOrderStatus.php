<?php

namespace App\Enums;

enum VillageTroopOrderStatus: string
{
    case Scheduled = 'scheduled';
    case Claimed = 'claimed';
    case Submitted = 'submitted';
    case Completed = 'completed';
    case WaitingResources = 'waiting_resources';
    case Cancelled = 'cancelled';
    case Failed = 'failed';
}
