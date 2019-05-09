### Docker-Container

```
bash bin/build.sh; docker rm -fv rets-api.$(git rev-parse --symbolic-full-name --abbrev-ref HEAD); docker run -itd \
  --name=rets-api.$(git rev-parse --symbolic-full-name --abbrev-ref HEAD) \
  --label="git.owner=boxmls" \
  --label="git.name=service-rets-api" \
  --label="git.branch=:$(git rev-parse --symbolic-full-name --abbrev-ref HEAD)" \
  --env=GIT_OWNER=boxmls \
  --env=GIT_NAME=service-rets-api \
  --env=GIT_BRANCH=$(git rev-parse --symbolic-full-name --abbrev-ref HEAD) \
  --env=NODE_ENV=development \
  --env=PORT=8080 \
  --memory=4g \
  --publish=8000 \
  --volume=$(pwd):/opt/sources/boxmls/service-rets-api \
  boxmls/rets-api:master
```
