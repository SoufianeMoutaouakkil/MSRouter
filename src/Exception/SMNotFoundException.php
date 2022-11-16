<?php

declare(strict_types=1);

namespace SMRouter\Exception;

use InvalidArgumentException;

final class SMNotFoundException extends InvalidArgumentException implements SMExceptionInterface
{
    protected $message = 'Page not found';
    protected $code = 404;
}
