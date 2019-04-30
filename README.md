# InfluxDB vs Elasticsearch for time series and metrics data

## Environment 👨🏻‍💻

- Mac OS X (Mojave 10.14) - 16 GB RAM
- Docker (with [Dinghy](https://github.com/codekitchen/dinghy))
- PHP 7.3
- InfluxDB 1.7.5 (Chronograf 1.7.10)
- Elasticsearch 7.0.0 (Kibana 7.0.0)

Ensure `dev.test` points the IP of your Docker daemon by editing your `/etc/hosts` file.

## Run it! 🏃 (recommanded)

It's recommanded to use available commands in the Makefile to get started quickly. :blush:

Chronograf (for InfluxDB) will be accessible on: `dev.test:18888`.  
Kibana (for Elasticsearch) will be accessible on: `dev.test:15601`.

```bash
make install # install docker stack
make start # start docker stack (not needed after an install)
make stop # stop docker stack
make clean # delete all docker stack (including generated datas)

make bench-ingestion-influxdb # benchmark influxdb ingestion performance
make bench-ingestion-elasticsearch # benchmark elasticsearch ingestion performance
make bench-response-time-influxdb # benchmark influxdb query response time
make bench-response-time-elasticsearch # benchmark elasticsearch query response time
make bench-disk-usage-influxdb # benchmark influxdb disk usage (run ingestion command first)
make bench-disk-usage-elasticsearch # benchmark elasticsearch disk usage (run ingestion command first)
```

## Or, if you prefer doing it manually...

### Dockerization 🐳

#### InfluxDB

```bash
# Create network, volume and containers. Chronograf is accessible on: dev.test:18888.
docker network create bench_influxdb && \
docker run -p 18086:8086 -v $PWD/influxdb:/var/lib/influxdb --name bench_influxdb -d -e INFLUXDB_DB=rio --net=bench_influxdb influxdb && \
docker run -p 18888:8888 --name bench_chronograf -d --net=bench_influxdb chronograf --influxdb-url=http://bench_influxdb:8086
```

#### Elasticsearch

```bash
# Create network, volume and containers. Kibana is accessible on: dev.test:15601.
docker network create bench_elasticsearch && \
docker run -p 19200:9200 -v $PWD/elasticsearch:/usr/share/elasticsearch/data --name bench_elasticsearch -d -e "discovery.type=single-node" --net bench_elasticsearch elasticsearch:7.0.0 && \
docker run -p 15601:5601 --name bench_kibana -d -e ELASTICSEARCH_HOSTS=http://bench_elasticsearch:9200 --net bench_elasticsearch kibana:7.0.0
```

### Benchmark it! ✨

```bash
composer install

# InfluxDB
php src/influxdb/ingestion.php
php src/influxdb/response-time.php

# Elasticsearch
php src/elasticsearch/ingestion.php
php src/elasticsearch/response-time.php
```
