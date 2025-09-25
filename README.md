1. Скопировать env
```
cp .env.example .env
```

2. Прописать в env DATABASE_URL и MESSENGER_TRANSPORT_DSN

3. При первом запуске
```
docker-compose up -d --build
```

4. Последующие
```
docker-compose up -d
```
```
docker-compose down
```
