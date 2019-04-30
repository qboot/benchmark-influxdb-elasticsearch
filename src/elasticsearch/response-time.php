<?php

require_once __DIR__ . '/../utils.php';

use Elastica\Client;
use Elastica\Query;
use Elastica\Search;

$elasticaClient = new Client([
    'host' => 'dev.test',
    'port' => 19200,
]);

$search = new Search($elasticaClient);

$index = $elasticaClient->getIndex('statistic');
$search->addIndex($index);

$hours = generateHours(100);
$queryTime = 0;
$total = count($hours);

foreach ($hours as $i => $hour) {
    $from = $hour->modify("-7 day")->format(DateTime::RFC3339);
    $to = $hour->format(DateTime::RFC3339);

    $query = new Query([
        'size' => 0,
        'track_total_hits' => true,
        'query' => [
            'bool' => [
                'filter' => [
                    [
                        'range' => [
                            '@timestamp' => [
                                'gte' => $from,
                            ],
                        ],
                    ],
                    [
                        'range' => [
                            '@timestamp' => [
                                'lt' => $to,
                            ],
                        ],
                    ],
                ],
            ],
        ],
        'aggs' => [
            'data' => [
                'date_histogram' => [
                    'field' => '@timestamp',
                    'interval' => 'hour',
                    'order' => ['_key' => 'asc'],
                ],
                'aggs' => [
                    'data' => [
                        'terms' => [
                            'field' => 'statusCodeType',
                            'order' => ['_key' => 'asc'],
                        ]
                    ]
                ]
            ]
        ]
    ]);

    $search->setQuery($query);
    $result = $search->search();

    $queryTime += $result->getResponse()->getQueryTime();

    echo ($i + 1) . '/' . $total . "\r\n";
}

echo "[Elasticsearch] Response time: $queryTime\r\n";
