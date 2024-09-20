<!-- Main Common Content Block -->
<div class="main-common-block">
    <!-- Main Header -->
    <div class="main-common-header">
        <h2>{$title|default:" "}</h2>
    </div>
    <!-- /Main Header -->

    <script type="text/javascript">
    function draw24HoursChart() {
        datevalue = $('#24-hours-active-campaigns input').val();
        jQuery.post(
            '',
            {
                'action': '24-hours-chart-data',
                'date': datevalue,
                'csrftoken': '{$smarty.session.csrftoken|default:''}'
            },
            function(campaignPerHours) {
                var data = new google.visualization.DataTable();
                data.addColumn('number', 'Time of Day');
                data.addColumn('number');
                data.addColumn({ type: 'string', role: 'tooltip', p: { 'html': true } });
    
                details = {};
                $.each(campaignPerHours, function(i, v) {
                    tooltip = '<div style="margin: 10px;"><ul style="margin-left: 10px;">';
                    $.each(v, function(cId, cDetails) {
                        details[cId] = cDetails;
                        tooltip += '<li><a href="/admin_listcampaign.php?id=' + cId + '" target="_blank">' + cDetails.name + '</a> (' + cId + ')</li>';
                    });
                    tooltip += '</ul></div>';
                    data.addRow([i + 0.5, Object.keys(v).length, tooltip]);
                });

                $detailsContainer = $('#24-hours-active-campaigns .details');
                $detailsContainer.find('.dynamic').remove();
                $detailsContainer.prepend('<p class="dynamic">' + Object.keys(details).length + ' unique campaigns</p>');
                $detailsTable = $detailsContainer.find('table > tbody');
                $.each(details, function(cId, cDetails) {
                    $detailsTable.prepend(
                        '<tr class="dynamic"><td>' +
                            cId +
                        '</td><td>' +
                            '<a href="/admin_listcampaign.php?id=' + cId + '" target="_blank">' + cDetails.name + '</a>' +
                        '</td><td>' +
                            '<a href="/admin_listgroup.php?id=' + cDetails.groupowner + '" target="_blank">' + cDetails.groupownername + '</a> (' + cDetails.groupowner + ')' +
                        '</td></tr>'
                    );
                });

                var options = {
                    title: 'Active running campaigns by hour for ' + (datevalue ? datevalue : 'today'),
                    bar: { groupWidth: '90%' },
                    legend: 'none',
                    tooltip: { isHtml: true ,  trigger: 'selection' },
                    hAxis: {
                        title: 'Time of Day',
                        minValue: 0,
                        maxValue: 24,
                        gridlines: {
                            count: 24
                        }
                    },
                    vAxis: {
                        title: 'Campaigns',
                        minValue: 0,
                        gridlines: { count: data.getColumnRange(1).max + 1 }
                    }
                };
                var chart = new google.visualization.ColumnChart(
                    document.getElementById('24-hours-active-campaigns-chart')
                );
                chart.draw(data, options);
            },
            'json'
        );
    }

    $(document).ready(function() {
        draw24HoursChart();
        $('#24-hours-active-campaigns input').datepicker().on('change', function(e){
            draw24HoursChart();
        });
    });
    </script>

                      
                                        
    <div id="24-hours-active-campaigns" style="min-height: 500px;">
        <h3 class="secondary-header">
            Active running campaigns by date
        </h3>
        <div class="datepair">
            date: <input type="text" class="date start" style="width: 80px;"/>
        </div>
        <div id="24-hours-active-campaigns-chart"></div>
        <div class="details">
            <table class="common-object-table" style="width: 100%;">
                <thead>
                    <tr>
                        <th>Campaign Id</th>
                        <th>Campaign Name</th>
                        <th>User Group</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
    <div class="clearfix"></div>
</div>
<script type="text/javascript" src="/js/datepair-20130405.js"></script>
