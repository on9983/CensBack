FROM nginx:1.22-alpine
#FROM nginx/unit:1.29.1-php8.1

#RUN docker-php-ext-install mysqli pdo pdo_mysql

WORKDIR /code

COPY . .

#RUN chown -R unit:unit /code

COPY ./docker/nginx/nginx.conf /etc/nginx/nginx.conf
#COPY ./docker/nginx/unit.conf.json /docker-entrypoint.d/.unit.conf.json
