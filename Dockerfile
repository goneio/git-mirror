FROM alpine:3.8
ADD start_runit /sbin/
RUN mkdir /etc/container_environment && \
    chmod a+x /sbin/start_runit && \
    mkdir /etc/service && \
    mkdir /etc/runit_init.d && \
    apk add --update \
    runit \
    php7 \
    php7-common \
    php7-openssl \
    php7-tokenizer \
    php7-mbstring \
    php7-json \
    php7-zip \
    php7-curl \
    php7-posix \
    bash \
    openssh-client \
    git \
    ncurses \
    coreutils \
    && \
    rm -rf /var/cache/apk/*

WORKDIR /app

# Copy cron & worker tasks into location and chmod accordingly.
ADD ./ /app/

RUN chmod +x /app/git-mirror.php

CMD ["/app/git-mirror.php"]
