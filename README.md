# php-rest-api

When you start

``` bash
composer update
```

``` bash
composer dump
```

Create .env file and populate from stuff from settings.php

make sure that your web server has PUT and DELETE verbs allowed

``` bash
DB_DRIVER = 'mysql'
DB_HOST = 'localhost'
DB_NAME = 'rest-api'
DB_USER = 'root'
DB_PASS = 'pass'
```

Make sure that those env names are not already present on your system/config as it causes errors.
