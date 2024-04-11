<?php

namespace SIAM;

use Ampersand\Log\Logger;
use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Provider\ResourceOwnerInterface;
use SIAM\Controller\AbstractController;
use SIAM\Controller\LoginController;
use SIAM\Exception\AccountDoesNotExistException;
use Ampersand\Exception\BadRequestException;
use SIAM\Exception\ServerErrorException;
use SIAM\Exception\InvalidStateException;
use SIAM\IDP\IDPInterface;
use Slim\Http\Request;
use Slim\Http\Response;

use function Ampersand\Misc\makeValidURL;

// The AOuthController connects API-calls to the executable code.
class OAuthController extends AbstractController
{
    public function getIdentityProviders(Request $request, Response $response, array $args): Response
    {
        // Prepare list with identity providers for the UI
        $idps = array_map(function (IDPInterface $idp) {
            return [
                'name' => $idp->getName(), 'loginUrl' => "api/v1/OAuthLogin/login/{$idp->getId()}", 'logo' => $idp->getLogoUrl()
            ];
        }, IdentityProviderFactory::getProviders($this->app));

        return $response->withJson(
            ['identityProviders' => $idps, 'notifications' => $this->app->userLog()->getAll()],
            200,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
        );
    }

    public function redirectToIdp(Request $request, Response $response, array $args = []): Response
    {
        $idp = IdentityProviderFactory::getProvider($this->app, $args['idp']);

        $url = $idp->getOAuthProvider()->getAuthorizationUrl($idp->getAuthorizationOptions());

        return $response->withRedirect($url);
    }

    public function handleCallback(Request $request, Response $response, $args = []): Response
    {
        $identityProvider = IdentityProviderFactory::getProvider($this->app, $args['idp']);

        $identityProvider->handleCallbackError($request);

        $resource = $this->authenticate(
            $request->getQueryParam('code'),
            $request->getQueryParam('state'),
            $identityProvider->getOAuthProvider()
        );

        // We log credentials received to login with level NOTICE
        // This allows to use the logs to diagnose issues when users indicate they can't login.
        Logger::getLogger('APPLICATION')->notice(
            "Credentials received from OAuth provider '{$identityProvider->getName()}'",
            $resource->toArray()
        );

        $isLoggedIn = $this->login($resource, $identityProvider);

        if ($isLoggedIn === false) {
            throw new ServerErrorException("Login failed for resource '{$resource->getId()}' of IDP '{$identityProvider->getName()}'");
        }

        // Prepend serverUrl if redirect url is specified as relative path
        $settings = $this->app->getSettings();
        $url = makeValidURL(
            $settings->get('oauthlogin.redirectAfterLogin'),
            $settings->get('global.serverURL')
        );

        return $response->withRedirect($url);
    }

    /**
     * Process authentication request
     */
    protected function authenticate(string $code, string $state, AbstractProvider $provider): ResourceOwnerInterface
    {
        if (empty($code)) {
            throw new BadRequestException("No authentication code provided. Please try again");
        }

        if ($state !== IdentityProviderFactory::getStateToken($this->app)) {
            throw new InvalidStateException("Invalid state parameter");
        }

        try {
            // Try to get an access token using the authorization code grant.
            $accessToken = $provider->getAccessToken('authorization_code', [
                'code' => $code
            ]);

            // We have an access token, which we may use in authenticated requests against the service provider's API:
            // Access Token:    $accessToken->getToken()
            // Refresh Token:   $accessToken->getRefreshToken()
            // Expired in:      $accessToken->getExpires()
            // Expired?         $accessToken->hasExpired()

            // Using the access token, we may look up details about the resource
            $resourceOwner = $provider->getResourceOwner($accessToken); // @phan-suppress-current-line PhanTypeMismatchArgument, PhanTypeMismatchArgumentSuperType
        } catch (\League\OAuth2\Client\Provider\Exception\IdentityProviderException $e) {
            // Failed to get the access token or user details.
            throw new BadRequestException('Failed to get the access token or user details: ' . $e->getMessage(), previous: $e);
        }

        return $resourceOwner;
    }

    protected function login(ResourceOwnerInterface $resource, IDPInterface $idp): bool
    {
        $profile = $idp->getProfileData($resource);
        $userId = $profile->getUserId();

        $loginController = new LoginController($this->container);

        try {
            return $loginController->login(
                $userId,
                $profile,
                linkOrganizationByExternalId: $idp->linkOrganizationByExternalId(),
                createOrganizationIfNotExist: $idp->createOrganizationIfNotExist()
            );
        } catch (AccountDoesNotExistException $e) {
            // Rethrow exception when it is not allowed to create accounts with this identity provider
            if (!$idp->createAccountIfNotExist()) {
                throw $e;
            }

            // Create new account
            $loginController->createNewAccount($userId, $profile);

            // Retry login for newly created account
            return $loginController->login(
                $userId,
                $profile,
                linkOrganizationByExternalId: $idp->linkOrganizationByExternalId(),
                createOrganizationIfNotExist: $idp->createOrganizationIfNotExist()
            );
        }
    }
}
