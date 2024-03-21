<?php

namespace SIAM\IDP;

use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Provider\LinkedInResourceOwner;
use League\OAuth2\Client\Provider\ResourceOwnerInterface;
use SIAM\Exception\ServerErrorException;
use SIAM\ProfileData;
use Ampersand\Exception\BadRequestException;
use SIAM\Exception\CancellationException;
use Slim\Http\Request;

class LinkedIn extends AbstractIDP
{
    public function getOAuthProvider(): AbstractProvider
    {
        return new \League\OAuth2\Client\Provider\LinkedIn($this->getProviderOptions());
    }

    public function getProfileData(ResourceOwnerInterface $resource): ProfileData
    {
        if (!($resource instanceof LinkedInResourceOwner)) {
            throw new ServerErrorException("Invalid resource owner interface for LinkedIn OAuth provider");
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

    public function handleCallbackError(Request $request): void
    {
        // See: https://learn.microsoft.com/en-us/linkedin/shared/authentication/authorization-code-flow?tabs=HTTPS1#failed-requests
        $error = $request->getQueryParam('error', null);
        if (!is_null($error)) {
            // Handle special case where user cancels the login flow
            if ($error === 'user_cancelled_login') {
                throw new CancellationException($request->getQueryParam('error_description', "User cancelled authentication flow"));
            }
        }

        // Continue with generic error handler
        parent::handleCallbackError($request);
    }
}
