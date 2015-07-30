FROM phusion/baseimage:0.9.17

ENV DEBIAN_FRONTEND noninteractive

ADD ./build /build

RUN /build/base.sh
RUN /build/php.sh && \
    /build/nginx.sh && \
    /build/services.sh && \
    /build/locales.sh && \
    /build/environment.sh && \
    /build/ssh.sh && \
    /build/cleanup.sh

EXPOSE 22
EXPOSE 80
