<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Laravel\Horizon\Horizon;
use Laravel\Horizon\HorizonApplicationServiceProvider;

class HorizonServiceProvider extends HorizonApplicationServiceProvider
{
    public function boot(): void
    {
        parent::boot();

        Horizon::night();
    }

    protected function gate(): void
    {
        Gate::define('viewHorizon', function ($user = null) {
            if ($this->app->environment('local')) {
                return true;
            }

            return in_array(optional($user)->email, [
                //
            ]);
        });
    }
}
