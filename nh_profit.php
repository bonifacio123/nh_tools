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

# Load settings
clearstatcache();

if (!file_exists('settings_profit.json')) {
    echo "\nNH_BENCHMARK: settings_profit.json - file does not exist in same location as PHP script\n\n";
    exit;    
}

$json     = file_get_contents('settings_profit.json');
$settings = json_decode($json, TRUE);

if (NULL == $settings) {
    echo "\nNH_BENCHMARK: Invalid or corrupt settings_profit.json file\n\n";
    exit;    
}

# Check that paths and files exist
if (!is_dir($settings['pathToNicehashLogsDir'])) {
    echo "\nNH_PROFIT: {$settings['pathToNicehashLogsDir']} - directory does not exist\n\n";
    exit;
}

$useThisLog = isset($argv[1]) ? $argv[1] : $settings['defaultNicehashLogFile'];

if (!file_exists($settings['pathToNicehashLogsDir'] . $useThisLog)) {
    echo "\nNH_PROFIT: {$settings['pathToNicehashLogsDir']}{$useThisLog} - file does not exist\n\n";
    exit;
}

if (!is_dir($settings['pathToHtmlDir'])) {
    echo "\nNH_PROFIT: {$settings['pathToHtmlDir']} - direcotry does not exist\n\n";
    exit;
}

# System defined
$handle       = fopen($settings['pathToNicehashLogsDir'] . $useThisLog, 'r');
$btc          = 0;
$profit2      = array();
$allAlgos     = array();
$globalProfit = array();

if ($handle) {
    while (($buff = fgets($handle, 4096)) !== FALSE) {
        $buffr = trim(iconv("UCS-2", "ISO-8859-1", $buff));

        # Grab global profitability details if available
        if (FALSE !== strpos($buffr, 'Current Global profit:') &&
            FALSE === strpos($buffr, 'IS PROFITABLE') &&
            preg_match('/Current Global profit: ([.0-9]*) /', $buffr, $matches)) {
                $microsec = strtotime($datetime) * 1000;
                if ($microsec > 1234) {
                    $globalProfit[] = "[{$microsec},{$matches[1]}]";
                }
        }

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
            if (preg_match('/Profits for (.*) \((.*)\)/', $buffr, $matches)) {
                $device = $matches[1] . '|' . str_replace(array(' ','#'), '_', $matches[2]);
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

    # Create INDEX.HTML menu
    $fileName = $settings['pathToHtmlDir'] . 'index.html';

    file_put_contents($fileName, "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\" \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd\">
<html xmlns=\"http://www.w3.org/1999/xhtml\">
<p><h2>Nicehash Tools - Profits - v1.1.0 - <a href=\"https://github.com/bonifacio123/nh_tools\" target=\"_blank\">GitHub</a></h2><p>
<a href=\"global_profits.html\" title=\" For all devices \">Global Profits</a>
<br><br><h3>Algo Profits</h3>
");

    # Build HTML content
    foreach ($profit2 as $dev => $devProp) {
        list($deviceID, $deviceName) = explode('|', $dev);

        file_put_contents($fileName, "<a href=\"{$deviceName}.html\" title=\" {$deviceID} \">{$deviceName}</a> - {$deviceID}<br>\n", FILE_APPEND);

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
            text: 'Nicehash Algo Profitability<br>{$deviceName}<br>{$deviceID}'
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

        file_put_contents("{$settings['pathToHtmlDir']}{$deviceName}.html", $html); # write device stats
    }
}

# Create Global Profits detail

$html = "<!DOCTYPE html>
<script src=\"https://code.jquery.com/jquery-3.1.1.min.js\"></script>
<script src=\"https://code.highcharts.com/stock/highstock.js\"></script>
<script src=\"https://code.highcharts.com/stock/modules/exporting.js\"></script>

<div id=\"container\" style=\"height: 400px; min-width: 310px\"></div>
<script language='javascript'>
function chart (seriesData) {
    // Create the chart
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

        title: {
            text: 'Nicehash Global Profit'
        },

        yAxis: {
            title: {
                text: 'USD'
            }
        },

        series: [{
            name: 'USD',
            data: seriesData,
            tooltip: {
                valueDecimals: 2
            }
        }]
    });
}

var data = [
" . implode(",\n", $globalProfit) . "
];

chart(data);
</script>
";

file_put_contents("{$settings['pathToHtmlDir']}global_profits.html", $html);

# Finished

echo "\nnh_profit: HTML files created\n\n";
