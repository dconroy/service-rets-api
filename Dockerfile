#################################################################
## MPO RETS API
##
##
## @author potanin@UD
#################################################################


FROM          hhvm/hhvm-proxygen:3.27.8

USER          root

ENV           DOCKER_IMAGE boxmls/rets-api
ENV           DEBIAN_FRONTEND noninteractive


RUN						\
              wget -qO - https://packagecloud.io/gpg.key | apt-key add -

RUN curl -sL https://deb.nodesource.com/setup_6.x | bash

RUN           \
              apt-get -y update && \
              apt-get -y upgrade

RUN apt-get -y install curl python-software-properties nano nodejs

RUN           \
              npm install -g pm2

RUN           \
              apt-get install -y php-curl php-mbstring php-zip

RUN           \
              curl -sS https://getcomposer.org/installer | php && \
              mv composer.phar /usr/bin/composer


RUN           \
              mkdir /var/log/service-rets-api && \
              chown 500:500 /var/log/service-rets-api

ADD           .  /opt/sources/boxmls/service-rets-api/

WORKDIR       /opt/sources/boxmls/service-rets-api

#RUN           \
#              composer install --quiet

RUN           \
              adduser --home /home/core core --uid 500 --disabled-password --gecos "" && \
              usermod -aG sudo core

RUN           ssh-keyscan -t rsa github.com | ssh-keygen -lf -

#RUN           \
 #             npm install --prod

RUN           \
              ln -sf /opt/sources/boxmls/service-rets-api/static/etc/default/hhvm.sh /etc/default/hhvm.sh && \
              ln -sf /opt/sources/boxmls/service-rets-api/static/etc/hhvm/php.ini /etc/hhvm/php.ini && \
              ln -sf /opt/sources/boxmls/service-rets-api/static/etc/hhvm/server.ini /etc/hhvm/server.ini

RUN           \
              chmod +x /opt/sources/boxmls/service-rets-api/bin/*.sh && \
              chown -R 500:500 /opt/sources/boxmls/service-rets-api

RUN           \
              touch /var/log/syslog && \
              chown -R 500:500 /var/log/syslog

RUN           \
              chown -R 500:500 /home/core && \
              chown -R 500:500 /opt/sources/boxmls/service-rets-api && \
              chown -R core:core /usr/local/bin

RUN           \
              echo "127.0.0.1 localhost" >> /etc/hosts

VOLUME        [ "/opt/sources/boxmls/service-rets-api" ]

WORKDIR       /opt/sources/boxmls/service-rets-api

EXPOSE        8000

#USER          core

ENTRYPOINT    [ "/bin/bash", "/opt/sources/boxmls/service-rets-api/bin/docker.entrypoint.sh" ]

CMD           [ "/bin/bash" ]
