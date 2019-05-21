#################################################################
## MPO RETS API
##
##
## @author potanin@UD
#################################################################


FROM          hipstack/hipstack:0.2.0

USER          root

ENV           DOCKER_IMAGE boxmls/rets-api


RUN						\
              wget -qO - https://packagecloud.io/gpg.key | sudo apt-key add -

RUN           \
              export DEBIAN_FRONTEND=noninteractive && \
              apt-get -y --force-yes update && \
              apt-get -y --force-yes upgrade

RUN           \
              export DEBIAN_FRONTEND=noninteractive && \
              apt-get -y --force-yes upgrade hhvm

ENV           NGINX_VERSION 1.9.4
ENV           NPS_VERSION 1.9.32.6

RUN           \
              curl --silent http://nodejs.org/dist/v6.10.3/node-v6.10.3.tar.gz --output ~/node-v6.10.3.tar.gz && \
              tar -xzvf ~/node-v6.10.3.tar.gz -C ~/ && \
              cd ~/node-v6.10.3 && \
              ./configure && \
              make && \
              make install && \
              cd ~/ && \
              rm -rf ~/node-v6.10.3*

RUN           \
              echo deb http://httpredir.debian.org/debian wheezy-backports main | sudo tee /etc/apt/sources.list.d/backports.list && \
              apt-get update --fix-missing

RUN           \
              apt-get install -y --force-yes nginx && \
              apt-get clean all


RUN           \
              apt-get install -y --force-yes nginx-extras && \
              apt-get clean all

RUN           \
              cd /usr/src && \
              wget --quiet https://github.com/pagespeed/ngx_pagespeed/archive/release-${NPS_VERSION}-beta.zip && \
              unzip release-${NPS_VERSION}-beta.zip && \
              cd /usr/src/ngx_pagespeed-release-${NPS_VERSION}-beta/ && \
              wget --quiet https://dl.google.com/dl/page-speed/psol/${NPS_VERSION}.tar.gz && \
              tar -xzvf ${NPS_VERSION}.tar.gz

RUN           \
              cd /usr/src && \
              wget --quiet http://nginx.org/download/nginx-${NGINX_VERSION}.tar.gz && \
              tar -xvzf nginx-${NGINX_VERSION}.tar.gz && \
              cd /usr/src/nginx-${NGINX_VERSION}/ && \
                ./configure --add-module=/usr/src/ngx_pagespeed-release-${NPS_VERSION}-beta \
                  --with-http_ssl_module \
                  --with-threads \
                  --with-stream \
                  --prefix=/usr/local/share/nginx \
                  --conf-path=/etc/nginx/nginx.conf \
                  --sbin-path=/usr/sbin \
                  --error-log-path=/var/log/nginx/error.log && \
                  make && make install

RUN           \
              cd /usr && \
              rm -fr /usr/src/* && \
              mkdir -p /var/pagespeed/cache && \
              apt-get clean && \
              rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/* && \
              find /var/log -type f | while read f; do echo -ne '' > $f; done;


RUN           \
              curl -skS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin && \
              ln -sf /usr/local/bin/composer.phar /usr/local/bin/composer && \
              chmod +x /usr/local/bin/composer

RUN           \
              usermod --home /home/core --login core hipstack && \
              usermod -a -G sudo core && \
              groupmod -n core hipstack && \
              echo core:jxchpwnzaggbyhme | /usr/sbin/chpasswd && \
              usermod -a -G sudo core && \
              mv /home/hipstack /home/core && \
              yes | cp -r /root/.scripts /home/core && \
              chown -R core:core /home/core && \
              echo "core ALL=(ALL) NOPASSWD: ALL" >> /etc/sudoers && \
              mkdir -p /root/.ssh && \
              mkdir -p /etc/pki/tls/certs && \
              mkdir -p /etc/pki/tls/private && \
              mkdir -p /home/core/.ssh && \
              mkdir -p /home/core/.config

RUN           \
              mkdir -p /root/.ssh && \
              mkdir -p /etc/pki/tls/certs && \
              mkdir -p /etc/pki/tls/private && \
              mkdir -p /var/run

RUN           \
              mkdir /var/log/service-rets-api && \
              chown 500:500 /var/log/service-rets-api

ADD           .              /opt/sources/boxmls/service-rets-api/

RUN           \
              cd /opt/sources/boxmls/service-rets-api && \
              composer install --quiet

RUN           \

              ln -sf /opt/sources/boxmls/service-rets-api/static/etc/default/hhvm.sh /etc/default/hhvm.sh && \
              ln -sf /opt/sources/boxmls/service-rets-api/static/etc/hhvm/php.ini /etc/hhvm/php.ini && \
              ln -sf /opt/sources/boxmls/service-rets-api/static/etc/hhvm/server.ini /etc/hhvm/server.ini && \
              ln -sf /opt/sources/boxmls/service-rets-api/static/etc/nginx/default.conf /etc/nginx/sites-enabled/default && \
              ln -sf /opt/sources/boxmls/service-rets-api/static/etc/nginx/nginx.conf /etc/nginx/nginx.conf && \
              ln -sf /opt/sources/boxmls/service-rets-api/static/etc/nginx/hhvm.conf /etc/nginx/hhvm.conf && \
              ln -sf /opt/sources/boxmls/service-rets-api/static/etc/home/bashrc.sh /home/core/.bashrc && \
              ln -sf /opt/sources/boxmls/service-rets-api/static/etc/home/bashrc.sh /root/.bashrc && \
              ln -sf /opt/sources/boxmls/service-rets-api/static/git-hooks/post-merge.sh /opt/sources/boxmls/service-rets-api/.git/hooks/post-merge

RUN           \
              chmod +x /opt/sources/boxmls/service-rets-api/bin/*.sh && \
              chmod +x /opt/sources/boxmls/service-rets-api/static/etc/home/bashrc.sh && \
              chmod +x /opt/sources/boxmls/service-rets-api/static/etc/init.d/*.sh && \
              chown -R 500:500 /opt/sources/boxmls/service-rets-api

RUN           \
              touch /var/log/syslog && \
              touch /var/log/monit.log && \
              sudo chown -R 500:500 /var/log/syslog && \
              sudo chown -R 500:500 /var/log/monit.log

RUN           \
              sudo chown root:root /root/.ssh && \
              sudo chown -R 500:500 /home/core && \
              sudo chown -R 500:500 /opt/sources/boxmls/service-rets-api && \
              sudo chown -R core:core /usr/local/bin

RUN           \
              echo "127.0.0.1 localhost" >> /etc/hosts

VOLUME        [ "/opt/sources/boxmls/service-rets-api" ]

WORKDIR       /opt/sources/boxmls/service-rets-api

EXPOSE        8000

USER          core

ENTRYPOINT    [ "/bin/bash", "/opt/sources/boxmls/service-rets-api/bin/docker.entrypoint.sh" ]

CMD           [ "/bin/bash" ]
