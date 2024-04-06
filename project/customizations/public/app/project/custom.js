// This file routes HTML-pages from a path in the URL to a page in the directory /public of the site (in this case /var/www/public/)
// For explanation please consult https://github.com/orgs/AmpersandTarski/discussions/1399
angular.module('AmpersandApp')
.config(['$routeProvider', function ($routeProvider) {
    $routeProvider
        .when('/gegevens', // route path in url after '#' (in case of localhost:  https://localhost/#/gegevens)
            {
                templateUrl: 'app/src/shared/gegevens.html', // path to custom html file to route to, relative to the `public` directory
            });
}]);