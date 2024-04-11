angular.module('AmpersandApp')
    .config(['$routeProvider', function ($routeProvider) {
        $routeProvider
            .when('/page/home',
                {
                    templateUrl: 'app/project/oauth/login.html',
                });
    }]);
