<?php

use App\Providers\AppServiceProvider;
use App\Providers\AvailabilityServiceProvider;
use App\Providers\FortifyServiceProvider;
use App\Providers\PaymentServiceProvider;

return [
    AppServiceProvider::class,
    AvailabilityServiceProvider::class,
    PaymentServiceProvider::class,
    FortifyServiceProvider::class,
];
