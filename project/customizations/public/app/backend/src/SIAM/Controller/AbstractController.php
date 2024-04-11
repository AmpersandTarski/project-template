<?php

namespace SIAM\Controller;

use InvalidArgumentException;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Signer\Key\InMemory;
use Psr\Container\ContainerInterface;
use Ampersand\Exception\AccessDeniedException;
use Ampersand\Exception\BadRequestException;
use Slim\Http\Request;

use function SIAM\Utils\getHashSecret;
use function SIAM\Utils\paramToBool;

/**
 * AbstractController class is the basis of all STH concrete controller classes.
 *
 * It defines some properties and methods that can be used by all controllers,
 * e.g. a reference to a JWT configuration or method to check for the required user role.
 *
 * This STH AbstractController extends the Ampersand AbstractController which
 * allows for even more reusable properties and methods, including a reference
 * to the \Ampersand\AmpersandApp object.
 */
abstract class AbstractController extends \Ampersand\Controller\AbstractController
{
    protected $jwtConf;

    public function __construct(ContainerInterface $container)
    {
        parent::__construct($container);

        $this->jwtConf = Configuration::forSymmetricSigner(
            new Sha256(),
            InMemory::plainText(getHashSecret())
        );
    }

    protected function requireRoles(string ...$roles): void
    {
        // Access control check
        if (!$this->app->hasRole($roles)) {
            throw new AccessDeniedException("Access denied. Requires role(s): " . implode(", ", $roles));
        }
    }

    /**
     * Returns true for "1", "true", "on" and "yes"
     * Returns false for "0", "false", "off", "no", and ""
     * @throws BadRequestException for unsupported value
     */
    protected function paramToBool(Request $request, string $paramName, bool $default): bool
    {
        try {
            return paramToBool($request->getParam($paramName, $default));
        } catch (InvalidArgumentException $e) {
            $allowedValues = ["1", "true", "on", "yes", "0", "false", "off", "no"];
            throw new BadRequestException(
                message: "Unsupported value for parameter '{$paramName}'. Supported are: " . implode(",", $allowedValues),
                previous: $e,
            );
        }
    }
}
