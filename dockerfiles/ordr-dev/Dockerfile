FROM ordr-base:0.1

ENV DEBIAN_FRONTEND noninteractive

ADD ./build /build

RUN /build/memcache.sh && \
    /build/session.sh && \
    /build/tools.sh && \
    /build/nginx.sh && \
    /build/ssh.sh

EXPOSE 22
