<?php declare(strict_types=1);

namespace App\Charts;

use App\Charts\QuickChart;

class Charts
{
    // Format values are: svg, png, jpeg, webp

    // Radial Gauge good for measuring percentages or values out of max values
    public static function radialGauge(string $label, int $data, array $range = [0, 100], string|int $width = 250, string|int $height = 250, string $format = 'svg', bool $shortUrl = false) : string
    {
        $chart = new QuickChart([
            'width' => $width,
            'height' => $height,
            'format' => $format,
        ]);
        // Let's calculate how much of the range we are at, percentage wise
        $percentage = floor(($data / $range[1]) * 100);
        // Let's set the background color based on the percentage
        // If we are between 0 and 50, we are green, from 50 to 75 we are orange, from 75 to 80 we are crimson, from 80 to 100 we are red
        if ($percentage >= 0 && $percentage <= 25) {
            $background = 'getGradientFillHelper("horizontal", ["lime", "green"])';
        } elseif ($percentage > 25 && $percentage < 50) {
            $background = 'getGradientFillHelper("horizontal", ["yellow", "green"])';
        } elseif ($percentage >= 50 && $percentage < 75) {
            $background = 'getGradientFillHelper("horizontal", ["orange", "yellow"])';
        } elseif ($percentage >= 75 && $percentage <= 85) {
            $background = 'getGradientFillHelper("horizontal", ["yellow", "crimson"])';
        } elseif ($percentage > 85 && $percentage <= 100) {
            $background = 'getGradientFillHelper("horizontal", ["crimson", "red"])';
        } else {
            $background = 'getGradientFillHelper("horizontal", ["green", "lime"])';
        }

        $chart->setConfig('{
            type: "radialGauge",
            data: {
                datasets: [{
                    data: [' . $data . '],
                    backgroundColor: ' . $background . ',
                    borderWidth: 1,
                    borderColor: "rgba(0,0,0, 0.95)",
                    label: "' . $label . '",
                }]
            },
            options: {
                // See https://github.com/pandameister/chartjs-chart-radial-gauge#options
                domain: [' . implode(',', $range) . '],
                trackColor: "rgba(119,119,119, 0.95)",
                trackBorderWidth: 1,
                roundedCorners: false,
                legend: {},
                title: {
                    display: true,
                    text: "' . $label . '"
                },
                centerPercentage: 80,
                centerArea: {
                    fontSize: 16,
                    displayText: true,
                    text: (val) => val + "/" + ' . $range[1] . ' + "\n(' . $percentage . '%)",
                    subText: "",
                    padding: 4,
                    fontColor: \'#777\',
                },
                responsive: true,
                title: {
                    display: true,
                    fontSize: 18,
                    text: \'' . $label . '\',
                    color: \'#777\',
                    align: \'center\',
                    position: \'top\',
                    fullSize: false
                },
                legend: {
                    display: false,
                    position: \'right\',
                    align: \'top\',
                    labels: {
                        fontColor: \'#777\',
                        fontStyle: \'bold\',
                        fontSize: 14,
                        padding: 12
                    }
                },
            }
        }');
        return ($shortUrl) ?  '<figure class="m-1"><img src="' . $chart->getShortUrl() . '" title="' . $label . '" alt="' . $label . '" width="' . $width . '" height="' . $height . '"  /></figure>' : '<figure class="m-1"><img src="' . $chart->getUrl() . '" title="' . $label . '" alt="' . $label . '" width="' . $width . '" height="' . $height . '" /></figure>';
    }
    // Donut or Pie chart in one
    public static function doughnutOrPieChart(string $type, string $title, array $labels, array $data, string|int $width = 300, string|int $height = 300, string $format = 'svg', bool $shortUrl = false) : string
    {
        $chart = new QuickChart([
            'width' => $width,
            'height' => $height,
            'format' => $format,
        ]);

        $var = '';
        foreach ($labels as $entry) {
            $var .= "'$entry',";
        }
        // Put an If statement to change colors based on type or label
        $background_color_string = '[
            \'rgba(54, 162, 235, 1)\', // blue
            \'rgba(75, 192, 192, 1)\', // green
            \'rgba(255, 99, 132, 1)\', // red
            \'rgba(255, 159, 64, 1)\', // orange
            \'rgba(153, 102, 255, 1)\', // purple
            \'rgba(255, 206, 86, 1)\', // yellow
            \'rgba(255, 0, 0, 1)\', // bright red
            \'rgba(0, 255, 255, 1)\', // cyan
            \'rgba(255, 0, 255, 1)\', // magenta
            \'rgba(128, 128, 128, 1)\' // grey
        ]';


