#!/bin/sh

set -e

mv /build/services/20-dev-init.sh /etc/my_init.d/20-dev-init.sh

mv /build/config/ssh_host_rsa_key /etc/ssh/ssh_host_rsa_key
chmod 0600 /etc/ssh/ssh_host_rsa_key

rm /etc/service/sshd/down
