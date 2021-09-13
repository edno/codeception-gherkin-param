ARG version=7.4
FROM php:$version-alpine
WORKDIR /codeception
ENV XDEBUG_MODE=coverage

deps:
  RUN apk add --quiet --no-progress --no-cache $PHPIZE_DEPS
  RUN pecl -q install xdebug
  RUN docker-php-ext-enable xdebug
  RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/bin --filename=composer

setup:
  FROM +deps
  COPY . .
  RUN composer update --prefer-stable --no-progress --no-interaction --quiet

test:
  FROM +setup
  RUN ./vendor/bin/codecept run --no-interaction --coverage --coverage-xml
  SAVE ARTIFACT tests/_output AS LOCAL ./tests/_output

mutation:
  FROM +setup
  RUN ./vendor/bin/infection --min-covered-msi=80 --no-progress --no-interaction --log-verbosity=all --threads=$(nproc)
  SAVE ARTIFACT tests/_output/infection AS LOCAL ./tests/_output/infection

phpmd:
  FROM +setup
  RUN ./vendor/bin/phpmd src,tests ansi codesize,unusedcode,naming,design,controversial

phpcs:
  FROM +setup
  RUN ./vendor/bin/phpcs -p --colors src tests

phpcbf:
  FROM +setup
  RUN ./vendor/bin/phpcbf -p src tests 2>&1 || true
  SAVE ARTIFACT src AS LOCAL ./src
  SAVE ARTIFACT tests AS LOCAL ./tests

all:
  BUILD +phpcs
  BUILD +phpmd
  BUILD +test
  BUILD +mutation
