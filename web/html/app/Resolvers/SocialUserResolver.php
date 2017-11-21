<?php

namespace App\Resolvers;

use Socialite;

use Adaojunior\Passport\SocialGrantException;
use Adaojunior\Passport\SocialUserResolverInterface;

use App\Repositories\UserRepository;

class SocialUserResolver implements SocialUserResolverInterface
{

    protected $users;

    public function __construct(UserRepository $userRepository) {
        $this->users = $userRepository;
    }
    
    /**
     * Resolves user by given network and access token.
     *
     * @param string $network
     * @param string $accessToken
     * @return \Illuminate\Contracts\Auth\Authenticatable
     */
    public function resolve($network, $accessToken, $accessTokenSecret = null)
    {
        switch ($network) {
            case 'facebook':
                return $this->authWithFacebook($accessToken);
                break;
            default:
                throw SocialGrantException::invalidNetwork();
                break;
        }
    }
    
    
    /**
     * Resolves user by facebook access token.
     *
     * @param string $accessToken
     * @return \App\User
     */
    protected function authWithFacebook($accessToken)
    {
        if($accessToken != null && trim($accessToken) != "") {
            $facebookUser = Socialite::driver('facebook')->userFromToken($accessToken);
            //Verifica se já existe um usuário com esse email
            $user = $this->users->findById($facebookUser->email, null, false);
            if(!$user || $user == null) {
                //Create new user
                $data = [];
                $data['name'] = $facebookUser->name;
                $data['email'] = $facebookUser->email;
                if($facebookUser->user != null && isset($facebookUser->user['gender'])) {
                    $data['gender'] = $facebookUser->user['gender'];
                }

                if($facebookUser->user != null && isset($facebookUser->user['birthday'])) {
                    $data['born'] = $facebookUser->user['birthday'];
                }

                if(isset($facebookUser->avatar_original) && $facebookUser->avatar_original != null) {
                    $data['file'] = $facebookUser->avatar_original;
                }
                $user = $this->users->create($data);
            }
        
            $time = time() + $user->expiresIn;
            $this->users->updateSocialLogin($user->id, ['social_id' => $facebookUser->id, 'token' => $accessToken, 'expiresin' => $time]);
            
            return $user;
        }
    }
}