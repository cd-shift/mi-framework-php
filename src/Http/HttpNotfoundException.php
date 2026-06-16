<?php

declare(strict_types=1);

namespace Http;

use Exceptions\MiException;

/**
 * Represents the "route not found" HTTP error condition.
 */
class HttpNotFoundException extends MiException
{
}
