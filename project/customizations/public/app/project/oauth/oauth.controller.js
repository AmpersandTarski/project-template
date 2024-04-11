angular.module('AmpersandApp')
    .controller('LoginExtLoginController', function ($scope, Restangular, $location, $window, NotificationService, LoginService) {
        // When already logged in, navigate to home
        $scope.$watch(LoginService.sessionIsLoggedIn(), function () {
            if (LoginService.sessionIsLoggedIn()) {
                $location.path('/'); // goto home
            }
        });

        Restangular.one('OAuthLogin/login').get().then(
            function (data) { // on success
                $scope.idps = data.identityProviders;
                $scope.bram = "hoi";

                // Redirect automatically when there is only a single identity provider option
                if ($scope.idps.length === 1) {
                    $window.location.href = $scope.idps[0].loginUrl;
                }

                NotificationService.updateNotifications(data.notifications);
            }
        );
    }).controller('LoginExtLogoutController', function ($scope, Restangular, $location, NotificationService, NavigationBarService) {
        $scope.logout = function () {
            Restangular.one('oauthlogin/logout').get().then(
                function (data) { // success
                    NotificationService.updateNotifications(data.notifications);
                    NavigationBarService.refreshNavBar();
                    $location.path('/'); // goto home
                }
            );
        };
    });