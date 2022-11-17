<?php

declare(strict_types=1);

namespace MSDEV\MSRouter\Exception;

use InvalidArgumentException;

final class MSNotFoundException extends InvalidArgumentException implements MSExceptionInterface
{
    protected $message = 'Page not found';
    protected $code = 404;
}
