![](https://dt9ph4xofvj87.cloudfront.net/user/sites/shawacademy.com/themes/mytheme/images/logo/logo-284-50/png/regular.png)
## How to launch with one command?

* Step 1: `composer install`
* Step 2: `php -S localhost:9999 launch.php`

`launch.php` is a custom script that gives you a reproducable development environment. 

## SSO Mock

* Step 1: Before you try this out, make sure you paste `6cdVzOYGVW` in the api_keys of the flarum database in MySQL.
* Step 2: Head to [here](http://localhost:9999/admin#/extensions) and enable *Single Sign On* extension.
* Step 3: Fill in the details as show in here ![](https://i.imgur.com/umGJsnx.png)
* Step 3: Now, navigate to `sso` folder and run `php -S localhost:8888`
* Step 4: Access the sample SSO website on `localhost:8888` 

___
* Last revision on 16/09/2017
* @aligajani

