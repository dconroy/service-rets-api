#!/bin/bash
######################################
##
##
##
##
######################################


## Fix Permissions.
#git reset --hard
#git clean -df
#git checkout ${GIT_BRANCH}
#git pull

pm2 startOrReload ecosystem.config.js

## Pipe.
exec "$@"