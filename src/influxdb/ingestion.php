<?php

require_once __DIR__ . '/../utils.php';

use InfluxDB\Client;
use InfluxDB\Database;
use InfluxDB\Point;

$database = Client::fromDSN('influxdb://dev.test:18086/rio');

$points = [];
foreach (generateFixtures(1, DURATION_LAST_YEAR, 100, 50) as $fixtures) {
    foreach ($fixtures as $point) {
        $points[] = new Point(
            'statistic',
            $point['value'],
            $point['tags'],
            [],
            $point['date']->getTimestamp()
        );
    }

    if (count($points) > 25000) {
        $database->writePoints($points, Database::PRECISION_SECONDS);
        $points = [];
    }
}

if ($points) {
    $database->writePoints($points, Database::PRECISION_SECONDS);
}
