#! /bin/bash

cd /vagrant
php artisan migrate --bench="flarum/core"
php artisan db:seed --class="Flarum\Core\Support\Seeders\DatabaseSeeder"
cd /vagrant/workbench/flarum/core/ember
npm install
bower install
