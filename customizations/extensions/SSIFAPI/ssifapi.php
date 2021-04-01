<?php

use Slim\Http\Request;
use Slim\Http\Response;
use Ampersand\Interfacing\Options;
use Ampersand\Interfacing\Resource;
use Ampersand\Interfacing\ResourceList;

global $api;

$api->group('/ssif', function () {
    $this->get('/attestations/attid/{attid}/{attestationType}/{attestationObjectId}', function (Request $request, Response $response, $args = []) {
        /** @var \Ampersand\AmpersandApp $ampersandApp */
        $ampersandApp = $this['ampersand_app'];
        
        // Prepare
        $options = Options::INCLUDE_REF_IFCS;
        $resource = ResourceList::makeFromInterface($args['attid'], $args['attestationType'])->one($args['attestationObjectId']);
    
        $attestation = [ 'meta' => 'placeholder' // get meta data of attestation from generic interface definition
                       , 'payload' => $resource->get($options, $depth = null)
                       ];
        
        // Content
        $response = $response->withJson($attestation, 200, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        // Download
        if ($request->getQueryParam('download')) {
            $filename = "{$args['attestationType']}_{$args['attestationObjectId']}.json";
            $response = $response->withHeader('Content-Disposition', "attachment; filename={$filename}");
        }

        return $response;
    });

    $this->post('/attestations/{formType}/{formId}/{interfaceId}', function (Request $request, Response $response, $args = []) {
        /** @var \Ampersand\AmpersandApp $ampersandApp */
        $ampersandApp = $this['ampersand_app'];
        
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
                       ];
        
        return $response->withJson($respContent, 200, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    });
})->add(function (Request $request, Response $response, callable $next) {
    // Authorisatie check doen
    /** @var \Ampersand\AmpersandApp $ampersandApp */
    // $ampersandApp = $this['ampersand_app'];
    // $accesTokens = (array) $ampersandApp->getSettings->get('ssif.accessTokens');

    // if (is_null($request->getHeaderLine('X-API-Key'))
    //     || !in_array($request->getHeaderLine('X-API-Key'), $accesTokens, true)
    // ) {
    //     throw new Exception("Access denied: token invalid", 403);
    // }

    return $next($request, $response);
});
