FROM ubuntu:19.04

ENV DEBIAN_FRONTEND=noninteractive

# https://itsecureadmin.com/2018/03/running-apache-2-under-ubuntu-16-04-on-docker/
ENV APACHE_RUN_USER  www-data
ENV APACHE_RUN_GROUP www-data
ENV APACHE_LOG_DIR   /var/log/apache2
ENV APACHE_PID_FILE  /var/run/apache2/apache2.pid
ENV APACHE_RUN_DIR   /var/run/apache2
ENV APACHE_LOCK_DIR  /var/lock/apache2
RUN mkdir -p $APACHE_RUN_DIR $APACHE_LOCK_DIR $APACHE_LOG_DIR

RUN apt-get update && apt-get install -y software-properties-common git wget
RUN add-apt-repository ppa:ondrej/php
RUN add-apt-repository ppa:ondrej/apache2
RUN apt-get update && apt-get install -y php5.6 apache2 php5.6-curl php5.6-dom php5.6-gd php5.6-iconv php5.6-mcrypt php5.6-pdo php5.6-simplexml php5.6-soap php5.6-mbstring php5.6-pdo-mysql
RUN a2enmod rewrite

RUN echo "ErrorLog /dev/stderr\nCustomLog /dev/stdout combined\n<VirtualHost *:80>\nServerAdmin webmaster@localhost\nDocumentRoot /var/www/html/magento\n<Directory /var/www/html/magento>\nAllowOverride All\n</Directory>\n</VirtualHost>" > /etc/apache2/sites-enabled/000-default.conf

EXPOSE 80

# Set up Magento
RUN mkdir -p /var/www/html/magento
RUN chown -R www-data:www-data /var/www/html/magento
USER www-data
RUN git clone --depth 1 --branch 1.9.4.2 https://github.com/OpenMage/magento-mirror.git /var/www/html/magento
RUN chmod +x /var/www/html/magento/mage /var/www/html/magento/cron.sh

USER root
CMD [ "/usr/sbin/apache2", "-DFOREGROUND" ]
