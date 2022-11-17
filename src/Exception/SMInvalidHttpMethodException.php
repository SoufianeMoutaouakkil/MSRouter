<?php

declare(strict_types=1);

namespace SMRouter\Exception;

use InvalidArgumentException;

final class SMInvalidHttpMethodException extends InvalidArgumentException implements SMExceptionInterface
{
    # to simplify implimentation
}