FROM php:8.1-fpm-alpine
RUN apk add zlib-dev libpng-dev
RUN docker-php-ext-install mysqli pdo pdo_mysql gd

WORKDIR /code

COPY . .

# RUN php bin/console doctrine:database:create --no-interaction
# RUN php bin/console make:migration --no-interaction
# RUN php bin/console doctrine:migrations:migrate --no-interaction

# CMD ["php","bin/console","doctrine:database:create", "--no-interaction"]; ["php","bin/console","make:migration ", "--no-interaction"]; ["php","bin/console","d:m:m", "--no-interaction"]