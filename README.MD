- [Системные требования](#системные-требования)
    - [Windows](#windows)
    - [Linux](#linux)
        - [Установка Docker](#установка-docker)
        - [Установка docker-compose](#установка-docker-compose)
- [Развертывание проекта](#развертывание-проекта)
    - [Настройка проекта](#настройка-домена)
    - [Первый запуск приложения](#первый-запуск-приложения)
    - [Последующий запуск](#последующий-запуск)
- [Тестирование сервиса](#тестирование)
  - [Первый запуск тестов](#первый-запуск-тестов)
  - [Последующий запуск тестов](#последующий-запуск-тестов)


# Системные требования
## Windows

Необходимо, чтобы на компьютере был настроен [WSL](https://docs.microsoft.com/en-us/windows/wsl/install) и установлен [Docker](https://docs.docker.com/desktop/windows/install/). С инструкциями по установке можно ознакомиться по ссылкам, приведенным в предыдущем предложении.

## Linux

### Установка Docker

С помощью следующих команд мы настроим репозиторий для установки Docker

    sudo apt update
    sudo apt install apt-transport-https ca-certificates curl software-properties-common
    curl -fsSL https://download.docker.com/linux/ubuntu/gpg | sudo apt-key add -
    sudo add-apt-repository "deb [arch=amd64] https://download.docker.com/linux/ubuntu focal stable"

После успешного добавления репозитория, установим Docker

    sudo apt install docker-ce

Теперь Docker должен быть доступен в системе, проверить можно командой

    sudo systemctl status docker

### Установка docker-compose

Запустите следующую команду для скачивания текущей стабильной версии

    sudo curl -L "https://github.com/docker/compose/releases/download/1.29.2/docker-compose-$(uname -s)-$(uname -m)" -o /usr/local/bin/docker-compose

> Чтобы установить другую версию Compose, измените 1.29.2 на ту версию, которую хотите установить
Добавьте права исполнения для исходных файлов

    sudo chmod +x /usr/local/bin/docker-compose

# Настройка домена

Чтобы обращаться к проекту по домену, необходимо зарегистрировать его в локальном hosts-файле:

    sudo cat /etc/hosts

    127.0.0.1 billing.study-on.local

# Развертывание проекта

Клонируйте репозиторий с проектом

    git clone ...

Запускаем docker-compose

    docker compose up -d // на windows эта команда должна всегда отрабатывать
    или
    sudo docker compose up -d // если на linux не прокатило с обычными правами
    или
    make up 

Под linux возможно понадобится выдать права на папки `vendor` командами:
    
    sudo chmod 777 -R vendor

Если все прошло успешно, то выполняем следующую команду для проверки работы контейнеров

    [sudo] docker ps

Результат должен выглядеть примерно так

    CONTAINER ID   IMAGE                 COMMAND                  CREATED          STATUS          PORTS                                                                                  NAMES
    1494612ad917   nginx:alpine          "/docker-entrypoint.…"   17 minutes ago   Up 17 minutes   0.0.0.0:82->80/tcp, :::82->80/tcp                                                      billing.study-on.local
    1e2228efd920   study-onbilling-php   "docker-php-entrypoi…"   17 minutes ago   Up 17 minutes   9000/tcp                                                                               study-onbilling-php-1
    e1452383eaef   mailhog/mailhog       "MailHog"                17 minutes ago   Up 17 minutes   0.0.0.0:1025->1025/tcp, :::1025->1025/tcp, 0.0.0.0:8025->8025/tcp, :::8025->8025/tcp   mailhog
    f00f040d5bc2   postgres:alpine       "docker-entrypoint.s…"   17 minutes ago   Up 17 minutes   127.0.0.1:5430->5432/tcp                                                               study-onbilling-postgres-1

## Первый запуск приложения

Выполняем команду

    [sudo] docker compose exec php composer install

Выполняем следующие команды
    
    [sudo] docker compose exec php bin/console doctrine:database:create
    [sudo] docker compose exec php bin/console make:migration
    [sudo] docker compose exec php bin/console doctrine:migration:migrate
    [sudo] docker compose exec php bin/console doctrine:fixtures:load

Если возникли какие-либо ошибки с базой данных

    docker compose exec php bin/console doctrine:schema:update --force

Если все прошло успешно, то приложение будет доступно на `localhost:82` или `billing.study-on.local:82`

## Последующий запуск

Запускаем контейнеры командой

    [sudo] docker compose up -d 
    или 
    make up

# Тестирование
## Первый запуск тестов

Выполняем следующие команды

    [sudo] docker compose exec php bin/console doctrine:database:create --env=test
    [sudo] docker compose exec php bin/console doctrine:migration:migrate --env=test
    [sudo] docker compose exec php bin/console doctrine:fixtures:load --env=test

Если возникли какие-либо ошибки с базой данных

    docker compose exec php bin/console doctrine:schema:update --force

Затем выполняем команду

    [sudo] docker compose exec php bin/phpunit --testdox
    или 
    make phpunit

## Последующий запуск тестов

    [sudo] docker compose exec php bin/phpunit --testdox
    или
    make phpunit
    
