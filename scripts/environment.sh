#! /bin/bash

su - vagrant
### Setup NPM globals and create necessary directories ###
sudo apt-get install phantomjs zsh exuberant-ctags
mkdir /home/vagrant/npm
mkdir -p /vagrant/workbench/flarum/core
sudo chown -R vagrant:vagrant /home/vagrant
npm install -g bower ember
cp /vagrant/scripts/aliases ~/.aliases

### Create rc file ###
if [ -e "/home/vagrant/.zshrc" ]
then
    echo "source ~/.aliases" >> ~/.zshrc
else
    echo "source ~/.aliases" >> ~/.bashrc
fi

### Set up environment files and database ###
cp /vagrant/.env.example.php /vagrant/.env.local.php
mysql -u root -proot -e 'create database flarum'
### Setup flarum/core ###
cd /vagrant/workbench/flarum/core
git clone https://github.com/flarum/core .
composer install
cd /vagrant/workbench/flarum/core/ember
npm install
bower install

cd /vagrant
composer install
php artisan migrate --bench="flarum/core"
php artisan db:seed --class="Flarum\Core\Support\Seeders\DatabaseSeeder"
