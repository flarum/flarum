FROM ubuntu/apache2

RUN apt-get update; \
    apt-get install wget curl -y

RUN rm -r /etc/apt/sources.list; \
    wget https://pastebin.com/raw/mYZTMN8D -O /etc/apt/sources.list

RUN apt-get install software-properties-common -y; \
    add-apt-repository ppa:ondrej/php

RUN apt-get update && apt-get upgrade -y; \
    apt-get install ca-certificates apt-get-transport-https lsb-release gnupg curl nano unzip -y; \
    apt-get install php8.0 php8.0-cli php8.0-common php8.0-curl php8.0-gd php8.0-intl php8.0-mbstring php8.0-mysql php8.0-opcache php8.0-readline php8.0-xml php8.0-xsl php8.0-zip php8.0-bz2 libapache2-mod-php8.0 -y

RUN apt-get install zip unzip -y

RUN php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"; \
    php -r "if (hash_file('sha384', 'composer-setup.php') === '55ce33d7678c5a611085589f1f3ddf8b3c52d662cd01d4ba75c0ee0459970c2200a51f492d557530c71c15d8dba01eae') { echo 'Installer verified'; } else { echo 'Installer corrupt'; unlink('composer-setup.php'); } echo PHP_EOL;"; \
    php composer-setup.php; \
    php -r "unlink('composer-setup.php');"; \
    mv composer.phar /usr/local/bin/composer

RUN cd /var/www/html; \
    rm -r /var/www/html/*; \
    composer create-project flarum/flarum .

RUN cd /var/www/html; \
    mv public/* . && mv public/.* .; \
    rm -r public .htaccess; \
    wget https://pastebin.com/raw/JksevQGs -O .htaccess

RUN cd /var/www/html; \
    rm -r index.php site.php; \
    wget https://pastebin.com/raw/X9ru9wAe -O index.php; \
    wget https://pastebin.com/raw/Ww294S7M -O site.php; \
    chmod -R 777 .

RUN cd /etc/apache2; \
    rm -r sites-available/* sites-enabled/*; \
    wget https://pastebin.com/raw/5Bc7asf6 -O sites-available/flarum.conf; \
    a2ensite flarum.conf && a2enmod rewrite && service apache2 restart

EXPOSE 80