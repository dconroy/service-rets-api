#!/bin/bash
######################################
##
##
##
##
######################################


## Fix Permissions.
sudo chown -R core:core       /home/core
sudo chmod +x /etc/init.d/*

git reset --hard
git clean -df
git checkout ${GIT_BRANCH}
git pull

# In case it was not created
sudo mkdir -p /var/run

sudo NODE_ENV=production npm install --loglevel warn -g pm2

npm install
sudo npm link

## Service Status
pm2 start /opt/sources/boxmls/service-rets-api/status.js \
  --name "status" \
  --log=/var/log/service-rets-api/status.log \
  --silent \
  --force \
  --merge-logs \
  --log-date-format="YYYY-MM-DD HH:mm:ss"

#sudo service nginx            start
nohup hhvm --mode server -vServer.Type=fastcgi -vServer.Port=9000 >/var/log/service-rets-api/hhvm.out 2>/var/log/service-rets-api/hhvm.error &
sudo service nginx start

## Pipe.
exec "$@"