<?php

use Slim\Http\Request;
use Slim\Http\Response;
use Ampersand\Interfacing\Options;
use Ampersand\Interfacing\ResourceList;
use Lcobucci\JWT\Builder;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Signer\Key;

/** @var \Slim\App $api */
global $api;

$api->group('/ssif', function () {

    // API method to redirect the user with an 'credential-issue-request' to the SSI service
    $this->get('/credential-issue-request/{ifcId}/{token}', function (Request $request, Response $response, $args = []) {
        /** @var \Ampersand\AmpersandApp $ampersandApp */
        $ampersandApp = $this['ampersand_app'];
        
        // Input
        $ifcId = $args['ifcId']; // name of Ampersand interface that specifies credential data
        $credentialToken = $args['token']; // an access token
        $subjectId = $request->getQueryParam('subjectId'); // the subject (Ampersand atom identifier for which a credential must be issued)
        $credentialType = rawurldecode($request->getQueryParam('credentialType')); // URI of the credential type that needs to be issued
        $callbackUrl = rawurldecode($request->getQueryParam('callbackUrl', $request->getUri()->getHost()));

        // Prepare
        $resource = ResourceList::makeFromInterface($credentialToken, $ifcId)->one($subjectId);
        $jti = bin2hex(random_bytes(12));
        
        // JWT interface with SSI service
        // See: https://ci.tno.nl/gitlab/ssi-lab/developer-docs/-/blob/master/jwt-descriptions/jwt-credential-issue-request.md
        $jwt = (new Builder())
            ->identifiedBy($jti)
            ->issuedBy($request->getAttribute('ssiServiceOrgId')) // org id from group middleware
            ->permittedFor('ssi-service-provider')
            ->issuedAt(time())
            ->withClaim('sub', 'credential-issue-request')
            ->withClaim('type', $credentialType)
            ->withClaim('data', $resource->get(Options::INCLUDE_REF_IFCS))
            ->withClaim('callbackUrl', $callbackUrl);
        $signer = new Sha256();
        $secretKey = new Key($request->getAttribute('ssiServiceSharedSecret')); // secret from group middleware
        $token = $jwt->getToken($signer, $secretKey);
        
        // 301 Redirect with jwt token to ssi issuing service
        return $response->withRedirect("{$request->getAttribute('ssiServiceEndpoint')}/issue/{$token}"); // endpoint from group middleware
    });

    // API method to process 'credential-issue-response' from the SSI service
    $this->get('/credential-issue-response/{token}', function (Request $request, Response $response, $args = []) {
    });

    // API method to redirect the user with an 'credential-verify-request' to the SSI service
    $this->get('/credential-verify-request/{formId}', function (Request $request, Response $response, $args = []) {
        
        // Input
        $formId = $args['formId'];
        $credentialType = rawurldecode($request->getQueryParam('credentialType')); // URI of the credential type that is requested

        // Prepare
        $jti = bin2hex(random_bytes(12));
        $callbackUrl = $request->getUri()->getHost() . "/api/vi/ssif/credential-verify-response/{$formId}";

        // JWT interface with SSI service
        // See: https://ci.tno.nl/gitlab/ssi-lab/developer-docs/-/blob/master/jwt-descriptions/jwt-credential-verify-request.md
        $jwt = (new Builder())
            ->identifiedBy($jti)
            ->issuedBy($request->getAttribute('ssiServiceOrgId')) // org id from group middleware
            ->permittedFor('ssi-service-provider')
            ->issuedAt(time())
            ->withClaim('sub', 'credential-verify-request')
            ->withClaim('type', $credentialType)
            ->withClaim('callbackUrl', $callbackUrl);
        $signer = new Sha256();
        $secretKey = new Key($request->getAttribute('ssiServiceSharedSecret')); // secret from group middleware
        $token = $jwt->getToken($signer, $secretKey);
        
        // 301 Redirect with jwt token to ssi issuing service
        return $response->withRedirect("{$request->getAttribute('ssiServiceEndpoint')}/verify/{$token}"); // endpoint from group middleware
    });

    // API method to process 'credential-verify-response' from the SSI service
    $this->get('/credential-verify-response/{formId}/{token}', function (Request $request, Response $response, $args = []) {
    });

    /**
     * API used for testing/debugging with downloading/uploading json files containing the attestation/credential info)
     */
    $this->get('/credential-data/{attid}/{interfaceId}/{subjectId}', function (Request $request, Response $response, $args = []) {
        /** @var \Ampersand\AmpersandApp $ampersandApp */
        $ampersandApp = $this['ampersand_app'];
        
        // Prepare
        $options = Options::INCLUDE_REF_IFCS;
        $resource = ResourceList::makeFromInterface($args['attid'], $args['interfaceId'])->one($args['subjectId']);
    
        $credentialData = $resource->get($options, $depth = null);
        
        // Content
        $response = $response->withJson($credentialData, 200, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        // Download
        if ($request->getQueryParam('download')) {
            $filename = "Credential-{$args['subjectId']}.json";
            $response = $response->withHeader('Content-Disposition', "attachment; filename={$filename}");
        }

        return $response;
    });

    /**
     * DEPRECATED
     * API used for testing/debugging with downloading/uploading json files containing the attestation/credential info)
     */
    $this->post('/attestations/{formType}/{formId}/{interfaceId}', function (Request $request, Response $response, $args = []) {
        /** @var \Ampersand\AmpersandApp $ampersandApp */
        $ampersandApp = $this['ampersand_app'];
        /** @var \Ampersand\AngularApp $angularApp */
        $angularApp = $this['angular_app'];
        
        $form = ResourceList::makeFromInterface($args['formId'], $args['interfaceId'])->one($args['formId']);
        
        $transaction = $ampersandApp->newTransaction();
        
        if (is_uploaded_file($_FILES['file']['tmp_name'])) {
            $content = file_get_contents($_FILES['file']['tmp_name']);
        } else {
            $content = $request->getBody()->getContents();
        }

        $data = json_decode($content, false);
        
        // Check if payload is specified
        if (!isset($data->payload)) {
            throw new Exception("Payload of attestation not provided", 400);
        }
        
        $form->put($data->payload);
        // TODO: also put metadata of attestation
        
        $transaction->runExecEngine()->close();

        $respContent = [ 'content'               => $data
                       , 'notifications'         => $ampersandApp->userLog()->getAll()
                       , 'invariantRulesHold'    => $transaction->invariantRulesHold()
                       , 'isCommitted'           => $transaction->isCommitted()
                       , 'sessionRefreshAdvice'  => $angularApp->getSessionRefreshAdvice()
                       , 'navTo'                 => $angularApp->getNavToResponse($transaction->isCommitted() ? 'COMMIT' : 'ROLLBACK')
                       ];
        
        return $response->withJson($respContent, 200, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    });
})->add(function (Request $request, Response $response, callable $next) {
    /** @var \Ampersand\AmpersandApp $ampersandApp */
    
    // SSI service endpoint
    $ssiServiceEndpoint = getenv('SSI_SERVICE_ENDPOINT');
    if ($ssiServiceEndpoint === false) {
        throw new Exception("SSI service endpoint not configured", 500);
    }
    $request = $request->withAttribute('ssiServiceEndpoint', $ssiServiceEndpoint);

    // SSI service shared secret
    $ssiServiceSharedSecret = getenv('SSI_SERVICE_SHARED_SECRET');
    if ($ssiServiceSharedSecret === false) {
        throw new Exception("SSI service shared secret not configured", 500);
    }
    $request = $request->withAttribute('ssiServiceSharedSecret', $ssiServiceSharedSecret);

    // SSI service my organization id
    $ssiServiceOrgId = getenv('SSI_SERVICE_MY_ORG_ID');
    if ($ssiServiceOrgId === false) {
        throw new Exception("SSI service organization id not configured", 500);
    }
    $request = $request->withAttribute('ssiServiceOrgId', $ssiServiceOrgId);

    return $next($request, $response);
});
