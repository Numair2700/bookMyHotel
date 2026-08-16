<?php

namespace App\Modules\Payment\Data;

/**
 * The outcome of charging a token at the gateway. Carries only a reference the
 * gateway returns — never any card data.
 */
final class GatewayResult
{
    public function __construct(
        public readonly bool $successful,
        public readonly string $reference,
        public readonly ?string $failureReason = null,
    ) {}
}
