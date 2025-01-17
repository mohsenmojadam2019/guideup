<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

use Adaojunior\Passport\SocialUserResolverInterface;
use App\Resolvers\SocialUserResolver;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(SocialUserResolverInterface::class, SocialUserResolver::class);
    }
}
