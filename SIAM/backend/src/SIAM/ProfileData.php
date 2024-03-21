<?php

namespace SIAM;

use Ampersand\Exception\BadRequestException;

class ProfileData
{
    protected string $userId;
    public ?string $firstName = null;
    public ?string $lastName = null;
    public ?string $displayName = null;
    protected ?string $email = null;
    public ?array $subscriptionIds = null;
    public ?string $organizationId = null;
    public ?string $organizationName = null;

    public function __construct(string $userId)
    {
        $this->userId = $userId;
    }

    public function getUserId(): string
    {
        return $this->userId;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string | null $email): self
    {
        if (is_null($email)) {
            $this->email = null;
            return $this;
        }

        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            throw new BadRequestException("Invalid emailadress format provided: '{$email}'");
        }

        $this->email = $email;

        return $this;
    }

    public function getOrganizationId(): ?string
    {
        return $this->organizationId;
    }

    public function getOrganizationName(): ?string
    {
        return $this->organizationName;
    }
}
