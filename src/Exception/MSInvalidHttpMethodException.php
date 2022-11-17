<?php

declare(strict_types=1);

namespace MSDEV\MSRouter\Exception;

use InvalidArgumentException;

final class MSInvalidHttpMethodException extends InvalidArgumentException implements MSExceptionInterface
{
    # to simplify implimentation
}
