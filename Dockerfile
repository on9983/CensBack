FROM ghcr.io/on9983/oni-php:php81

WORKDIR /code

COPY . .

COPY nginx.conf /etc/nginx/nginx.conf
COPY .env.dockerprod ./.env

ENV DOCKER_ENV=true
ENV APP_ENV=prod