<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\AuthServiceInterface;
use App\Services\Impl\AuthServiceImpl;
use App\Repositories\AuthRepositoryInterface;
use App\Repositories\Impl\AuthRepositoryImpl;

class ApiAuthServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind(AuthServiceInterface::class, AuthServiceImpl::class);
        $this->app->bind(AuthRepositoryInterface::class, AuthRepositoryImpl::class);
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
