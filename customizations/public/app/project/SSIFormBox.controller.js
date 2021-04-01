angular.module('AmpersandApp')
.controller('SSIFormBoxController', function ($scope, $route, $q, FileUploader, NotificationService, NavigationBarService, $location) {
    $scope.uploader = null;

    $scope.getQRCodeImageSrc = function (width, height, resource) {
        // https://developers.google.com/chart/infographics/docs/qr_codes

        let url = 'https://chart.googleapis.com/chart'; // google api
        url += '?chs=' + width + 'x' + height; // dimensions (width x height)
        url += '&cht=qr'; // a QR code
        url += '&chl=' + encodeURI(resource._view_.proxy + '/verifier/' + resource._view_.formType + '/' + resource._view_.formId + '/' + resource._view_.ifcId + '?reqAttestType=' + resource._view_.attestationType); // the data to encode
        url += '&choe=UTF-8&chld=L|0';

        return url;
    };

    $scope.initSocket = function(resource) {
        
        var scriptUrl = resource._view_.proxy + '/socket.io/socket.io.js';

        var promise = loadSocketIO(scriptUrl).then(function (successMessage) {
            console.log(successMessage);

            var socket = io.connect(resource._view_.proxy);

            console.log('Connection check 1 new', socket.connected);
            socket.on('connect', function() {
                console.log('Connection check 2', socket.connected);
                socket.emit('join', resource._view_.formId);
                console.log('join with ID: ' + resource._view_.formId);
            });

            socket.on('check', function(data) {
                console.log('Check:', data);
            });

            socket.on('refresh', function(data) {
                $route.reload();
                // location.reload();
            });
            
        }, function (errorMessage) {
            console.error(errorMessage);
        });
        
    };

    $scope.initUploader = function(resource) {
        $scope.uploader = new FileUploader({
            method: 'POST',
            alias: 'file',
            url: 'api/v1/ssif/attestations/' + resource._view_.formType + '/' + resource._view_.formId + '/' + resource._view_.ifcId,
            autoUpload : true
        });

        $scope.uploader.onSuccessItem = function(fileItem, response, status, headers) {
            NotificationService.updateNotifications(response.notifications);
            if(response.sessionRefreshAdvice) NavigationBarService.refreshNavBar();
            if(response.navTo != null){
                $location.url(response.navTo);
            } else {
                $route.reload(); // reload page
            }
        };
        
        $scope.uploader.onErrorItem = function(item, response, status, headers){
            let message;
            let details;
            if(typeof response === 'object'){
                message = response.msg || 'Error while importing';
                NotificationService.addError(message, status, true);
                
                if(response.notifications !== undefined) NotificationService.updateNotifications(response.notifications); 
            }else{
                message = status + ' Error while importing';
                details = response; // html content is excepted
                NotificationService.addError(message, status, true, details);
            }
        };
    };

    function loadSocketIO(scriptUrl) {
        var deferred = $q.defer();
        var loaded = false;
        
        // Construct <script> element
        var script = document.createElement('script');
        script.type = 'text/javascript';
        script.src = scriptUrl;
        
        // Register callback to resolve promise when script is loaded
        script.onreadystatechange = script.onload = function() {
            if (!loaded) {
                deferred.resolve('Socket.io loaded');
            }
            loaded = true;
        };

        // Add script tag to body. The browser watches this and loads script
        document.body.appendChild(script);
        
        return deferred.promise;
    }
});