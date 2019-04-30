CURRENT_DIR=$(shell pwd)

install-influxdb:
	docker network create bench_influxdb && \
	docker run -p 18086:8086 -v $(CURRENT_DIR)/influxdb:/var/lib/influxdb --name bench_influxdb -d -e INFLUXDB_DB=rio --net=bench_influxdb influxdb && \
	docker run -p 18888:8888 --name bench_chronograf -d --net=bench_influxdb chronograf --influxdb-url=http://bench_influxdb:8086

install-elasticsearch:
	docker network create bench_elasticsearch && \
	docker run -p 19200:9200 -v $(CURRENT_DIR)/elasticsearch:/usr/share/elasticsearch/data --name bench_elasticsearch -d -e "discovery.type=single-node" --net bench_elasticsearch elasticsearch:7.0.0 && \
	docker run -p 15601:5601 --name bench_kibana -d -e ELASTICSEARCH_HOSTS=http://bench_elasticsearch:9200 --net bench_elasticsearch kibana:7.0.0

install: install-influxdb install-elasticsearch
	composer install

start-influxdb:
	docker start bench_influxdb bench_chronograf

start-elasticsearch:
	docker start bench_elasticsearch bench_kibana

start: start-influxdb start-elasticsearch

stop-influxdb:
	docker stop bench_influxdb bench_chronograf > /dev/null 2>&1 || true

stop-elasticsearch:
	docker stop bench_elasticsearch bench_kibana > /dev/null 2>&1 || true

stop: stop-influxdb stop-elasticsearch

clean-influxdb: stop-influxdb
	docker rm bench_influxdb bench_chronograf > /dev/null 2>&1 || true
	sleep 5
	docker network rm bench_influxdb > /dev/null 2>&1 || true
	rm -rf ./influxdb && mkdir influxdb && touch influxdb/.gitkeep

clean-elasticsearch: stop-elasticsearch
	docker rm bench_elasticsearch bench_kibana > /dev/null 2>&1 || true
	sleep 5
	docker network rm bench_elasticsearch > /dev/null 2>&1 || true
	rm -rf ./elasticsearch && mkdir elasticsearch && touch elasticsearch/.gitkeep

clean: clean-influxdb clean-elasticsearch

bench-ingestion-influxdb:
	php ./src/influxdb/ingestion.php

bench-ingestion-elasticsearch:
	php ./src/elasticsearch/ingestion.php

bench-response-time-influxdb:
	php ./src/influxdb/response-time.php

bench-response-time-elasticsearch:
	php ./src/elasticsearch/response-time.php

bench-disk-usage-influxdb:
	du -sh ./influxdb/data/rio

bench-disk-usage-elasticsearch:
	curl -s -XGET "http://dev.test:19200/_cat/indices?v" | grep statistic
