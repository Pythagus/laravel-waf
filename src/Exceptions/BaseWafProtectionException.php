<?php

namespace Pythagus\LaravelWaf\Exceptions;

use RuntimeException;

/**
 * Base exception class raised when a WAF protection
 * was triggered by the HTTP request.
 *
 * @author: Damien MOLINA
 */
abstract class BaseWafProtectionException extends RuntimeException {}