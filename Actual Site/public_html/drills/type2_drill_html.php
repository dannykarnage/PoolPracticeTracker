<div class="history-data-display">
    <div class="history-data-separator-two-column">
        <div class="two-column-table-container">
            <table>
                <tbody>
                    <tr>
                        <th class="date-column-header">Date</th>
                        <th class="data-column-header">Score</th>
                    </tr>
                    <?php $odd = true; ?>
                    <?php for ($i = 0; $i < count($data["timestamp"]); $i++): ?>
                        <tr>
                            <?php if($odd): ?>
                                <?php $odd = false; ?>
                                <th class="date-column-odd">
                                    <?php echo htmlspecialchars(substr($data["timestamp"][$i], 0, 10)); ?>
                                </th>
                                <th class="data-column-odd">
                                    <?php echo htmlspecialchars($data["score"][$i]); ?>
                                </th>
                            <?php else: ?>
                                <?php $odd = true; ?>
                                <th class="date-column-even">
                                    <?php echo htmlspecialchars(substr($data["timestamp"][$i], 0, 10)); ?>
                                </th>
                                <th class="data-column-even">
                                    <?php echo htmlspecialchars($data["score"][$i]); ?>
                                </th>
                            <?php endif; ?>
                        </tr>
                    <?php endfor; ?>
                </tbody>
            </table>
        </div>

        <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
        <div id="chart_div" class="chart-container">
            <script>
                google.charts.load('current', {'packages':['corechart']});
                google.charts.setOnLoadCallback(drawChart);
            
                function drawChart()
                {
                    var data = new google.visualization.DataTable();
                    data.addColumn('datetime', 'Date');
                    data.addColumn('number', 'Score');

                    data.addRows([
                        <?php
                            $minDateStr = '';
                            $maxDateStr = '';
                            $dataRows = [];

                            for($i = 0; $i < count($data["timestamp"]); $i++)
                            {
                                $timestamp = $data["timestamp"][$i];
                                $score = $data["score"][$i];

                                // Extract date components for JavaScript Date object: new Date(Y, M-1, D, h, m, s)
                                $date_year = intval(substr($timestamp, 0, 4));
                                $date_month = intval(substr($timestamp, 5, 2)); // 1-12
                                $date_day = intval(substr($timestamp, 8, 2));
                                $date_hour = intval(substr($timestamp, 11, 2));
                                $date_minute = intval(substr($timestamp, 14, 2));
                                $date_second = intval(substr($timestamp, 17, 2));
                                
                                // Create the JavaScript Date constructor argument string
                                $datestr = $date_year . ', ' . ($date_month - 1) . ', ' . $date_day . ', ' . $date_hour . ', ' . $date_minute . ', ' . $date_second;
                                
                                $dataRows[] = "[new Date(" . $datestr . "), " . floatval($score) . "]";

                                // Set min/max date strings (logic retained from original, but protected)
                                if(count($data["timestamp"]) == 1)
                                {
                                    // Protect min/max against single-point data by adjusting time
                                    $minDateStr = $date_year . ', ' . ($date_month - 1) . ', ' . $date_day . ', ' . $date_hour . ', ' . $date_minute . ', ' . ($date_second - 1);
                                    $maxDateStr = $date_year . ', ' . ($date_month - 1) . ', ' . $date_day . ', ' . $date_hour . ', ' . $date_minute . ', ' . ($date_second + 1);
                                }
                                else
                                {
                                    if($i == 0)
                                    {
                                        $minDateStr = $datestr;
                                    }
                                    elseif($i == (count($data["timestamp"]) - 1))
                                    {
                                        $maxDateStr = $datestr;
                                    }
                                }
                            }
                            // *** SECURITY FIX: Echo the data rows separated by comma, already safe ***
                            echo implode(", ", $dataRows);
                        ?>
                    ]);

                    var options = {
                        width: 600,
                        height: 400,
                        legend: {position: 'none'},
                        pointSize: 5,
                        enableInteractivity: true,
                        chartArea: {
                            width: '90%'
                        },
                        hAxis: {
                            viewWindow: {
                                // *** SECURITY FIX: Use json_encode() to safely embed PHP strings in JS context ***
                                min: new Date(<?php echo json_encode($minDateStr); ?>),
                                max: new Date(<?php echo json_encode($maxDateStr); ?>)
                            },
                            gridlines: {
                                count: -1,
                                units: {
                                    days: {format: ['MMM dd']},
                                    hours: {format: ['HH:mm', 'ha']},
                                }
                            },
                            minorGridlines: {
                                units: {
                                    hours: {format: ['hh:mm:ss a', 'ha']},
                                    minutes: {format: ['HH:mm a Z', ':mm']}                                    
                                }
                            }
                        },
                        vAxis: {
                            // *** SECURITY FIX: Use json_encode() to safely embed PHP numbers in JS context ***
                            minValue: <?php echo json_encode(floatval($min_score)); ?>,
                            maxValue: <?php echo json_encode(floatval($max_score)); ?>
                        }
                    };
                    
                    var chart = new google.visualization.LineChart(
                    document.getElementById('chart_div'));
                    chart.draw(data, options);
                }
            </script>
        </div>
    </div>
</div>
