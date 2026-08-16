<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown when a review is not allowed — the stay is not completed, or it has
 * already been reviewed. Rendered as HTTP 422.
 */
class ReviewException extends RuntimeException {}
