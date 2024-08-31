<?php

namespace Pythagus\LaravelWaf\Exceptions;

/**
 * Exception raised when a malicious pattern was found
 * in the accessed URL. 
 *
 * @author: Damien MOLINA
 */
class SuspiciousUrlException extends BaseWafProtectionException {}