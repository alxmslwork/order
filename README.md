# oRDR

## Развертывание
 
Проект подготовлен для развертывания на базе `docker`. В проекте представлены два контейнера приложений:

- `ordr-base` базовый образ, содержащий всё необходимое окружение для работы приложения
- `ordr-dev` образ разработчика. Основан на базовом и предоставляет удобный инструментарий для разработки приложения

Для сборки контейнеров по образам необходимо выполнить команды

```
$ (cd dockerfiles/ordr-base && docker build -t ordr-base:0.1 .) 
$ (cd dockerfiles/ordr-dev && docker build -t ordr-dev:1.0 .) 
```

Для удобного запуска контейнеров существует файл [docker-compose.yml](dockerfiles/ordr-dev/docker-compose.yml) 
С его помощью очень удобно запускать контейнер приложения и связаных стореджей командой

```
$ docker-compose up -d
Creating ordrdev_cachesession2_1...
Creating ordrdev_cachesession1_1...
Creating ordrdev_redis_1...
Creating ordrdev_mysql_1...
Creating ordrdev_dev_1...
```

После старта контейнера разработчика, финальным шагом, в контейнере необходимо выполнить команду сборки проекта
 
```
./bin/build/dev
mysql: database map1 created
mysql: table map1::map created
mysql: database map2 created
mysql: table map2::map created
database counter created
table counter::counter created
default counter::counter.users initialized
default counter::counter.orders initialized
order1: database mysql created
mysql: table order1::order created
order2: database mysql created
mysql: table order2::order created
mysql: database payment1 created
mysql: table payment1::payment created
mysql: database payment2 created
mysql: table payment2::payment created
mysql: database user1 created
mysql: table user1::user created
mysql: database user2 created
mysql: table user2::user created
nginx config created at /var/www/ordr/config/dynamic/nginx.config
ok: run: nginx: (pid 915) 1s
```

Команда создает необходимые БД и таблицы БД. Создает конфиг сервера `nginx` и перезапускает сервер на домере 
 `ordr.alxmsl.stage`
Можно прописать IP адрес докер хоста на этот домен и пользоваться приложением через http://ordr.alxmsl.stage/index.html

## Полезные утилиты

`bin/db/init/cache.php` - скрипт прогрева поисковых сущностей по данным таблицы заказов

`bin/nginx/config` - скрипт сборки нового конфига сервера `nginx` по шаблону

`bin/session/show.php` - скрипт просмотра сессии по ее идентификатору

`bin/sync/sync` - скрипт синхронизации кода с контейнером (использует конфиг [lsyncd](bin/sync/lsyncd.conf)) 

