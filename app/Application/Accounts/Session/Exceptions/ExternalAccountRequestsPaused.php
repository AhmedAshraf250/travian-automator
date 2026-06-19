<?php

namespace App\Application\Accounts\Session\Exceptions;

use RuntimeException;

/**
 * Raised when a queued account session should stop before touching Travian.
 */
class ExternalAccountRequestsPaused extends RuntimeException {}
