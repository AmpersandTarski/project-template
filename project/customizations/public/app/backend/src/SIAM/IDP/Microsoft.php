<?php

namespace SIAM\IDP;

use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Provider\ResourceOwnerInterface;
use SIAM\ProfileData;
use Ampersand\AmpersandApp;
use Ampersand\Exception\BadRequestException;
use SIAM\Exception\CancellationException;
use Slim\Http\Request;

class Microsoft extends AbstractIDP
{
    public function __construct(string $id, array $providerConfig, AmpersandApp $app)
    {
        parent::__construct($id, $providerConfig, $app);

        // Set additional authorization options
        // See: https://docs.microsoft.com/en-us/azure/active-directory/develop/v2-oauth2-auth-code-flow
        $this->authorizationOptions += [
            'prompt' => 'select_account'
        ];
    }

    public function getOAuthProvider(): AbstractProvider
    {
        // The third party OAuth provider for Microsoft of StevenMaguire v2.2.0 (https://github.com/stevenmaguire/oauth2-microsoft) doesn't work
        // It gives error 'Access token is empty'. Release 2.2.0 is of Jun 2017, so it doesn't look like this lib is maintained.
        // Therefore we use the GenericProvider
        return new \League\OAuth2\Client\Provider\GenericProvider($this->getProviderOptions());
    }

    public function getProfileData(ResourceOwnerInterface $resource): ProfileData
    {
        /** @var \League\OAuth2\Client\Provider\GenericResourceOwner $resource */
        $data = $resource->toArray();

        $profile = new ProfileData(
            $data['mail']
                ?? $resource->getId()
                ?? throw new BadRequestException("Null value provided for resource id")
        );
        $profile->firstName = $data['givenName'];
        $profile->lastName = $data['surname'];
        $profile->displayName = $data['displayName'] ?? $data['mail'];
        $profile->setEmail($data['mail']);

        return $profile;
    }

    public function handleCallbackError(Request $request): void
    {
        // See: https://learn.microsoft.com/en-us/azure/active-directory/develop/v2-oauth2-auth-code-flow
        $error = $request->getQueryParam('error', null);
        $subcode = $request->getQueryParam('error_subcode', null);
        if (!is_null($error)) {
            // Handle special case where user cancels the login flow
            if ($error === 'access_denied' && $subcode === 'cancel') {
                throw new CancellationException($request->getQueryParam('error_description', "User cancelled authentication flow"));
            }
        }

        // Continue with generic error handler
        parent::handleCallbackError($request);
    }
}
