#!/bin/sh

set -e

if [ -e /.dev-initialized ]; then
    exit 0
fi

mkdir -p /var/www/.ssh
mkdir -p /root/.ssh

echo "${DEV_SSH_PUBKEY}" >> /var/www/.ssh/authorized_keys
echo "${DEV_SSH_PUBKEY}" >> /root/.ssh/authorized_keys

chown www-data:www-data -R /var/www/.ssh
chmod go-rwx -R /var/www/.ssh
chmod go-rwx -R /root/.ssh

touch /.dev-initialized
