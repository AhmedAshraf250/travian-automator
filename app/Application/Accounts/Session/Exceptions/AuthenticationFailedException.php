<?php

namespace App\Application\Accounts\Session\Exceptions;

use RuntimeException;

/**
 * Thrown when the application cannot establish an authenticated Travian session.
 */
class AuthenticationFailedException extends RuntimeException {}
