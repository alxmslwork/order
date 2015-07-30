#!/bin/sh

set -e

apt-get install -y nginx-extras

echo "daemon off;" >> /etc/nginx/nginx.conf
