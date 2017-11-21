FROM debian:jessie

ENV EJABBERD_USER=ejabberd \
    EJABBERD_HOME=/var/lib/ejabberd \
    HOME=$EJABBERD_HOME \
    PATH=$EJABBERD_HOME/bin:/usr/sbin:/usr/bin:/sbin:/bin \
    XMPP_DOMAIN=localhost \
    # Set default locale for the environment
    LC_ALL=C.UTF-8 \
    LANG=en_US.UTF-8 \
    LANGUAGE=en_US.UTF-8

# Create directory structure and user for ejabberd
RUN groupadd -r $EJABBERD_USER \
    && useradd -r -m \
       -g $EJABBERD_USER \
       -d $EJABBERD_HOME \
       $EJABBERD_USER \
    && mkdir $EJABBERD_HOME/ssl \
    && mkdir $EJABBERD_HOME/conf \
    && mkdir $EJABBERD_HOME/backup \
    && mkdir $EJABBERD_HOME/upload \
    && mkdir $EJABBERD_HOME/database \
    && mkdir $EJABBERD_HOME/module_source \
    && chown -R $EJABBERD_USER:$EJABBERD_USER $EJABBERD_HOME

#Install dependencies
RUN set -x \
	&& buildDeps=' \
        git-core \
        build-essential \
        automake \
        libssl-dev \
        zlib1g-dev \
        libexpat-dev \
        libyaml-dev \
        libsqlite3-dev \
        erlang-src erlang-dev \
	' \
	&& requiredAptPackages=' \
	    locales \
        ldnsutils \
        python2.7 \
        python-jinja2 \
	python-requests \
        ca-certificates \
        libyaml-0-2 \
        erlang-base erlang-snmp erlang-ssl erlang-ssh erlang-webtool \
        erlang-tools erlang-xmerl erlang-corba erlang-diameter erlang-eldap \
        erlang-eunit erlang-ic erlang-odbc erlang-os-mon \
        erlang-parsetools erlang-percept erlang-typer \
        python-mysqldb \
	' \
    && apt-key adv \
        --keyserver keys.gnupg.net \
        --recv-keys 434975BD900CCBE4F7EE1B1ED208507CA14F4FCA \
	&& apt-get update \
	&& apt-get install -y $buildDeps $requiredAptPackages --no-install-recommends \
	&& dpkg-reconfigure locales && \
        locale-gen C.UTF-8 \
    && /usr/sbin/update-locale LANG=C.UTF-8 \
    && echo 'en_US.UTF-8 UTF-8' >> /etc/locale.gen \
    && locale-gen

#Install Ejabberd
RUN cd /tmp \
    && git clone https://github.com/processone/ejabberd.git --branch 16.09.x --single-branch --depth=1 ejabberd

WORKDIR /tmp/ejabberd

#Add Custom mods to the src
ADD ./mod_offline_http_post.erl src/mod_offline_http_post.erl

RUN chmod +x ./autogen.sh \
    && ./autogen.sh \
    && ./configure --enable-user=$EJABBERD_USER --enable-mysql \
    && make install \
    && cd $EJABBERD_HOME

#Remove build dependencies
RUN rm -rf /var/lib/apt/lists/* \
    && apt-get purge -y --auto-remove $buildDeps
   #&& rm -rvf /tmp/ejabberd

COPY ./validateuser.py $EJABBERD_HOME/external-auth/validateuser.py

RUN chown -R $EJABBERD_USER:$EJABBERD_USER $EJABBERD_HOME/external-auth \
&& chmod +x $EJABBERD_HOME/external-auth/validateuser.py

COPY ./run.sh /

RUN ["chmod", "+x", "/run.sh"]

# Continue as user
USER $EJABBERD_USER

# Set workdir to ejabberd root
WORKDIR $EJABBERD_HOME

ADD ./etc /etc/ejabberd/

VOLUME ["$EJABBERD_HOME/database", "$EJABBERD_HOME/ssl", "$EJABBERD_HOME/backup", "$EJABBERD_HOME/upload"]
EXPOSE 4560 5222 5269 5280 5443

ENTRYPOINT ["/run.sh"]
