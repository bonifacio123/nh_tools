<?php
/*
 *   Nicehash Benchmark Export Benchmarks To CSV
 */

# Load settings
clearstatcache();

if (!file_exists('settings_benchmarks.json')) {
    echo "\nNH_BENCHMARK: settings_benchmarks.json - file does not exist in same location as PHP script\n\n";
    exit;    
}

$json     = file_get_contents('settings_benchmarks.json');
$settings = json_decode($json, TRUE);

if (NULL == $settings) {
    echo "\nNH_BENCHMARK: Invalid or corrupt settings_benchmarks.json file\n\n";
    exit;    
}

# Check that paths and files exist
$useThisCsv = isset($argv[1]) ? $argv[1] : $settings['defaultCsvFileName'];

if (!is_dir($settings['pathToNicehashConfigsDir'])) {
    echo "\nNH_BENCHMARK: {$settings['pathToNicehashConfigsDir']} - directory does not exist\n\n";
    exit;
}

if (!is_dir($settings['pathToCsvSaveDir'])) {
    echo "\nNH_BENCHMARK: {$settings['pathToCsvSaveDir']} - directory does not exist\n\n";
    exit;
}

$files = glob("{$settings['pathToNicehashConfigsDir']}benchmark_*");

if (count($files) < 1) {
    echo "\nNH_BENCHMARK: no benchmark files found in {$settings['pathToNicehashConfigsDir']}\n\n";
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
$fp = fopen($settings['pathToCsvSaveDir'] . $useThisCsv, 'w');

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

fputcsv($fp, $line, $settings['csvSeperator']);

# Write CSV algo detail
foreach ($algo as $algoName => $nul) {
    $line = array($algoName);

    foreach ($card as $uuid => $name) {
        $speed = isset($summ[$uuid][$algoName]) ? $summ[$uuid][$algoName] : 0;
        $line[] = $speed;
    }

    fputcsv($fp, $line, $settings['csvSeperator']);
}

fclose($fp);

echo "\nnh_benchmarks: CSV file created\n\n";
