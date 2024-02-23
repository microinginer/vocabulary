Init project
---
```shell
cp env.example .env
```
You should change database credentials in .env then you can start containers
```shell
docker-compose up nginx -d 
```

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

Apply migrations
---
```shell
docker-compose run --rm artisan migrate
```
