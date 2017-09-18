![](https://dt9ph4xofvj87.cloudfront.net/user/sites/shawacademy.com/themes/mytheme/images/logo/logo-284-50/png/regular.png)
## How to launch with one command?

* Step 1: `composer install`
* Step 2: `php -S localhost:9999 launch.php`

`launch.php` is a custom script that gives you a reproducable development environment. 

## SSO Mock

* Step 1: Before you try this out, make sure you paste `6cdVzOYGVW` in the api_keys of the flarum database in MySQL.
* Step 2: Create a `config.php` file in the `sso` folder with the contents given at the very bottom.
* Step 3: Head to [here](http://localhost:9999/admin#/extensions) and enable *Single Sign On* extension.
* Step 4: Fill in the details as shown in the image below.
* Step 5: Now, navigate to `sso` folder and run `php -S localhost:8888`
* Step 6: Access the sample SSO website on `localhost:8888` 

![](https://i.imgur.com/umGJsnx.png)

```
<?php
return [
    // URL to your Flarum forum
    'flarum_url' => 'http://localhost:9999',
    // Domain of your main site (without http://)
    'root_domain' => 'localhost',
    // Create a random key in the api_keys table of your Flarum forum
    'flarum_api_key' => '6cdVzOYGVW',
    // Random token to create passwords
    'password_token' => 'NotSecureToken',
    // How many days should the login be valid
    'lifetime_in_days' => 14,
];

```

___
* Last revision on 16/09/2017
* @aligajani

