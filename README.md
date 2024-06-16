<p align="center">
<a href="https://flarum.org/"><img src="./logo.png"></a>
</p>


<h2>
<span style="display: inline-block; margin-right: .3rem;">About</span>
<img style="filter: saturate(0%) brightness(100);" width="120" src="./logo.png">
</h2>

**[devmaluku](https://devmaluku.org/) is a delightfully discussion platform website in Maluku.** It's fast and easy to use, we believe that by bringing together people with the same interests can have a good influence on internet users in Maluku.

with all the features you need to run a successful community. It is designed to be:

## Installation
 Before you install, it's important to check that your server support the requirements to run, you will need:
* `Apache` (with mod_rewrite enabled) or `Nginx`
* `PHP 7.3+` with the following extensions: `curl`, `dom`, `fileinfo`, `gd`, `json`, `mbstring`, `openssl`, `pdo_mysql`, `tokenizer`, `zip`
* `MySQL 5.6+/8.0.23+` or `MariaDB 10.0.5+`

### Installing by unpacking an archive
If you don't have SSH access to your server or you prefer not to use the command line, you can install by unpacking an archive. Please make sure you choose the one that matches your PHP version and public path.


### Docker
Read the **[Installation guide](https://github.com/mondediefr/docker-flarum)** to get started. For community support, refer to the forum on chat.


### URL Rewriting

#### Apache
Includes a .htaccess file in the public directory – make sure it has been uploaded correctly. Flarum will not function properly if mod_rewrite is not enabled or .htaccess is not allowed. Be sure to check with your hosting provider (or your VPS) that these features are enabled. If you're managing your own server, you may need to add the following to your site configuration to enable .htaccess files:

```xml
<Directory "/path/to/flarum/public">
    AllowOverride All
</Directory>
```
This ensures that htaccess overrides are allowed so Flarum can rewrite URLs properly.

Methods for enabling mod_rewrite vary depending on your OS. You can enable it by running sudo a2enmod rewrite on Ubuntu. mod_rewrite is enabled by default on CentOS. Don't forget to restart Apache after making modifications!


#### Nginx
Assume you have a exsiting PHP site set up within Nginx, add the following to your server's configuration block:

```bash
include /path/.nginx.conf;
```

### Folder Ownership
During installation, Flarum may request that you make certain directories writable. Modern operating systems are generally multi-user, meaning that the user you log in as is not the same as the user Flarum is running as. The user that Flarum is running as MUST have read + write access to:

The root install directory, so Flarum can edit config.php.
The storage subdirectory, so Flarum can edit logs and store cached data.
The assets subdirectory, so that logos and avatars can be uploaded to the filesystem.
Extensions might require other directories, so you might want to recursively grant write access to the entire Flarum root install directory.

There are several commands you'll need to run in order to set up file permissions. Please note that if your install doesn't show warnings after executing just some of these, you don't need to run the rest.

First, you'll need to allow write access to the directory. On Linux:


```bash
chmod 775 -R /path/to/directory
```
If that isn't enough, you may need to check that your files are owned by the correct group and user. By default, in most Linux distributions www-data is the group and user that both PHP and the web server operate under. You'll need to look into the specifics of your distro and web server setup to make sure. You can change the folder ownership in most Linux operating systems by running:
```bash
chown -R www-data:www-data /path/to/directory
```

### Setup config
By default directory structure includes a public directory which contains only publicly-accessible files. This is a security best-practice, ensuring that all sensitive source code files are completely inaccessible from the web root.

However, if you wish to host app in a subdirectory (like yoursite.com/devmaluku), or if your host doesn't give you control over your webroot (you're stuck with something like public_html or htdocs), you can set up app without the public directory.

If you intend to install using one of the archives, you can simply use the no-public-dir (Public Path = No) archives and skip the rest of this section. If you're installing via Composer, you'll need to follow the instructions below.

Simply move all the files inside the public directory (including .htaccess) into the directory you want to serve Flarum from. Then edit .htaccess and uncomment lines 9-15 in order to protect sensitive resources. For Nginx, uncomment lines 8-11 of .nginx.conf.

You will also need to edit the index.php file and change the following line:
```php
// site.php

'base' => __DIR__,
'public' => __DIR__,
'storage' => __DIR__.'/storage',
```

## Contributing

Thank you for considering contributing! Please read the **[Contributing guide](./CONTRIBUTE.md)** to learn how you can help.

This repository only holds the skeleton application. Most development happens in [devmaluku/core](https://github.com/devmaluku/core).

