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
docker-compose run --rm composer install
```

Apply migrations
---
```shell
docker-compose run --rm artisan migrate
```
