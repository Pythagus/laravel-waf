<?php

namespace Pythagus\LaravelWaf\Exceptions;

/**
 * Exception raised when a blacklist matched the
 * associated HTTP parameter. For example, blacklisted
 * Accept-Language or IP address.
 *
 * @author: Damien MOLINA
 */
class BlacklistedBehaviorException extends BaseWafProtectionException {}