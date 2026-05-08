<?php

namespace App\Application\Accounts\Session\Exceptions;

use RuntimeException;

/**
 * Thrown when a Travian login page does not expose a recognizable login form.
 */
class LoginFormNotFoundException extends RuntimeException {}
