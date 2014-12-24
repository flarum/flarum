#! /bin/bash

cp /vagrant/.env.example.php /vagrant/.env.local.php
sudo apt-get install phantomjs zsh exuberant-ctags
curl -L http://install.ohmyz.sh | sh
## Comment the below line out if you don't want zsh
chsh /bin/zsh vagrant

bash /vagrant/scripts/aliases.sh
