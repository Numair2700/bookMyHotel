<?php

namespace App\Providers;

use App\Modules\Payment\Contracts\PaymentGatewayInterface;
use App\Modules\Payment\Contracts\PaymentServiceInterface;
use App\Modules\Payment\Gateways\FakeGateway;
use App\Modules\Payment\PaymentService;
use Illuminate\Support\ServiceProvider;

/**
 * Wires the Payment bounded module. Swapping the gateway (e.g. to a real Stripe
 * driver) is a one-line change here; no caller names the concrete class.
 */
class PaymentServiceProvider extends ServiceProvider
{
    /**
     * @var array<class-string, class-string>
     */
    public array $bindings = [
        PaymentGatewayInterface::class => FakeGateway::class,
        PaymentServiceInterface::class => PaymentService::class,
    ];
}
