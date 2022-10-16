## Requirements
* Docker
* NPM

## Setup

```bash
cp docker-compose.yml.example docker-compose.yml
cp nginx.conf.example nginx.conf
cp redis.conf.example redis.conf

docker-compose up -d

docker-compose exec php bash
composer install
cp .env.example .env
php artisan key:generate
```

## Run parallel multiple commands

* Run queue
```bash
php artisan queue:work
```

* Run Node
```bash
cd nodejs
node server
```
