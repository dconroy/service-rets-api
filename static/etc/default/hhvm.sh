#!/bin/sh
## This is a configuration file for /etc/init.d/hhvm.
## Overwrite start up configuration of the hhvm service.
##
## This file is sourced by /bin/sh from /etc/init.d/hhvm.

CONFIG_FILE="/etc/hhvm/server.ini"

RUN_AS_USER="core"
RUN_AS_GROUP="core"

## Add additional arguments to the hhvm service start up that you can't put in CONFIG_FILE for some reason.
## Default: ""
## Examples:
##   "-vLog.Level=Debug"                Enable debug log level
##   "-vServer.DefaultDocument=app.php" Change the default document
ADDITIONAL_ARGS="-v Eval.JitProfileInterpRequests=2"

## PID file location.
## Default: "/var/run/hhvm/pid"
PIDFILE="/var/run/hhvm/hhvm.pid"