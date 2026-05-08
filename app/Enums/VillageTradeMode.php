<?php

namespace App\Enums;

/**
 * Defines how a village participates in inter-village resource transfers.
 */
enum VillageTradeMode: string
{
    case Donor = 'donor';
    case Receiver = 'receiver';
    case Balanced = 'balanced';
    case Paused = 'paused';
}
