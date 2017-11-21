<?php
/**
 * Laravel Passport - Customize Token response.
 */
namespace App\Http\Controllers\Auth;

use App\Repositories\UserRepository;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use League\OAuth2\Server\Exception\OAuthServerException;
use Psr\Http\Message\ServerRequestInterface;
use Laravel\Passport\Http\Controllers\AccessTokenController as BaseController;
use Response;
use Socialite;

use GuzzleHttp\Exception\ClientException;
use League\OAuth2\Server\AuthorizationServer;
use Laravel\Passport\TokenRepository;
use Lcobucci\JWT\Parser as JwtParser;

class AccessTokenController extends BaseController
{
    protected $users;
	
    public function __construct(UserRepository $userRepository,
								AuthorizationServer $server,
                                TokenRepository $tokens,
                                JwtParser $jwt) {
		parent::__construct($server, $tokens, $jwt);
		
        $this->users = $userRepository;
    }
	
    public function issueToken(ServerRequestInterface $request)
    {
        try {
			if($request->getParsedBody()['grant_type'] == 'social' && $request->getParsedBody()['network'] == 'facebook') { 
				$token = $request->getParsedBody()['access_token'];
				$facebook_user = Socialite::driver('facebook')->userFromToken($token);
				$username = $facebook_user->email;
			}
			else if($request->getParsedBody()['grant_type'] == 'password') {
				$username = $request->getParsedBody()['username'];
			}
			
            //get user
            $user = $this->users->findById($username);

            //issuetoken
            $tokenResponse = parent::issueToken($request);

            //convert response to json string
            $content = $tokenResponse->getContent();//->getBody()->__toString();
	    //dd($content);
            //convert json to array
            $data = json_decode($content, true);

            if(isset($data["error"]))
                throw new OAuthServerException('The user credentials were incorrect.', 6, 'invalid_credentials', 401);

            //add access token to user
            return Response::json([
				'user' => collect($user),
				'access_token' => $data['access_token'],
				'refresh_token' => $data['refresh_token'],
				'expires_in' => $data['expires_in'],
				'token_type' => $data['token_type']
			]);
        }
        catch (ModelNotFoundException $e) { // email notfound
            //return error message
        } catch (ClientException $exception) {
            $error = json_decode($exception->getResponse()->getBody());

            throw OAuthServerException::invalidRequest('access_token', object_get($error, 'error.message'));
        }
    }
}
