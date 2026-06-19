<?php

namespace App\Application\Accounts\Connection;

use RuntimeException;

/**
 * Stops the current account flow after scheduling a connection retry.
 */
class AccountConnectionBackoffStarted extends RuntimeException {}
