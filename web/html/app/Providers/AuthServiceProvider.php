<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

use Laravel\Passport\Bridge\AccessTokenRepository;
use Laravel\Passport\Http\Controllers\AccessTokenController;
use Laravel\Passport\Passport;

use App\Models\User;
use App\Policies\UserPolicy;
use App\Models\Guide;
use App\Policies\GuidePolicy;
use App\Models\Gallery;
use App\Policies\GalleryPolicy;
use App\Models\Place;
use App\Policies\PlacePolicy;
use App\Models\Review;
use App\Policies\ReviewPolicy;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [
        User::class => UserPolicy::class,
        Guide::class => GuidePolicy::class,
        Gallery::class => GalleryPolicy::class,
        Place::class => PlacePolicy::class,
        Review::class => ReviewPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

		
		/*$this->app->bind(AccessTokenRepository::class, function ($app) {
            return $app->make(\App\Auth\AccessTokenRepository::class);
        });*/
	
        Passport::routes();
	
		$this->app->bind(AccessTokenController::class, \App\Http\Controllers\Api\AccessTokenController::class);

		
        //Passport::tokensExpireIn(Carbon::now()->addDays(15));
        //Passport::refreshTokensExpireIn(Carbon::now()->addDays(30));
    }
}
