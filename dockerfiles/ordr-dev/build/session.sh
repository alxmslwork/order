#!/bin/sh

# session
echo 'session.save_handler=memcache' >> /etc/php5/mods-available/session.ini
echo 'session.save_path="tcp://cachesession1:11211,tcp://cachesession2:11211"' >> /etc/php5/mods-available/session.ini

ln -s /etc/php5/mods-available/session.ini /etc/php5/fpm/conf.d/30-session.ini
ln -s /etc/php5/mods-available/session.ini /etc/php5/cli/conf.d/30-session.ini

