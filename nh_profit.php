<?php
/*
 *   Nicehash Profit Report
 *
 *   Written using PHP 7.1.3 with charting lib from highcharts.com
 *   Haven't testing under php 5.x but should work if ICONV is supported
 *
 *   To use: 1) Modify vars under "User Defined" section below
 *           2) Execute: php nh_profit.php          (script uses log.txt as default input)
 *                or     php nh_profit.php log1.txt      
 *           3) Open in your browser "index.html"
 */

# User Defined
$pathToLogs = 'E:\\coin\\NHML-1.8.1.3\\logs\\';
$pathToHtml = 'E:\\nh\\';
$defaultLog = 'log.txt';

if (!is_dir($pathToLogs)) {
    echo "\nNH_PROFIT: {$pathToLogs} - directory does not exist\n\n";
    exit;
}

$useThisLog = isset($argv[1]) ? $argv[1] : $defaultLog;

if (!file_exists($pathToLogs . $useThisLog)) {
    echo "\nNH_PROFIT: {$pathToLogs}{$useThisLog} - file does not exist\n\n";
    exit;
}

if (!is_dir($pathToHtml)) {
    echo "\nNH_PROFIT: {$pathToHtml} - direcotry does not exist\n\n";
    exit;
}

# System defined
$handle     = fopen($pathToLogs . $useThisLog, 'r');
$btc        = 0;
$profit2    = array();
$allAlgos   = array();

if ($handle) {
    while (($buff = fgets($handle, 4096)) !== FALSE) {
        $buffr = trim(iconv("UCS-2", "ISO-8859-1", $buff));

        # Capture current bitcoin rate
        if (preg_match('/Current Bitcoin rate: (.*)/', $buffr, $matches)) {
            $btc = $matches[1];
        }

        if ($btc > 0) {
            # Capture date/time of this statistic section
            if (preg_match('/^\[(.*)\] \[INFO\].*Current device profits:/', $buffr, $matches)) {
                $datetime = $matches[1];
            }

            # Get name of device (GPU, CPU, etc)
            if (preg_match('/Profits for.*\((.*)\)/', $buffr, $matches)) {
                $device = str_replace(array(' ','#'), '_', $matches[1]);
                $profit = array();
            }

            # Save profit values
            if (preg_match('/PROFIT = (.*)\s+\(SPEED.*\[(.*)\]/', $buffr, $matches)) {
                $val          = $matches[1];
                $alg          = $matches[2];
                $profit[$alg] = $val;
            }

            # 
            if (preg_match('/MOST PROFITABLE ALGO: (.*), PROFIT: (.*)$/', $buffr, $matches)) {

                # Only show first 100,000 entries
                if ($ct[$device] < 100000) {
                    if (!isset($allAlgos[$device])) {
                        ksort($profit);

                        foreach ($profit as $k => $v) {
                            $allAlgos[$device][] = "'{$k}'";
                        }                        
                    }

                    $microsec = strtotime($datetime) * 1000;

                    foreach ($profit as $k => $v) {
                        $profit2[$device][$k][] = "[{$microsec}," . number_format((float)$v * $btc, 2, '.', '') * 1 . ']';
                    }
                }
            }
        }
    }

    if (!feof($handle)) {
        echo "Error: unexpected fgets() fail\n";
        exit;
    }

    # Now build HTML files

    file_put_contents($pathToHtml . 'index.html',"<p><h2>Nicehash Algo Profitability</h2><p>");

    foreach ($profit2 as $dev => $devProp) {
        file_put_contents($pathToHtml . 'index.html',"<a href=\"{$dev}.html\">{$dev}</a><br>", FILE_APPEND);

        $html = "<!DOCTYPE html>
<script src=\"https://code.jquery.com/jquery-3.1.1.min.js\"></script>
<script src=\"https://code.highcharts.com/stock/highstock.js\"></script>
<script src=\"https://code.highcharts.com/stock/modules/exporting.js\"></script>
<div id=\"container\" style=\"height: 400px; min-width: 310px\"></div>
<script language='javascript'>
var seriesOptions = [];
var names = [" . implode(',', $allAlgos[$dev]) . "];

function createChart() {
    Highcharts.stockChart('container', {
        rangeSelector: {
            buttons: [{
                count: 1,
                type: 'hour',
                text: '1H'
            }, {
                count: 2,
                type: 'hour',
                text: '2H'
            }, {
                count: 4,
                type: 'hour',
                text: '4H'
            }, {
                count: 8,
                type: 'hour',
                text: '8H'
            }, {
                count: 12,
                type: 'hour',
                text: '12H'
            }, {
                count: 1,
                type: 'day',
                text: '1D'
            }, {
                count: 1,
                type: 'week',
                text: '1W'
            }, {
                type: 'all',
                text: 'All'
            }],
            inputEnabled: false,
            selected: 0
        },

        exporting: {
            enabled: true
        },

        yAxis: {
            title: {
                text: 'USD'
            }
        },

        title: {
            text: 'Nicehash Algo Profitability'
        },

        tooltip: {
            pointFormat: '<span style=\"color:{series.color}\">{series.name}</span>: <b>{point.y}</b> ({point.change}%)<br/>',
            valueDecimals: 2,
            split: true
        },

        series: seriesOptions
    });
}
";
        $ct = -1;

        foreach ($devProp as $algo => $data) {
            ++$ct;
            $html .= "
seriesOptions[{$ct}] = {
    name: \"{$algo}\",
    data: [" . implode(',', $data) . "]
};
";
        }

        $html .= "createChart();
</script>
";

        file_put_contents("{$pathToHtml}{$dev}.html", $html); # write device stats
    }
}
