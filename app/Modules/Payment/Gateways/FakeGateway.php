<?php

namespace App\Modules\Payment\Gateways;

use App\Modules\Payment\Contracts\PaymentGatewayInterface;
use App\Modules\Payment\Data\GatewayResult;
use Illuminate\Support\Str;

/**
 * A deterministic stand-in for a real gateway (e.g. Stripe test mode). It takes
 * a client-side token and returns a reference, mirroring a tokenised charge
 * without ever handling card data.
 *
 * For demos and tests the behaviour is predictable: a token containing "fail"
 * (like Stripe's decline test cards) is declined; anything else succeeds.
 */
class FakeGateway implements PaymentGatewayInterface
{
    public function charge(string $token, float $amount, string $currency = 'AED'): GatewayResult
    {
        $reference = 'fake_'.Str::lower(Str::random(24));

        if (str_contains(Str::lower($token), 'fail')) {
            return new GatewayResult(false, $reference, 'The card was declined.');
        }

        return new GatewayResult(true, $reference);
    }
}
