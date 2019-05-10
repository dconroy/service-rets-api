![image](https://user-images.githubusercontent.com/308489/57512890-9acacc00-7315-11e9-854f-ad77da4d2742.png)

# Service RETS API

* PHP proxy server for interacting with a RETS server to download real estate listings and related data made available from an MLS system. 
* Receiving REST requests along with converting it into RETS requests, applying MLS-specific conventions on-the-fly. 

![image](https://user-images.githubusercontent.com/308489/57520206-c35ac200-7325-11e9-9042-9ca4680539c2.png)

### Docker Start

To run in development mode we need to change the name and volume-mount our repository for SSH deployment to work:

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

## Installation

* Make a fork of the current repository into your user/organization
* Update config data with needed values at [config.json](https://github.com/boxmls/service-rets-api/blob/master/config.json#L4-L10)

## Swager setup 

Swagger UI can be accessed here:

`/docs/index.html`

API Key required: 
[package.json](https://github.com/boxmls/service-mls-api/blob/master/package.json#L15)

Also, Swagger UI is accessible via direct URL to rets-api service. To get direct URL:

* SSH to any cluster's machine
* Check PORT which is used by `rets-api` service ( use `docker ps` to find `rets-api` container ).
* To load Swagger UI via direct link, set HEADER `DEBUG=true`. It's required to determine Swagger's resources.
* Note! If resource is not using SSL connect, the following HEADER must be set too: `x-protocol=http`.
* The direct link will be like: `http://host:32768/docs/index.html`

![image](https://user-images.githubusercontent.com/12067297/57527778-37529580-7339-11e9-8c3b-6a9b1b881251.png)

![image](https://user-images.githubusercontent.com/12067297/57527541-9fed4280-7338-11e9-967b-1af8387e62e2.png)

# Support

Do you have any questions. Please, visit [Support](https://boxmls.github.io/support) page for consulting and help.
