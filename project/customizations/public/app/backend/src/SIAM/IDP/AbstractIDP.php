<?php

namespace SIAM\IDP;

use Ampersand\AmpersandApp;
use League\OAuth2\Client\Provider\AbstractProvider;
use Ampersand\Exception\BadRequestException;
use SIAM\IdentityProviderFactory;
use Slim\Http\Request;

use function Ampersand\Misc\makeValidURL;

abstract class AbstractIDP implements IDPInterface
{
    public string $id;
    public string $name;
    public string $logoUrl;
    public array $scopes;
    public string $serverUrl;
    public array $authorizationOptions = [];
    protected bool $createAccountIfNotExist;
    protected bool $linkOrganizationByExternalId;
    protected bool $createOrganizationIfNotExist;
    protected array $providerConfig = [];

    public function __construct(string $id, array $providerConfig, AmpersandApp $app)
    {
        $this->id = $id;
        $this->name = $providerConfig['name'];
        $this->logoUrl = $providerConfig['logoUrl'];
        $this->scopes = $providerConfig['scopes'];
        $this->serverUrl = $app->getSettings()->get('global.serverURL');

        // Additional configs
        $this->createAccountIfNotExist = $providerConfig['createAccountIfNotExist'] ?? true;
        $this->linkOrganizationByExternalId = $providerConfig['linkOrganizationByExternalId'] ?? false;
        $this->createOrganizationIfNotExist = $providerConfig['createOrganizationIfNotExist'] ?? true;

        $this->authorizationOptions += [
            'scope' => $this->scopes,
            'state' => IdentityProviderFactory::getStateToken($app)
        ];

        $this->providerConfig = $providerConfig;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getLogoUrl(): string
    {
        return $this->logoUrl;
    }

    public function getAuthorizationOptions(): array
    {
        return $this->authorizationOptions;
    }

    protected function getProviderOptions(): array
    {
        $providerConfig = $this->providerConfig;

        return [
            'clientId'                => $providerConfig['clientId'],
            'clientSecret'            => $providerConfig['clientSecret'],
            'redirectUri'             => makeValidUrl("api/v1/oauthlogin/callback/{$this->id}", $this->serverUrl),
            'urlAuthorize'            => $providerConfig['authBase'],
            'urlAccessToken'          => $providerConfig['tokenUrl'],
            'urlResourceOwnerDetails' => $providerConfig['apiUrl'],
        ];
    }

    public function getOAuthProvider(): AbstractProvider
    {
        return new \League\OAuth2\Client\Provider\GenericProvider($this->getProviderOptions());
    }

    public function handleCallbackError(Request $request): void
    {
        // See: https://www.oauth.com/oauth2-servers/authorization/the-authorization-response/
        // First check if authorization response has errors
        $error = $request->getQueryParam('error', null);
        if (!is_null($error)) {
            $errorDescription = urldecode($request->getQueryParam('error_description', ''));
            $errorDescription = str_replace('+', ' ', $errorDescription); // certain IDPs use a '+' to concatenate words in the error description

            throw new BadRequestException("{$error} - {$errorDescription}");
        }
    }

    public function createAccountIfNotExist(): bool
    {
        return $this->createAccountIfNotExist;
    }

    public function linkOrganizationByExternalId(): bool
    {
        return $this->linkOrganizationByExternalId;
    }

    public function createOrganizationIfNotExist(): bool
    {
        return $this->createOrganizationIfNotExist;
    }
}
