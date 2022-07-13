# OAuth2 Server user claims

This module exposes OAuth2 user claims per server and scope.

## Installation

Include the repository in your project's `composer.json` file:

    "repositories": [
        ...
        {
            "type": "vcs",
            "url": "https://github.com/EuropeanUniversityFoundation/oauth2_server_user_claims"
        }
    ],

Then you can require the package as usual:

    composer require euf/oauth2_server_user_claims

Finally, install the module:

    drush en oauth2_server_user_claims

## Usage

Once enabled, the module provides an interactive form located at `/user/{user}/oauth2-claims`, accessible via a local task link, which lists all claims associated with the user on the basis of a selected OAuth2 server.

Access to the new form requires the _View user OAuth2 claims_ permission and the ability to view the user's information.
