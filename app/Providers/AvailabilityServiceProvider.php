<?php

namespace App\Providers;

use App\Modules\Availability\AvailabilityService;
use App\Modules\Availability\Contracts\AvailabilityServiceInterface;
use Illuminate\Support\ServiceProvider;

/**
 * Wires the Availability bounded module. Callers depend on the interface; this
 * provider is the only place that names the concrete implementation, so the
 * module can be swapped or extracted without touching its consumers.
 */
class AvailabilityServiceProvider extends ServiceProvider
{
    /**
     * @var array<class-string, class-string>
     */
    public array $bindings = [
        AvailabilityServiceInterface::class => AvailabilityService::class,
    ];
}
