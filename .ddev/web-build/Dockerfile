
ENV COMPOSER_ALLOW_SUPERUSER 1
ENV COMPOSER_HOME /home/.composer
RUN mkdir -p /home/.composer
RUN printf "deb http://http.us.debian.org/debian stable main contrib non-free" > /etc/apt/sources.list.d/nonfree.list
RUN  apt-get update
RUN DEBIAN_FRONTEND=noninteractive apt-get install -y \
    p7zip-full \
    build-essential \
    unrar && rm -rf /var/lib/apt/lists/*
