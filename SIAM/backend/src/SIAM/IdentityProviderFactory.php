<?php

namespace SIAM;

use Ampersand\AmpersandApp;
use Ampersand\Exception\BadRequestException;
use Ampersand\Exception\InvalidConfigurationException;
use SIAM\Exception\ServerErrorException;
use SIAM\IDP\IDPInterface;

use function SIAM\Utils\getHashSecret;

class IdentityProviderFactory
{
    public static function getProvider(AmpersandApp $app, string $id): IDPInterface
    {
        $identityProviders = self::getOAuthConfig($app);

        if (!isset($identityProviders[$id])) {
            throw new BadRequestException("Unsupported identity provider");
        }

        return self::makeProvider($id, $identityProviders[$id], $app);
    }

    public static function getProviders(AmpersandApp $app): array
    {
        $providers = self::getOAuthConfig($app);
        return array_map(function ($id, $providerConfig) use ($app) {
            return self::makeProvider($id, $providerConfig, $app);
        }, array_keys($providers), $providers);
    }

    protected static function makeProvider(string $id, array $providerConfig, AmpersandApp $app): IDPInterface
    {
        if (!isset($providerConfig['class'])) {
            throw new InvalidConfigurationException("No OAuth identity provider class specified for IDP '{$id}'");
        }

        $class = $providerConfig['class'];
        if (!class_exists($class)) {
            throw new InvalidConfigurationException("OAuth identity provider class '{$class}' specified but does not exist for IDP '{$id}'");
        }

        $idp = new $class($id, $providerConfig, $app);

        return $idp;
    }

    /**
     * Returns a keyed hash value of the session id that can be used as state token in OAuth request
     * The state is used to prevent CSRF (cross-site request forgery)
     */
    public static function getStateToken(AmpersandApp $app): string
    {
        $token = hash_hmac(
            'sha256',
            $app->getSession()->getId(),
            getHashSecret()
        );
        if ($token === false) {
            throw new ServerErrorException("Cannot create state token for OAuth login request");
        }
        return $token;
    }

    /**
     * Undocumented function
     *
     * @param AmpersandApp $app
     * @return array<string, array>
     */
    protected static function getOAuthConfig(AmpersandApp $app): array
    {
        $identityProviders = $app->getSettings()->get('oauthlogin.identityProviders');

        if (is_null($identityProviders)) {
            throw new InvalidConfigurationException("No identity providers specified for OAuthLogin extension");
        }

        if (!is_array($identityProviders)) {
            throw new InvalidConfigurationException("Identity providers must be specified as array");
        }

        return $identityProviders;
    }
}
