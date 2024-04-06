<?php
// This file contains the PHP-code to fill the Ampersand relations from SIAM.adl
namespace SIAM\Controller;

use Throwable;
use Ampersand\Core\Atom;
use Ampersand\Interfacing\ResourceList;
use Ampersand\Log\Logger;
use SIAM\Controller\AbstractController;
use SIAM\Exception\AccountDoesNotExistException;
use Ampersand\Exception\BadRequestException;
use SIAM\Exception\ServerErrorException;
use Slim\Http\Request;
use Slim\Http\Response;
use SIAM\ProfileData;

class LoginController extends AbstractController
{
    public function logout(Request $request, Response $response, array $args = []): Response
    {
        $this->app->logout();
        return $this->success($response);
    }

    /**
     * Login user by their userId
     * Return true on login, false otherwise
     *
     * @throws AccountDoesNotExistException when account does not exist
     * @throws ServerErrorException when multiple accounts are found for given userId
     */
    public function login(
        string $userId,
        ProfileData $profile,
        bool $linkOrganizationByExternalId = false,
        bool $createOrganizationIfNotExist = true
    ): bool {
        if (empty($userId)) {
            throw new BadRequestException("Empty user id provided to login");
        }

        $accounts = ResourceList::makeFromInterface($userId, 'AccountForUserid')->getResources();

        if (empty($accounts)) {
            throw new AccountDoesNotExistException("No account with user id '{$userId}'");
        } elseif (count($accounts) === 1) {
            $account = current($accounts);
        } else {
            // This SHOULD NOT happen
            throw new ServerErrorException("Multiple users registered with account identifier '{$userId}'");
        }

        // Link organization
        if (!is_null($profile->getOrganizationId()) && $linkOrganizationByExternalId) {
            $this->linkToOrganizationByExternalId($account, $profile, createIfNotExist: $createOrganizationIfNotExist);
        }

        // Set account subscriptions is provided
        $subscriptionIds = $profile->subscriptionIds;
        if (!is_null($subscriptionIds)) {
            // Clear any existing account subscriptions
            foreach ($account->getLinks('accountSubscription[Account*Identifier]') as $link) {
                $link->delete();
            }

            // Set new subscriptions
            foreach ($subscriptionIds as $subscriptionId) {
                $account->link($subscriptionId, 'accountSubscription[Account*Identifier]')->add();
            }
        }

        // Login account
        $transaction = $this->app->getCurrentTransaction();
        $this->app->login($account); // Automatically closes transaction

        if ($transaction->isCommitted()) {
            $this->app->userLog()->notice("Login successfull");
            return true;
        } else {
            throw new ServerErrorException("Failed login attempt for userId '{$userId}'. Transaction is not committed");
        }
    }

    public function createNewAccount(string $userId, ProfileData $profile): Atom
    {
        $account = $this->app->getModel()->getConceptByLabel('Account')->createNewAtom();
        $person = $this->app->getModel()->getConceptByLabel('Person')->createNewAtom(); // this will hold account profile information
        $account->link($person, 'accPerson[Account*Person]')->add();

        // Set account user id
        $account->link($userId, 'accUserid[Account*UserID]')->add();

        $email = $profile->getEmail();
        if (!is_null($email)) {
            $person->link($email, 'personEmailaddress[Person*Emailaddress]')->add();

            // Try to link to organization (domain) based on emailaddress
            $this->linkToOrganizationByDomain($account, $email);
        }

        // Set profile first name
        $firstName = $profile->firstName;
        if (!is_null($firstName)) {
            $person->link($firstName, 'personFirstName[Person*FirstName]')->add();
        }

        // Set profile last name
        $lastName = $profile->lastName;
        if (!is_null($lastName)) {
            $person->link($lastName, 'personLastName[Person*LastName]')->add();
        }

        // Set account display name
        $displayName = $profile->displayName;
        if (!is_null($displayName)) {
            $account->link($displayName, 'accDisplayName[Account*UserID]')->add();
        }

        return $account;
    }

    protected function linkToOrganizationByDomain(Atom $account, string $email): Atom
    {
        try {
            // If possible, add account to organization(s) based on domain name
            $domain = strtolower(explode('@', $email)[1]); // match with lower case domain names
            $orgs = ResourceList::makeFromInterface($domain, 'DomainOrgs')->getResources();
            foreach ($orgs as $org) {
                $account->link($org, 'accOrg[Account*Organization]')->add();
            }
        } catch (Throwable $e) {
            // Domain orgs not supported/working => skip
        }
        return $account;
    }

    protected function linkToOrganizationByExternalId(Atom $account, ProfileData $profile, bool $createIfNotExist = true): ?Atom
    {
        $organizationId = $profile->getOrganizationId();

        if (is_null($organizationId)) {
            return null;
        }

        $relOrgExternalIdentifier = $this->app->getModel()->getRelation('orgExternalIdentifier[Organization*Identifier]');

        $externalId = $relOrgExternalIdentifier->tgtConcept->makeAtom($organizationId);
        $organizations = array_map(
            fn (\Ampersand\Core\Link $link) => $link->src(),
            $relOrgExternalIdentifier->getAllLinks(null, $externalId)
        );

        if (empty($organizations)) {
            if ($createIfNotExist === false) {
                return null;
            }

            $organizationName = $profile->getOrganizationName();

            if (is_null($organizationName) || empty($organizationName)) {
                Logger::getLogger('APPLICATION')->warning("Cannot create new organization with empty name. External identifier: '{$organizationId}'");
                return null;
            }

            // Create new organization
            $newOrg = $this->app->getModel()->getConcept('Organization')->createNewAtom()->add();
            $newOrg->link($organizationName, 'orgFullName[Organization*OrgFullName]')->add();
            $newOrg->link($organizationName, 'orgAbbrName[Organization*OrgAbbrName]')->add();
            $newOrg->link($organizationId, 'orgExternalIdentifier[Organization*Identifier]')->add();
            $organizations[] = $newOrg;
        }

        if (count($organizations) > 1) {
            throw new ServerErrorException("Multiple organizations returned for external identifier '{$organizationId}'. This should not occur");
        }

        foreach ($organizations as $organization) {
            $account->link($organization, 'accOrg[Account*Organization]')->add();
        }

        return $organizations[0];
    }
}
