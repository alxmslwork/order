#!/bin/sh

set -e

for i in $(ls /build/services); do
    mkdir "/etc/service/${i}"
    cp "/build/services/${i}" "/etc/service/${i}/run"
done
