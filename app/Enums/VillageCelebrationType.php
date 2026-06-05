<?php

namespace App\Enums;

/**
 * Defines the supported village celebration selection modes.
 */
enum VillageCelebrationType: string
{
    case Auto = 'auto';
    case Small = 'small';
    case Great = 'great';
}
