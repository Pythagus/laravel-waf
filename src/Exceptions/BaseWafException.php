<?php

namespace Pythagus\LaravelWaf\Exceptions;

use RuntimeException;

/**
 * Base exception class raised when an issue was found
 * with the WAF protections or configurations.
 *
 * @author: Damien MOLINA
 */
abstract class BaseWafException extends RuntimeException {}