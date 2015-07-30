#!/bin/sh

set -e

chsh -s /bin/bash www-data
usermod -a -G docker_env www-data
echo "www-data ALL=(ALL:ALL) NOPASSWD: ALL" >> /etc/sudoers

mkdir -p /var/www
chown www-data:www-data -R /var/www

export PS1="\[\033[01;33m\]\u@\h\[\033[01;34m\] \w \$\[\033[00m\] "
export HISTCONTROL=ignoredups
export PAGER="less -M +Gg"
export APPLICATION_HOSTNAME=docker
export IS_IN_DOCKER=1
