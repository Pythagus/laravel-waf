<?php

namespace Pythagus\LaravelWaf\Exceptions;

/**
 * Exception raised when one of the HTTP header value
 * doesn't match the protection rules.
 *
 * @author: Damien MOLINA
 */
class InvalidHttpValueException extends BaseWafProtectionException {}