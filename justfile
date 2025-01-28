set shell := ["docker", "compose", "run", "--entrypoint", "/bin/sh", "-it", "--rm", "biblioteca", "-c"]
composer *args="":
    /usr/local/bin/composer {{args}}

sh *args="":
    sh {{args}}

php *args="":
    php {{args}}

console *args="":
    php bin/console {{args}}

console-xdebug *args="":
    env PHP_IDE_CONFIG="serverName=biblioteca.docker.test" XDEBUG_TRIGGER=1 XDEBUG_MODE=debug php bin/console {{args}}

install:
    composer install

tests:
    composer run test

update *args="":
    composer update {{args}}

lint:
    composer run lint

rector:
    composer rector

test-phpcs:
    composer run test-phpcs

phpcs:
    composer run phpcs

phpunit *args="":
    env XDEBUG_MODE=coverage composer run test-phpunit -- {{args}}

phpunit-xdebug *args="":
    composer test-phpunit-xdebug -- {{args}}

phpstan *args="":
    composer test-phpstan -- {{args}}

npm *args="":
    npm -- {{args}}
