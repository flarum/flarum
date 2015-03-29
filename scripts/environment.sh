#! /bin/bash

su - vagrant
### Setup NPM globals and create necessary directories ###
sudo apt-get install -y phantomjs zsh exuberant-ctags
mkdir /home/vagrant/npm
mkdir -p /vagrant/flarum/core
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
cp /vagrant/.env.example /vagrant/.env
mysql -u root -proot -e 'create database flarum'
### Setup flarum/core ###
cd /vagrant/flarum/core
git clone https://github.com/flarum/core .
composer install --prefer-dist
mkdir public
cd /vagrant/flarum/core/ember/forum
npm install
bower install
cd /vagrant/flarum/core/ember/admin
npm install
bower install

cd /vagrant
composer install --prefer-dist
php artisan flarum:install
php artisan flarum:seed
