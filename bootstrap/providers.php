<?php

use App\Providers\AppServiceProvider;
use App\Providers\ClientExtensionServiceProvider;
use App\Providers\ClientServiceProvider;

return [
    AppServiceProvider::class,
    ClientServiceProvider::class,
    ClientExtensionServiceProvider::class,
];
