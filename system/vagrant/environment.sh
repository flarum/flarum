#! /bin/bash

su - vagrant
### Setup NPM globals and create necessary directories ###
sudo apt-get install -y phantomjs zsh exuberant-ctags
mkdir /home/vagrant/npm
sudo chown -R vagrant:vagrant /home/vagrant

cp /vagrant/scripts/aliases ~/.aliases

### Create rc file ###
if [ -e "/home/vagrant/.zshrc" ]
then
    echo "source ~/.aliases" >> ~/.zshrc
else
    echo "source ~/.aliases" >> ~/.bashrc
fi

### Set up environment files and database ###
cp /vagrant/system/.env.example /vagrant/system/.env
mysql -u root -proot -e 'create database flarum'

### Setup flarum/core and install dependencies ###
cd /vagrant
git submodule init
git submodule update
cd /vagrant/system/core
composer install --prefer-dist
cd /vagrant/system
composer install --prefer-dist
composer dump-autoload

cd /vagrant/core/js
bower install
cd /vagrant/core/js/forum
npm install
gulp
cd /vagrant/core/js/admin
npm install
gulp

php artisan vendor:publish
php artisan flarum:install
php artisan flarum:seed
