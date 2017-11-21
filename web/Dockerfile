FROM php:7.0.12-apache

ENV DEBIAN_FRONTEND=noninteractive

# Install the PHP extensions I need for my personnal project (gd, mbstring, opcache)
RUN apt-get update && apt-get install -y \
	apt-utils \
	libfreetype6-dev \
	libjpeg62-turbo-dev \
	libpng12-dev \
	libjpeg-dev \
	libpq-dev \
	libbz2-dev \
	git-core \
	mysql-client \
	wget \
	curl \
	freetds-dev \
	build-essential \
	libffi6 \
	cron \
	vim \
	&& rm -rf /var/lib/apt/lists/* \
	&& cp -s /usr/lib/x86_64-linux-gnu/libsybdb.so /usr/lib/ \
	&& docker-php-ext-configure pdo_mysql --with-pdo-mysql=mysqlnd \
	&& docker-php-ext-configure gd --with-freetype-dir=/usr/include/ --with-jpeg-dir=/usr/include/ \
	&& docker-php-ext-install \
	pdo_dblib \
	pdo_mysql \
	mbstring \
	opcache \
	pdo \
	zip \
	bz2\
	tokenizer \
	gd \
	# Clean after install
	&& apt-get autoremove -y && apt-get clean all

COPY ./apache/apache2.conf /etc/apache2/apache2.conf

# Edit PHP INI
#RUN echo "memory_limit = 500M" > /usr/local/etc/php/php.ini \
#    && echo "upload_max_filesize = 50M" >> /usr/local/etc/php/php.ini \
#    && echo "post_max_size = 50M" >> /usr/local/etc/php/php.ini \
#    && echo "max_input_time = 60" >> /usr/local/etc/php/php.ini \
#    && echo "file_uploads = On" >> /usr/local/etc/php/php.ini \
#    && echo "max_execution_time = 300" >> /usr/local/etc/php/php.ini \
#    && echo "LimitRequestBody = 100000000" >> /usr/local/etc/php/php.ini

RUN a2enmod rewrite \
	expires \
	mime \
	filter \
	deflate \
	proxy_http \
	headers \
	php7 \
	ssl

# Configuration for Apache
RUN rm -rf /etc/apache2/sites-enabled/*
ADD ./apache/sites-avaliables /etc/apache2/sites-available
RUN ln -s /etc/apache2/sites-available/* /etc/apache2/sites-enabled/

#install cert-bot for https
RUN curl -o /usr/local/bin/certbot-auto https://dl.eff.org/certbot-auto &&  \
  chmod +x /usr/local/bin/certbot-auto && \
  mkdir /etc/letsencrypt

COPY ./ssl /etc/letsencrypt

RUN certbot-auto --os-packages-only -n

#install composer for php and laravel

RUN curl -sS https://getcomposer.org/installer | php -- --filename=composer --install-dir=/usr/local/bin

#Add crons
ADD ./crontab /etc/crontab

# Add html files
#ADD ./web/html/ /var/www/html/

# Change working directory

WORKDIR /var/www/html

EXPOSE 80 443

COPY ./run.sh /

RUN ["chmod", "+x", "/run.sh"]

ENTRYPOINT ["/run.sh"]
