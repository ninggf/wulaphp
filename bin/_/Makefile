DST_IMG_TAG   = wula/app
TEST_SVC_NAME = test
app_files     = conf crontab modules extensions includes themes vendor wwwroot artisan bootstrap.php
DOC_RUN       = docker-compose -f tests/docker-compose.yml

build:image

image:tar
	docker build --build-arg APP_VER="${APP_VER}" -t "${DST_IMG_TAG}:${APP_VER}-app" -f docker/Dockerfile docker/
	docker build --build-arg APP_VER="${APP_VER}" -t "${DST_IMG_TAG}:${APP_VER}-svc" -f docker/service.Dockerfile docker/

tag:build
	@if [ -z "$${REG_HOST}"  ]; then \
		echo 'error: REG_HOST is not exported for registry host!';\
		echo 'please run "export REG_HOST=xx.xx.xx.xx" first!';\
		exit 1;\
	fi
	docker tag "${DST_IMG_TAG}:${APP_VER}-app" "${REG_HOST}/${DST_IMG_TAG}:${APP_VER}-app"
	docker tag "${DST_IMG_TAG}:${APP_VER}-svc" "${REG_HOST}/${DST_IMG_TAG}:${APP_VER}-svc"

tar:clean
	@if [ -z "$${APP_VER}"  ]; then \
  		echo 'error: APP_VER is not exported for app version!';\
  		echo 'please run "export APP_VER=xx.xx.xx" first!';\
		exit 1;\
	fi
	tar -cjf "docker/app-${APP_VER}.tar.bz2" ${app_files}

install:tag
	docker push "${REG_HOST}/${DST_IMG_TAG}:${APP_VER}-app"
	docker push "${REG_HOST}/${DST_IMG_TAG}:${APP_VER}-svc"

test:
	$(DOC_RUN) up -d
	$(DOC_RUN) exec ${TEST_SVC_NAME} sh ./tests/run_docker_test.sh || echo 'Could not pass the unit test!'
	$(DOC_RUN) down

clean:
	@echo "delete all bz2 files..."
	rm -f docker/*.tar.bz2
	@echo "rm old images..."
	for img in $$(docker images | grep ${DST_IMG_TAG} | awk 'BEGIN {OFS=":"} {print $$1,$$2}'); do docker rmi $$img;done
	@echo "clean done!"

cleantest:
	$(DOC_RUN) down
