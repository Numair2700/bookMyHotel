<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown when a stay cannot be booked because the calendar is incomplete for
 * the range or a night has no rooms left. Rendered as HTTP 422.
 */
class AvailabilityException extends RuntimeException
{
    public function __construct(string $message = 'No rooms remaining for these dates')
    {
        parent::__construct($message);
    }
}
