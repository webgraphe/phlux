FROM mlocati/php-extension-installer:latest AS php-ext-installer
FROM composer:2 AS composer

FROM php:8.4-cli

RUN pecl install xdebug \
    && docker-php-ext-enable xdebug

COPY --from=php-ext-installer /usr/bin/install-php-extensions /usr/local/bin/
COPY --from=composer /usr/bin/composer /usr/bin/composer
COPY --from=composer /usr/bin/composer /usr/local/bin/composer

RUN  <<EOF
apt-get update
apt-get upgrade -y
apt-get install -y --no-install-suggests --no-install-recommends \
  bc \
  gettext \
  git \
  libfcgi-bin \
  libsasl2-dev \
  openssh-client \
  tzdata \
  unzip \
  wget \
  zip
rm -rf /var/lib/apt/lists/*

cp /usr/share/zoneinfo/America/Toronto /etc/localtime

mkdir -m 0600 ~/.ssh && ssh-keyscan github.com >> ~/.ssh/known_hosts

mkdir /fh-entrypoint.d

addgroup --gid 1000 app
adduser --uid 1000 --disabled-password --ingroup app app

# Install PHP extensions
IPE_LZF_BETTERCOMPRESSION=0 install-php-extensions \
  apcu \
  bcmath \
  bz2 \
  calendar \
  gd \
  igbinary \
  intl \
  lzf \
  opcache \
  pcntl \
  zip \
  zstd

ln -s "${PHP_INI_DIR}/php.ini-development" "${PHP_INI_DIR}/php.ini"

# These were templated when entrypoint command performed
rm /usr/local/etc/php-fpm.d/zz-docker.conf /usr/local/etc/php-fpm.d/www.conf.default

if [ "$(uname -m)" = "aarch64" ]; then arch="aarch64"; else arch="x86_64"; fi
curl -sL -o /usr/local/bin/dumb-init "https://github.com/Yelp/dumb-init/releases/download/v${DUMB_INIT_VERSION}/dumb-init_${DUMB_INIT_VERSION}_${arch}"
chmod +x /usr/local/bin/dumb-init
EOF

WORKDIR /opt/project
