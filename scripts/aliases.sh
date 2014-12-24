#! /bin/bash
cp /vagrant/scripts/aliases ~/.aliases

if [ -e "/home/vagrant/.zshrc" ]
then
    echo "source ~/.aliases" >> ~/.zshrc
else
    echo "source ~/.aliases" >> ~/.bashrc
fi
