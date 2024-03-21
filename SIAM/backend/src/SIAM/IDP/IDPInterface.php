<?php

namespace SIAM\IDP;

use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Provider\ResourceOwnerInterface;
use SIAM\ProfileData;
use Slim\Http\Request;

interface IDPInterface
{
    public function getId(): string;
    public function getName(): string;
    public function getLogoUrl(): string;
    public function getOAuthProvider(): AbstractProvider;
    public function getAuthorizationOptions(): array;
    public function getProfileData(ResourceOwnerInterface $resource): ProfileData;
    public function handleCallbackError(Request $request): void;

    public function createAccountIfNotExist(): bool;
    public function linkOrganizationByExternalId(): bool;
    public function createOrganizationIfNotExist(): bool;
}
