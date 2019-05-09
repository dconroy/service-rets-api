#!/bin/sh

_BRANCH=$(git rev-parse --symbolic-full-name --abbrev-ref HEAD);

echo "Building [boxmls/service-rets-api:$_BRANCH] image."

docker build \
  --no-cache=true \
  --tag=boxmls/service-rets-api:${_BRANCH} \
  $(readlink -f $(pwd))

