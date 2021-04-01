angular.module('AmpersandApp')
.controller('CredentialController', function ($scope, $location) {

    $scope.getCredentialRequestURL = function (obj) {
        let data = obj._view_;
        let encodedCredentialType = encodeURIComponent(data.credentialType);
        let callbackUrl = encodeURIComponent($location.absUrl()) + '?token=';
        return `api/v1/ssif/credential-issue-request/${data.ifcId}/${data.token}?subjectId=${data.subjectId}&credentialType=${encodedCredentialType}&callbackUrl=${callbackUrl}`;
    };

    $scope.getCredentialDataURL = function (obj) {
        let data = obj._view_;
        return `api/v1/ssif/credential-data/${data.token}/${data.ifcId}/${data.subjectId}?download=yes`;
    };

    $scope.getCredentialVerifyRequestURL = function (obj) {
        let data = obj._view_;
        let encodedCredentialType = encodeURIComponent(data.credentialType);
        return `api/v1/ssif/credential-verify-request/${data.formId}?credentialType=${encodedCredentialType}`;
    }
});