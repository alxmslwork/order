#!/bin/sh

set -e

# memcache
echo 'memcache.allow_failover=1' >> /etc/php5/mods-available/memcache.ini
echo 'memcache.session_redundancy=3' >> /etc/php5/mods-available/memcache.ini
