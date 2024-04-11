<?php

namespace SIAM\IDP;

use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Provider\GithubResourceOwner;
use League\OAuth2\Client\Provider\ResourceOwnerInterface;
use Ampersand\Exception\BadRequestException;
use SIAM\Exception\ServerErrorException;
use SIAM\ProfileData;

class Github extends AbstractIDP
{
    public function getOAuthProvider(): AbstractProvider
    {
        return new \League\OAuth2\Client\Provider\Github($this->getProviderOptions());
    }

    public function getProfileData(ResourceOwnerInterface $resource): ProfileData
    {
        if (!($resource instanceof GithubResourceOwner)) {
            throw new ServerErrorException("Invalid resource owner interface for Github OAuth provider");
        }

        $profile = new ProfileData(
            $resource->getEmail()
                ?? $resource->getId()
                ?? throw new BadRequestException("Null value provided for resource id")
        );
        $profile->lastName = $resource->getName(); // there is only one name field in the Github API
        $profile->displayName = $resource->getNickname();
        $profile->setEmail($resource->getEmail());

        return $profile;
    }
}
