<?php

namespace SIAM\IDP;

use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Provider\ResourceOwnerInterface;
use SIAM\ProfileData;
use Ampersand\Exception\BadRequestException;

class OpenID extends AbstractIDP
{
    public function getOAuthProvider(): AbstractProvider
    {
        $options = $this->getProviderOptions();

        // See: https://openid.net/specs/openid-connect-basic-1_0.html
        $options += [
            'responseResourceOwnerId' => 'sub', // the identifier is in the 'sub' field
            'scopeSeparator' => ' ' // scopes are delimited by spaces
        ];

        return new \League\OAuth2\Client\Provider\GenericProvider($options);
    }

    public function getProfileData(ResourceOwnerInterface $resource): ProfileData
    {
        /** @var \League\OAuth2\Client\Provider\GenericResourceOwner $resource */
        $data = $resource->toArray();

        // See: https://openid.net/specs/openid-connect-basic-1_0.html#StandardClaims
        $profile = new ProfileData(
            $data['email']
                ?? $resource->getId()
                ?? throw new BadRequestException("Null value provided for resource id")
        );
        $profile->firstName = $data['given_name'];
        $profile->lastName = implode(' ', array_filter([
            $data['middle_name'] ?? null,
            $data['family_name']
        ])); // combine middle and last names
        $profile->displayName = $data['preferred_username'] ?? $data['email'];
        $profile->setEmail($data['email']);

        return $profile;
    }
}
