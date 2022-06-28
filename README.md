# burda

## Project setup
To set up project please run in project root folder
```
composer install
```
## Setup database

To set up DB please run in project root folder
```
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
```
## Project run inside docker

To run docker please run in project root folder
```
docker-compose up
```
After you can access to api

link: http://localhost:8080/api

## Run tests
For testing project please run in project roo folder
```
php bin/phpunit
```