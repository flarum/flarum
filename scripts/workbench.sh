#! /bin/bash

mkdir -p /vagrant/workbench/flarum/core
cd /vagrant/workbench/flarum/core
git clone https://github.com/flarum/core .

bash /vagrant/scripts/app.sh
