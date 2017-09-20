<?php
/*
 *   Nicehash Benchmark Export Benchmarks To CSV
 *
 *   Written using PHP 7.1.3
 *   Haven't testing under php 5.x but should work
 *
 *   To use: 1) Modify vars under "User Defined" section below
 *           2) Execute: php nh_benchmarks.php
 *           3) Open in excel benchmarks.csv
 */

# User Defined
$pathToConfigs = 'E:\\coin\\NHML-1.8.1.3\\configs\\';
$pathToCsv     = 'E:\\nh\\';
$defaultCsv    = 'benchmarks.csv';
$csvSeperator  = ',';

# Check that paths and files exist
$useThisCsv = isset($argv[1]) ? $argv[1] : $defaultCsv;

if (!is_dir($pathToConfigs)) {
    echo "\nNH_BENCHMARK: {$pathToConfigs} - directory does not exist\n\n";
    exit;
}

if (!is_dir($pathToCsv)) {
    echo "\nNH_BENCHMARK: {$pathToCsv} - directory does not exist\n\n";
    exit;
}

$files = glob("{$pathToConfigs}benchmark_*");

if (count($files) < 1) {
    echo "\nNH_BENCHMARK: no benchmark files found in {$pathToConfigs}\n\n";
    exit;
}

# Group benchmarks
$algo = array();
$summ = array();
$card = array();

foreach ($files as $fn) {
    $json          = file_get_contents($fn);
    $bench         = json_decode($json, TRUE);
    $device        = $bench['DeviceUUID'];
    $card[$device] = $bench['DeviceName'];

    foreach ($bench['AlgorithmSettings'] as $prop) {
        $algoName  = $prop['Name'];
        $algoSpeed = $prop['BenchmarkSpeed'];

        $summ[$device][$algoName] = $algoSpeed;

        $algo[$algoName] = 'X';
    }
}

ksort($algo);

# Open CSV file for writing
$fp = fopen($pathToCsv . $useThisCsv, 'w');

# Write CSV header
$line = array('Algo');
$gpu  = 0;
$cpu  = 0;

foreach ($card as $uuid => $name) {
    if ('GPU-' == substr($uuid, 0, 4)) {
        ++$gpu;
        $shortName = 'GPU#' . $gpu;
    } else {
        ++$cpu;
        $shortName = 'CPU#' . $cpu;
    }

    $line[] = "{$name}\n{$shortName}";
}

fputcsv($fp, $line, $csvSeperator);

# Write CSV algo detail
foreach ($algo as $algoName => $nul) {
    $line = array($algoName);

    foreach ($card as $uuid => $name) {
        $speed = isset($summ[$uuid][$algoName]) ? $summ[$uuid][$algoName] : 0;
        $line[] = $speed;
    }

    fputcsv($fp, $line, $csvSeperator);
}

fclose($fp);
