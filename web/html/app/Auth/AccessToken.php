<?php
namespace App\Auth;
use Laravel\Passport\Bridge\AccessToken as BaseToken;
use Lcobucci\JWT\Builder;
use Lcobucci\JWT\Signer\Key;
use Lcobucci\JWT\Signer\Rsa\Sha256;
use League\OAuth2\Server\CryptKey;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Entities\ScopeEntityInterface;
class AccessToken extends BaseToken
{
    /**
     * Generate a JWT from the access token
     *
     * @param CryptKey $privateKey
     *
     * @return string
     */
    public function convertToJWT(CryptKey $privateKey)
    {
        $builder = new Builder();
        $builder->setAudience($this->getClient()->getIdentifier())
            ->setId($this->getIdentifier(), true)
            ->setIssuedAt(time())
            ->setNotBefore(time())
            ->setExpiration($this->getExpiryDateTime()->getTimestamp())
            ->setSubject($this->getUserIdentifier())
            ->set('scopes', $this->getScopes());
        if ($user = \App\Models\User::find($this->getUserIdentifier())) {
            $builder
                ->set('uid', $user->uuid)
                ->set('parent_id', $user->parent_id)
                ->set('name', $user->display_name)
                ->set('email', $user->email)
                ->set('avatar', $user->avatar)
                ->set('guide', $user->guide_id)
                ->set('admin', $user->is_admin);
                // Basically anything the the jwt consumers should be able to access without hitting the server
        }
        return $builder
            ->sign(new Sha256(), new Key($privateKey->getKeyPath(), $privateKey->getPassPhrase()))
            ->getToken();
    }
}