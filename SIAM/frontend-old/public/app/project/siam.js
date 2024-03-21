angular.module('AmpersandApp')
    .config(['$routeProvider', function ($routeProvider) {
        $routeProvider
            .when('/ext/Login', {
                resolveRedirectTo: ['LoginService', function (LoginService) {
                    if (LoginService.sessionIsLoggedIn()) {
                        return '/'; // nav to home when user is already loggedin
                    } else {
                        return; // will continue this route using controller and template below
                    }
                }],
                templateUrl: 'app/project/oauth/login.html',
                interfaceLabel: 'Login'
            });
    }]);
