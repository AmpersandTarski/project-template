# Identity and Access Management (IAM)

This page describes the IAM architecture of an Ampersand application, to allow Ampersand prototypes to work with an identity provider (IDP) based on OAuth. Users get single sign-on and you can avoid maintaining an identity administration. An Ampersand prototype can use one or more identity providers (e.g. a local identity provider, or a public one on the internet such as Google, Microsoft, Github, LinkedIn, etc.) to verify identities, so it does not need to store personal data for that purpose.

You go through four steps to set up IAM for an Ampersand prototype:
1. The Ampersand model<br>
   You need to make some changes to harvest the correct fields from the IDP’s.
2. The back end
3. The front end<br>
   The project template already contains the SIAM files for the back end and the front end.
4. Configure and link to an IDP<br>
   You can [watch a 20 minute video](https://surfdrive.surf.nl/files/index.php/s/6o3KZroivT6DfTn) on this topic (in Dutch).

## The Ampersand source code
Data that comes from an identity provider must land in Ampersand relations. So, you must define relations in your Ampersand code to process data from the identity provider. One such relation might be, for example:<br> `RELATION personEmailaddress[Person*Emailaddress]`<br>See `model/SIAM/Person.adl` or `model/SIAM/Roles.adl` for more examples. You are free to choose these relations depending on the information you need.

For privacy reasons and robustness, minimize the amount of personal data you need.

In `./SIAM/backend/src/Controller/LoginController.php` you will find the PHP-code that fills these relations.
So you can add all useful attributes your IDP provides by changing that PHP-code to fill the right relations.

## Back end
The directory backend contains the logic to translate the information from IDPs to the Ampersand relations.
The file backend/src/SIAM/ProfileData.php` contains the PHP-code to fetch data from the IDP’s API. The login controller (function login in backend/src/SIAM/OAuthController.php) makes a uniform data structure with the user’s credentials. The function login in backend/src/SIAM/LoginController.php feeds this data into the Ampersand relations. You can adapt this code to fill Ampersand relations with other information your IDP provides.

The apiLoader loads all PHP files. For this purpose, the namespaces in PHP-files must follow the directory path. The folder IPD in the back end contains some pre-configured identity providers.

OAuth.php connects endpoints with classes in de backend folder. 

The last lines of the configuration file `./SIAM/backend/config/project.yaml` tells which URL to access when a login succeeds. Similarly, it contains a URL for a failure page:
```
  OAuthLogin:
    config:
      oauthlogin.enabled: true
      # path 'redirect-after-login' triggers frontend to route back to page where status 401 was raised.
      oauthlogin.redirectAfterLogin: "#/redirect-after-login" # can be relative to global.serverURL or absolute starting with http(s)://
      oauthlogin.redirectAfterLoginFailure: "#/error?message=" # can be relative to global.serverURL or absolute starting with http(s)://
```

# Front end
The folder `./frontend-old/public/app/project/oauth` contains a controller called `oauth.controller.js` and a view called `login.html`.
 The controller connects the API calls to executable code. The view show the IDP’s logo so that a user can click on it.

# Configure
Now go to the desired identity provider to get
1. configuration information, and
2. the secret and client ID from your application to this IDP. (Please keep these secrets out of your repository)



Uitbreiden naar autorisaties:
In profileData.php wordt data uit de API gehaald (email, firstname, lastname etc.
Die verwerkt de IDP-specifieke dingen en stuurt het door naar de logincontroller.
Die slaat de rug tussen OAuth en mijn model in Ampersand.


