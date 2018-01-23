<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/
Route::post('user/facebook', 'Api\UserController@getOrCreateUser');
Route::get('user/{user_id}/place/{place_id}', 'Api\UserController@placeShow');
Route::get('user/{user_id}/place', 'Api\UserController@placeIndex');
Route::get('user/{user_id}/review/{review_id}', 'Api\UserController@reviewShow');
Route::get('user/{user_id}/review', 'Api\UserController@reviewIndex');
Route::get('user/{user_id}/guide', 'Api\UserController@guideIndex');
Route::get('user/{id}/{email}/exists', 'Api\UserController@exists');
Route::put('user/{id}/token', 'Api\UserController@token');
Route::resource('user', 'Api\UserController', ['except' => ['edit', 'create']]);

Route::get('guide/{guide_id}/place/{place_id}', 'Api\GuideController@placeShow');
Route::get('guide/{guide_id}/place', 'Api\GuideController@placeIndex');
Route::get('guide/{guide_id}/gallery/{gallery_id}', 'Api\GuideController@galleryShow');
Route::get('guide/{guide_id}/gallery', 'Api\GuideController@galleryIndex');
Route::get('guide/{guide_id}/review/{review_id}', 'Api\GuideController@reviewShow');
Route::get('guide/{guide_id}/review', 'Api\GuideController@reviewIndex');
Route::resource('guide', 'Api\GuideController', ['except' => ['edit', 'create']]);

Route::get('place/{place_id}/guide/{guide_id}', 'Api\PlaceController@guideShow');
Route::get('place/{place_id}/guide', 'Api\PlaceController@guideIndex');
Route::get('place/{place_id}/gallery/{gallery_id}', 'Api\PlaceController@galleryShow');
Route::get('place/{place_id}/gallery', 'Api\PlaceController@galleryIndex');
Route::get('place/{place_id}/review/{review_id}', 'Api\PlaceController@reviewShow');
Route::get('place/{place_id}/review', 'Api\PlaceController@reviewIndex');
Route::resource('place', 'Api\PlaceController', ['except' => ['edit', 'create']]);

Route::get('gallery/generatethumbnails', 'Api\GalleryController@generateThumbnails');
Route::resource('gallery', 'Api\GalleryController', ['except' => ['edit', 'create']]);

Route::post('review/{id}/{type}/exists', 'Api\ReviewController@hasReview');
Route::resource('review', 'Api\ReviewController',['except' => ['edit', 'create']]);

Route::resource('feedback', 'Api\FeedbackController',['except' => ['edit', 'create', 'destroy']]);

Route::resource('category', 'Api\CategoryController',['except' => ['edit', 'create', 'destroy']]);

#Route::post('chat/message', 'Api\ChatController@delete');
#Route::post('/offlinechat', 'Api\ChatController@received');

Route::post('user/forgot', 'Auth\ForgotPasswordController@sendResetLinkEmail');
Route::post('user/reset', 'Auth\ResetPasswordController@reset');

