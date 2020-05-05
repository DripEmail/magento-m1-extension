FROM ubuntu:20.04

ENV DEBIAN_FRONTEND=noninteractive
ARG CONTINUOUS_INTEGRATION

RUN apt update && apt install -y software-properties-common git wget curl gnupg2 ca-certificates lsb-release vim nginx fcgiwrap unzip net-tools make
RUN add-apt-repository ppa:ondrej/php
RUN apt install -y php5.6 php5.6-curl php5.6-dom php5.6-gd php5.6-iconv php5.6-mcrypt php5.6-pdo php5.6-simplexml php5.6-soap php5.6-mbstring php5.6-pdo-mysql php5.6-fpm php5.6-dev
RUN apt purge --assume-yes apache2
RUN service nginx stop;service php5.6-fpm stop;echo "nginx and php-fpm stopped"
# https://github.com/nginxinc/docker-nginx/blob/8921999083def7ba43a06fabd5f80e4406651353/mainline/jessie/Dockerfile#L21-L23
RUN ln -sf /dev/stdout /var/log/nginx/magento-access.log && ln -sf /dev/stderr /var/log/nginx/magento-error.log

# Configure nginx and php
RUN update-alternatives --set php /usr/bin/php5.6; \
    update-alternatives --set phar /usr/bin/phar5.6; \
    update-alternatives --set phar.phar /usr/bin/phar.phar5.6; \
    update-alternatives --set phpize /usr/bin/phpize5.6; \
    update-alternatives --set php-config /usr/bin/php-config5.6
RUN test "true" = "${CONTINUOUS_INTEGRATION}" && echo "skipping install of php-xdebug" || pecl install xdebug-2.5.5
ADD nginx*nginx.patch /etc/nginx/nginx.patch
ADD php*opcache.ini /etc/php/5.6/mods-available/10-opcache.patch
ADD nginx*magento /etc/nginx/sites-available/magento
ADD --chown=root nginx*startup /startup.sh
RUN cd /etc/nginx && /usr/bin/patch < nginx.patch && rm nginx.patch && cd
RUN cd /etc/php/5.6/mods-available && /usr/bin/patch < 10-opcache.patch && rm 10-opcache.patch && cd
RUN chmod 700 /startup.sh
COPY nginx*fpm_patch /etc/php/5.6/fpm/pool.d/www.conf.patch
COPY php*xdebug.ini /etc/php/5.6/mods-available/xdebug.ini
RUN rm -f /etc/php/5.6/fpm/conf.d/20-xdebug.ini;test "true" = "${CONTINUOUS_INTEGRATION}" && echo "skipping php-xdebug configuration" || ln -s /etc/php/5.6/mods-available/xdebug.ini /etc/php/5.6/fpm/conf.d/20-xdebug.ini
RUN ln -s /etc/nginx/sites-available/magento /etc/nginx/sites-enabled/magento
RUN cd /etc/php/5.6/fpm/pool.d && /usr/bin/patch < www.conf.patch && rm www.conf.patch && cd

# Set up Magento
RUN mkdir -p /var/www/html/magento
RUN chown -R www-data:www-data /var/www/html/magento
USER www-data
RUN git clone --depth 1 --branch 1.9.4.3 https://github.com/OpenMage/magento-mirror.git /var/www/html/magento
RUN chmod +x /var/www/html/magento/mage /var/www/html/magento/cron.sh
EXPOSE 80

USER root
CMD [ "/startup.sh" ]

# Make life a little nicer when we log into the container.
WORKDIR /var/www/html/magento
