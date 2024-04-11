<?php
// This file connects endpoints to the OAuthController and LoginController classes
/** @phan-file-suppress PhanInvalidFQSENInCallable */

use Ampersand\Exception\BadRequestException;
use Ampersand\Log\Logger;
use SIAM\Controller\LoginController;
use SIAM\Exception\AccountDoesNotExistException;
use Ampersand\Exception\InvalidConfigurationException;
use SIAM\Exception\CancellationException;
use SIAM\Exception\InvalidStateException;
use SIAM\OAuthController;
use Slim\Http\Request;
use Slim\Http\Response;

use function Ampersand\Misc\makeValidURL;

/** @var \Slim\App $api */
global $api;

/**
 * @phan-closure-scope \Slim\App
 */
$api->group('/oauthlogin', function () {
    $this->get('/login', OAuthController::class . ':getIdentityProviders');
    $this->get('/login/{idp}', OAuthController::class . ':redirectToIdp');
    $this->get('/logout', LoginController::class . ':logout');
    $this->get('/callback/{idp}', OAuthController::class . ':handleCallback');
})
    /**
     * @phan-closure-scope \Slim\Container
     */
    ->add(function (Request $request, Response $response, callable $next) {
        /** @var \Ampersand\AmpersandApp $ampersandApp */
        $ampersandApp = $this['ampersand_app'];
        $settings = $ampersandApp->getSettings();

        if (!$settings->get('oauthlogin.enabled', false)) {
            throw new InvalidConfigurationException("The OAuth login module is not enabled");
        }

        // Handle exceptions
        try {
            return $next($request, $response);
        } catch (InvalidStateException $e) {
            Logger::getLogger('API')->notice($e->getMessage());

            $reason = urlencode("Your session had expired. Please try again");
            $url = makeValidURL(
                $settings->get('oauthlogin.redirectAfterLoginFailure') . $reason,
                $settings->get('global.serverURL')
            );
            return $response->withRedirect($url);
        } catch (CancellationException $e) {
            Logger::getLogger('API')->notice($e->getMessage());

            // Just redirect back to the application. No error page is needed
            return $response->withRedirect($settings->get('global.serverURL'));
        } catch (BadRequestException $e) {
            Logger::getLogger('API')->error($e->getMessage());

            $url = makeValidURL(
                $settings->get('oauthlogin.redirectAfterLoginFailure') . urlencode("Something went wrong while logging in: {$e->getMessage()}"),
                $settings->get('global.serverURL')
            );
            return $response->withRedirect($url);
        } catch (AccountDoesNotExistException $e) {
            Logger::getLogger('API')->notice($e->getMessage());

            $url = makeValidURL(
                $settings->get('oauthlogin.redirectAfterLoginFailure') . urlencode("Account does not exist"),
                $settings->get('global.serverURL')
            );
        } catch (Throwable $e) {
            Logger::getLogger('API')->error($e->getMessage());

            $reason = urlencode("Something went wrong while logging in. Please try again or contact the application administrator for more information.");
            $url = makeValidURL(
                $settings->get('oauthlogin.redirectAfterLoginFailure') . $reason,
                $settings->get('global.serverURL')
            );
            return $response->withRedirect($url);
        }
    });
