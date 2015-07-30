#!/bin/sh

set -e

# remove dev
apt-get remove -y php5-dev make pkg-php-tools libpcre3-dev g++
apt-get autoremove -y

apt-get clean

rm -rf /build
rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*
