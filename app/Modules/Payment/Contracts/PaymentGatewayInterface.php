<?php

namespace App\Modules\Payment\Contracts;

use App\Modules\Payment\Data\GatewayResult;

/**
 * A tokenised payment gateway. The application only ever hands over a token
 * produced client-side and an amount; card details never reach the server,
 * which keeps the platform inside the reduced PCI scope (security checklist).
 *
 * The FakeGateway implements this for local use and tests; a real Stripe driver
 * can be bound in its place without touching any caller.
 */
interface PaymentGatewayInterface
{
    public function charge(string $token, float $amount, string $currency = 'AED'): GatewayResult;
}
