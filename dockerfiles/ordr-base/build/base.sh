#!/bin/sh

# https://bugs.launchpad.net/ubuntu/+source/apt/+bug/1332440
ulimit -n 1000

apt-get update
apt-get -y upgrade

# install dev
apt-get install -y php5-dev make pkg-php-tools libpcre3-dev g++
