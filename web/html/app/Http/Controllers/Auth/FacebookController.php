<?php
namespace App\Http\Controllers\Auth;
use App\Http\Controllers\Controller;
use Socialite;
use Auth;
use Illuminate\Http\Request;
use Response;
use User;
use Hash;

class FacebookController extends Controller
{

	public function redirectToProvider()
	{
		return Socialite::driver('facebook')->scopes([
            'email', 'public_profile'
        ])->redirect();
	}

	public function handleProviderCallback(Request $request)
	{
		$response = [];
		$state = $request->get('state');
    	$request->session()->put('state',$state);
		$user = Socialite::driver('facebook')->user();
		//var_dump($user);
		$time = time() + $user->expiresIn;
		$response['user'] = [
			'name' => $user->name,
			'email' => $user->email,
			'avatar' => $user->avatar,
			'gender' => $user->user['gender'],
			'expiresIn' => $time,
			'dateExpires' => $time
		];

			$response['access_token'] = $user->token;
        return Response::json($response, 200);
	}
}
