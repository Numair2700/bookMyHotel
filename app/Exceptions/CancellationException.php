<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown when a reservation cannot be cancelled (for example it is already
 * cancelled or completed). Rendered as HTTP 422.
 */
class CancellationException extends RuntimeException
{
    public function __construct(string $message = 'This reservation cannot be cancelled.')
    {
        parent::__construct($message);
    }
}
