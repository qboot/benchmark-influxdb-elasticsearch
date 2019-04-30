<?php

require_once __DIR__ . '/../vendor/autoload.php';

const INFLUXDB = 'InfluxDB';
const ELASTICSEARCH = 'Elasticsearch';

const STATUS_CODES_TYPE_INFORMATION = '1xx';
const STATUS_CODES_TYPE_SUCCESS = '2xx';
const STATUS_CODES_TYPE_REDIRECTION = '3xx';
const STATUS_CODES_TYPE_CLIENT_ERROR = '4xx';
const STATUS_CODES_TYPE_SERVER_ERROR = '5xx';
const STATUS_CODES_TYPE_UNKNOWN = '6xx';

const STATUS_CODES = [
    STATUS_CODES_TYPE_INFORMATION,
    STATUS_CODES_TYPE_SUCCESS,
    STATUS_CODES_TYPE_REDIRECTION,
    STATUS_CODES_TYPE_CLIENT_ERROR,
    STATUS_CODES_TYPE_SERVER_ERROR,
    STATUS_CODES_TYPE_UNKNOWN,
];

const DURATION_LAST_DAY = 24;
const DURATION_LAST_MONTH = 24 * 31;
const DURATION_LAST_YEAR = 24 * 31 * 12;

$statusCodes = array_map(function($statusCode) {
    return (int) $statusCode;
}, file(__DIR__ . '/fixtures/status-codes.txt', FILE_IGNORE_NEW_LINES));
$userAgents = file(__DIR__ . '/fixtures/user-agents.txt', FILE_IGNORE_NEW_LINES);
$project = uuid();

function findStatusCodeType(int $statusCode): string
{
    $search = ((string) $statusCode)[0];

    foreach(STATUS_CODES as $type) {
        if ($type[0] === $search) {
            return $type;
        }
    }
}

function getRandomValue(array $array = []): string
{
    return $array[rand(0, count($array) - 1)];
}

function uuid(): string
{
    $data = random_bytes(16);
    $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
    $data[8] = chr(ord($data[8]) & 0x3f | 0x80);

    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}

function generatePoint(string $project = null, DateTimeImmutable $hour = null): array
{
    global $statusCodes, $userAgents;

    if (!$project) {
        global $project;
    }

    if (!$hour) {
        $hour = generateHour();
    }

    $statusCode = getRandomValue($statusCodes);
    $userAgent = getRandomValue($userAgents);

    $statusCodeType = findStatusCodeType((int) $statusCode);

    return [
        'measurement' => 'statistic',
        'value' => rand(0, 1000000),
        'tags' => [
            'project' => $project,
            'statusCode' => $statusCode,
            'statusCodeType' => $statusCodeType,
            'userAgent' => substr($userAgent, 0, 25),
            'userAgentType' => rand(1, 4),
        ],
        'date' => $hour,
    ];
}

function generatePoints(string $project = null, DateTimeImmutable $hour = null, int $size = 10): array
{
    $points = [];
    for ($i = 0; $i < $size; $i++) {
        $points[] = generatePoint($project, $hour);
    }

    return $points;
}

function generateProjects(int $size = 10): array
{
    $projects = [];
    for ($i = 0; $i < $size; $i++) {
        $projects[] = uuid();
    }

    return $projects;
}

function generateHour(): DateTimeImmutable
{
    $today = new DateTimeImmutable();
    return $today->setTime((int) $today->format('H'), 0);
}

function generateHours(int $type = DURATION_LAST_DAY)
{
    $hours = [];
    for ($i = 0; $i < $type; $i++) {
        $hours[] = generateHour()->modify("-$i hour");
    }

    return $hours;
}

function generateFixtures(int $projectCount = 1, int $duration = DURATION_LAST_DAY, int $perHourUserAgentsCount = 10, int $perHourStatusCodesCount = 5, string $tsdb = INFLUXDB): Iterator
{
    $projects = generateProjects($projectCount);
    $hours = generateHours($duration);
    $perHourPointsCount = $perHourUserAgentsCount * $perHourStatusCodesCount;

    $cmp = 0;
    $length = $duration * $perHourPointsCount;
    $start = microtime(true);

    foreach($projects as $project) {
        foreach ($hours as $hour) {
            yield generatePoints($project, $hour, $perHourPointsCount);

            $loop = microtime(true);
            $time = getTime($loop - $start);
            echo "[$tsdb] Ingestion: $cmp/$length [$time]\r\n";
            $cmp += $perHourPointsCount;
        }
    }
}

function getTime(int $seconds = 0): string
{
    $hours = floor($seconds / 3600);
    $minutes = floor(($seconds / 60) % 60);
    $seconds = $seconds % 60;

    return "$hours:$minutes:$seconds";
}
