#!/bin/sh

echo ru_RU.UTF-8 UTF-8 >> /var/lib/locales/supported.d/local
dpkg-reconfigure locales

cp /usr/share/zoneinfo/Europe/Moscow /etc/localtime
echo Europe/Moscow > /etc/timezone

update-locale LANG=en_US.UTF-8

export LANG=en_US.UTF-8
