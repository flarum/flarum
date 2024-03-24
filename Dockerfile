FROM debian
WORKDIR /var/www/html
VOLUME [ "/var/www/html" ]
RUN apt-get update && apt-get install -y apache2 php-common php-mysql php-mbstring php-xml php-curl php-exif php-gd php-intl php-soap php-zip composer
RUN a2enmod rewrite
RUN sed -i 's#^DocumentRoot ".*#DocumentRoot "/var/www/html/public"#' /etc/apache2/sites-enabled/000-default.conf
COPY . /var/www/html
RUN composer install --no-dev --optimize-autoloader
RUN chown -R www-data:www-data /var/www/html
RUN chmod 755 /var/www/html/
EXPOSE 80
CMD ["apache2ctl", "-D", "FOREGROUND"]
