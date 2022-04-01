# JWT JS

This module is used to generate JWT after login and set to drupalSettings.

#### Recommended Modules

- [JWT](https://www.drupal.org/project/jwt) This JWT module on its own just
provides a framework for managing a single site-wide key and
integration with a JWT library.


## Site Key

Go to /admin/config/system/keys and add a RSA key.

Configuration must be file based.

Go to the console and run the below command to generate the keys:

openssl genrsa 2048 > /path/to/private/dir/jwt.key

Go to /admin/config/system/jwt to pick the key to be used. Use the JWT RSA Key.

## Enable the Module

 Now enable the jwt_js module to set a token to drupalSettings on login.
