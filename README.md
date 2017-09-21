# nh_tools

Some tools to process Nicehash logs

##Requirements
Although written using PHP 7.1.3 on Windows 10 it should work with PHP 5.x or Linux.

## Included Tools
- **nh_benchmarks.php:** Exports the calculated algo speeds to a csv file
- **nh_profit.php:** HTML representation of algo profits using the highcharts library

## Installing
* Download a zip copy of the source code using "Clone or download" button on the GitHub page.
* Extract the zip archive
* Update the two settings json files using the correct paths for your installation

## Usage: nh_profit
To generate the profit HTML files:

`
php nh_profit.php
`

To generate the profit HTML files using a log file other than the default one specified in the settings_profit.json file:

`
php nh_profit.php log.1.txt
`

Then using a web browser open the index.html file saved in the location specified in your settings_profit.json file.

## Usage: nh_benchmarks

To create the benchmarks CSV file:

`
php nh_benchmarks.php
`

Or to create the benchmarks CSV with a different file name other than the default one defined in settings_benchmarks.json file:

`
php nh_benchmarks.php algo_stats.csv
`

Then, Microsoft Excel or Open Office Calc, open the CSV file saved in the location specified in your settings_profit.json file.

## Sample Output

#### Benchmarks
###### (CPU & GPU listed in the order presented in the Nicehash log files)

[![nh_benchmarks_example.png](https://s26.postimg.org/yxbj2ek61/nh_benchmarks_example.png)](https://postimg.org/image/5ux8zkxw5/)

#### Profits Main Menu

[![nh_profit_menu_screen.png](https://s26.postimg.org/y1f1myahl/nh_profit_menu_screen.png)](https://postimg.org/image/go4r83f6d/)

#### Profits Detail

[![nh_profit_detail_graph.png](https://s26.postimg.org/yi099dy8p/nh_profit_detail_graph.png)](https://postimg.org/image/7wxqdtvv9/)
