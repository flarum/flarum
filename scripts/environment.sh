#! /bin/bash



cp /vagrant/.env.example.php /vagrant/.env.local.php

sudo apt-get install phantomjs zsh exuberant-ctags

curl -L http://install.ohmyz.sh | sh
## Comment the below line out if you don't want zsh
chsh /bin/zsh vagrant

cd /vagrant
php artisan migrate --bench="flarum/core"
php artisan db:seed --class="Flarum\Core\Support\Seeders\DatabaseSeeder"
cd /vagrant/workbench/flarum/core/ember
npm install
bower install

