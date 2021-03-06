#!/bin/sh

set -e

# php 5.5.x
LC_ALL=en_US.utf8 add-apt-repository -y ppa:ondrej/php5
apt-get install -y php5-cli \
                   php5-cgi \
                   php5-fpm \
                   php5-apcu \
                   php5-memcache \
                   php5-intl \
                   php5-curl \
                   php5-mysql \
                   php5-mcrypt \
                   php5-xhprof \
                   php5-redis

# mcrypt
ln -s /etc/php5/mods-available/mcrypt.ini /etc/php5/fpm/conf.d/20-mcrypt.ini
ln -s /etc/php5/mods-available/mcrypt.ini /etc/php5/cli/conf.d/20-mcrypt.ini

pecl config-set php_ini /etc/php5/fpm/php.ini

# xhprof
sed -i 's/^#.*//g' /etc/php5/mods-available/xhprof.ini
ln -s /etc/php5/mods-available/xhprof.ini /etc/php5/fpm/conf.d/20-xhprof.ini
ln -s /etc/php5/mods-available/xhprof.ini /etc/php5/cli/conf.d/20-xhprof.ini
