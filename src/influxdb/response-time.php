<?php

require_once __DIR__ . '/../utils.php';

use InfluxDB\Client;

$database = Client::fromDSN('influxdb://dev.test:18086/rio');

$hours = generateHours(100);

$start = microtime(true);
$total = count($hours);

foreach ($hours as $i => $hour) {
    $from = $hour->modify("-7 day")->format(DateTime::RFC3339);
    $to = $hour->format(DateTime::RFC3339);

    $result = $database->query("SELECT count(*) FROM statistic WHERE time > '$from' AND time <= '$to' GROUP BY statusCodeType,time(1h)");

    echo ($i + 1) . '/' . $total . "\n\r";
}

$end = microtime(true);
$executionTime = getTime($end - $start);

echo "[InfluxDB] Response time: $executionTime\r\n";
