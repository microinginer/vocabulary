Init project
---
```shell
cp env.example .env
```
You should change database credentials in .env then you can start containers

Install packages via composer
---
```shell
docker-compose run --rm composer install --prefer-dist
```

Install packages via npm
---
```shell
docker-compose run --rm node yarn install
```

Build js assets
---
```shell
docker-compose run --rm node yarn build
```

Start project in Production mode
---
```shell
docker-compose up nginx -d 
```

Start project in development mode
---
```shell
docker-compose -f docker-compose.yaml -f docker-compose-dev.yaml up nginx -d
```

Apply migrations
---
```shell
docker-compose run --rm artisan migrate
```