        $chart->setConfig('{
            type: "' . $type . '",
            data: {
                labels: [' . $var . '],
                datasets: [{
                    label: "' . $title . '",
                    backgroundColor: ' . $background_color_string . ',
                    data: [' . implode(",", $data) . '],
                    borderColor: \'rgba(0,0,0, 0.95)\',
                    borderWidth: 0,
                    weight: 600,
                    pointBackgroundColor: function(context) {
                        var index = context.dataIndex;
                        var value = context.dataset.data[index];
                        return value === \'DenyList\' ? \'green\' : \'blue\'
                    }
                }]
            },
            options: {
                    responsive: true,
                    title: {
                        display: true,
                        fontSize: 20,
                        text: \'' . $title . '\',
                        color: \'#777\',
                        align: \'center\',
                        position: \'top\',
                        fullSize: true
                    },
                    legend: {
                        display: true,
                        position: \'top\',
                        align: \'top\',
                        labels: {
                            fontColor: \'#777\',
                            fontStyle: \'bold\',
                            fontSize: 12,
                            padding: 12
                        }
                    },
                    plugins: {
                        doughnutlabel: {
                            labels: [
                                {
                                    text: \'' . array_sum($data) . '\',
                                    font: {
                                        size: \'30\',
                                        family: \'Arial, Helvetica, sans-serif\',
                                        weight: \'bold\'
                                    },
                                    backgroundColor: \'green\',
                                    color: \'#777\'
                                }
                            ]
                        },
                        datalabels: {
                            anchor: "center",
                            align: "center",
                            color: "white",
                            backgroundColor: "black",
                            borderColor: "black",
                            borderWidth: 1,
                            borderRadius: 6,
                            font: {
                                weight: \'bold\',
                                size: 12,
                            }
                        }
                    },
                }
            }');

        return ($shortUrl) ?  '<figure class="m-2"><img src="' . $chart->getShortUrl() . '" title="' . $title . '" alt="' . $title . '" width="' . $width . '" height="' . $height . '" /></figure>' : '<figure class="m-2"><img src="' . $chart->getUrl() . '" title="' . $title . '" alt="' . $title . '" width="' . $width . '" height="' . $height . '" /></figure>';
    }
    public static function lineChart(string $title, array $data, string|int $width, string|int $height, string $format, bool $shortUrl = false): string
    {
        $chart = new QuickChart([
            'width' => $width,
            'height' => $height,
            'format' => $format,
        ]);
        $backgroundColorArray = [
            'rgba(54, 162, 235, 1)',    // blue
            'rgba(75, 192, 192, 1)',    // green
            'rgba(255, 99, 132, 1)',    // red
            'rgba(255, 159, 64, 1)',    // orange
            'rgba(153, 102, 255, 1)',   // purple
            'rgba(255, 206, 86, 1)',    // yellow
            'rgba(255, 0, 0, 1)',       // bright red
            'rgba(0, 255, 255, 1)',     // cyan
            'rgba(255, 0, 255, 1)',     // magenta
            'rgba(128, 128, 128, 1)',   // grey
            'rgba(0, 128, 0, 1)',       // greenish
            'rgba(255, 165, 0, 1)',     // orange-yellow
            'rgba(0, 0, 255, 1)',       // pure blue
            'rgba(255, 140, 0, 1)',     // dark orange
            'rgba(148, 0, 211, 1)',     // dark violet
            'rgba(255, 69, 0, 1)',      // red-orange
            'rgba(0, 255, 0, 1)',       // pure green
            'rgba(255, 215, 0, 1)',     // gold
            'rgba(0, 255, 127, 1)',     // spring green
            'rgba(255, 20, 147, 1)',    // deep pink
        ];

        $datasets = $data['datasets'];

        // Calculate background colors dynamically based on the number of datasets
        $backgroundColors = array_slice($backgroundColorArray, 0, count($datasets));

        foreach ($datasets as $index => &$dataset) {
            $dataset['backgroundColor'] = $backgroundColors[$index];
            $dataset['borderColor'] = $backgroundColors[$index];
            $dataset['fill'] = false;
            $dataset['tension'] = 0.1;
        }

        $chart->setConfig('{
            type: "line",
            data: {
                labels: ' . json_encode($data['labels']) . ',
                datasets: ' . json_encode($datasets) . '
            },
            options: {
                responsive: true,
                title: {
                    display: true,
                    fontSize: 20,
                    text: \'' . $title . '\',
                    color: \'black\',
                    align: \'center\',
                    position: \'top\',
                    fullSize: true
                },
                legend: {
                    display: true,
                    position: \'right\',
                    align: \'start\',
                    fontSize: 9
                }
            }
        }');
        //var_dump($chart->getConfigStr());
        return ($shortUrl) ?  '<figure class="m-1"><img src="' . $chart->getShortUrl() . '" title="' . $title . '" alt="' . $title . '" /></figure>' : '<figure class="m-1"><img src="' . $chart->getUrl() . '" title="' . $title . '" alt="' . $title . '" /></figure>';
    }
}
