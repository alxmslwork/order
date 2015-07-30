FROM phusion/baseimage:0.9.17

ENV DEBIAN_FRONTEND noninteractive

ADD ./build /build

RUN /build/base.sh        && \
    /build/php.sh         && \
    /build/nginx.sh       && \
    /build/services.sh    && \
    /build/locales.sh     && \
    /build/environment.sh && \
    /build/cleanup.sh

EXPOSE 80
