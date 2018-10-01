all: prepare build push

prepare:
	composer install --ignore-platform-reqs

build:
	docker build -t gone/git-mirror:latest .

push:
	docker push gone/git-mirror:latest