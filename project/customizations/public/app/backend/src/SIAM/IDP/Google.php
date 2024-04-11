<?php

namespace SIAM\IDP;

use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Provider\GoogleUser;
use League\OAuth2\Client\Provider\ResourceOwnerInterface;
use SIAM\Exception\ServerErrorException;
use SIAM\ProfileData;
use Ampersand\Exception\BadRequestException;

class Google extends AbstractIDP
{
    public function getOAuthProvider(): AbstractProvider
    {
        return new \League\OAuth2\Client\Provider\Google($this->getProviderOptions());
    }

    public function getProfileData(ResourceOwnerInterface $resource): ProfileData
    {
        if (!($resource instanceof GoogleUser)) {
            throw new ServerErrorException("Invalid resource owner interface for Google OAuth provider");
        }

        $profile = new ProfileData(
            $resource->getEmail()
                ?? $resource->getId()
                ?? throw new BadRequestException("Null value provided for resource id")
        );
        $profile->firstName = $resource->getFirstName();
        $profile->lastName = $resource->getLastName();
        $profile->displayName = $resource->getEmail();
        $profile->setEmail($resource->getEmail());

        return $profile;
    }
}
