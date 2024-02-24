Init project
---
```shell
cp .env.example .env
```
You should change database credentials in .env then you can start containers

Install packages via composer
---
```shell
docker-compose run --rm composer install --prefer-dist
```

Generate key for application
---
```shell
docker-compose run --rm artisan key:generate
```

Install packages via npm
---
```shell
docker-compose run --rm node yarn install
```

Build js assets for production mode
---
```shell
docker-compose run --rm node yarn build
```
Watch js assets for development mode
---
```shell
docker-compose run --rm --service-ports node yarn dev --host
```

Start project
---
```shell
docker-compose up nginx -d 
```

Apply migrations
---
```shell
docker-compose run --rm artisan migrate
```
