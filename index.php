<?php
$loader = require_once 'vendor/autoload.php';
$loader->add('TAMReport', __DIR__);

use Symfony\Component\Yaml\Parser;
use TAMReport\TAMReport;
use TAMReport\TAMQuarterlyReport;

// This should be instansiated as their own objects.
// But meh.
$yaml = new Parser();
$accounts = $yaml->parse(file_get_contents('accounts.yml'));
$api = $yaml->parse(file_get_contents('api.yml'));

$monthly_report = new TAMReport($api, $accounts);
$quarterly_report = new TAMReport($api, $accounts);

$current_month = time();
if (!empty($_GET['month']) && is_numeric($_GET['month'])) {
  $current_month = strtotime($_GET['month'] . ' months'); 
}

// Set date time range.
$start = new DateTime('01-' . date('m-Y', $current_month));
$finish = new DateTime('01-' . date('m-Y', strtotime('next month', $current_month)));

$monthly_report->setDateRange($start, $finish);

$quarterly_data = new TAMQuarterlyReport($monthly_report);
$report = $quarterly_data->queryData();
$rows = $quarterly_data->getTableRows();

function html_row_attribute($value, $compare) {
  $target = ($value / $compare);
  if (($target > 0.8) && ($target < 1.2)) {
    return ' class="success"';
  }
  if (($target > 0.5) && ($target < 1.5)) {
    return ' class="warning"';
  }
  return ' class="danger"';
}

?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="">
    <meta name="author" content="">
    <link rel="icon" href="favicon.ico">

    <title>TAM Account Dashboard</title>

    <!-- Bootstrap core CSS -->
    <link href="//maxcdn.bootstrapcdn.com/bootstrap/3.1.1/css/bootstrap.min.css" rel="stylesheet">

    <!-- HTML5 shim and Respond.js IE8 support of HTML5 elements and media queries -->
    <!--[if lt IE 9]>
      <script src="https://oss.maxcdn.com/html5shiv/3.7.2/html5shiv.min.js"></script>
      <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
    <![endif]-->

    <script type="text/javascript" src="https://www.google.com/jsapi"></script>
    <script type="text/javascript">
      google.load("visualization", "1", {packages:["corechart"]});
      google.setOnLoadCallback(drawChart);
      function drawChart() {
        var data = [
          ['Name', 'Used', 'Provisioned', 'Allocated'],
          <?php foreach ($rows as $row): ?>
          ['<?php print $row['name'];?>', <?php print round($row['used'], 2); ?>, <?php print round($row['provisioned'], 2); ?>, <?php print round($row['allocated'], 2); ?>],
          <?php endforeach; ?>
          []
        ];

        data.pop();
        data = google.visualization.arrayToDataTable(data);

        var options = {
          // title: 'Company Performance',
          // vAxis: {title: 'Year',  titleTextStyle: {color: 'red'}}
          colors: ['#6DC39A', '#005C74', '#102C47']
        };

        var chart = new google.visualization.BarChart(document.getElementById('chart_div'));
        chart.draw(data, options);
      }
    </script> 
  </head>

  <body>
    <div class="container-fluid">
      <div class="row">
        <div class="col-md-10 col-md-offset-1 main">
          <h1 class="page-header">Dashboard</h1>
          <div class="dropdown">
            <button class="btn btn-default dropdown-toggle" type="button" id="dropdownMenu1" data-toggle="dropdown">
              Select reporting month
              <span class="caret"></span>
            </button>
            <ul class="dropdown-menu" role="menu" aria-labelledby="dropdownMenu1">
              <?php for ($i=0; $i > -12; $i--): ?>
              <li role="presentation" <?php if (date('m-Y', strtotime($i . ' months')) == date('m-Y', $current_month)) { echo 'class="active"'; } ?>>
                <a role="menuitem" tabindex="-1" href="?month=<?php print $i; ?>"><?php print date('F Y', strtotime($i . ' months')); ?></a>
              </li>
              <?php endfor; ?>
            </ul>
          </div>
          <div class="row">
            <div class="col-sm-12">
              <h4>TAM hours for <?php print $start->format('F, Y'); ?></h4>
              <div id="chart_div" style="width: 100%; height: <?php print count($rows) * 70; ?>px;"></div>
              <p>Report generated at <?php print date('H:i:s \o\n F dS', $report['date_queried']); ?></p>
            </div>
          </div>

          <h2 class="sub-header"><?php print $start->format('F'); ?> breakdown</h2>
          <div class="table-responsive">
            <table class="table table-striped">
              <thead>
                <tr>
                  <th>Client</th>
                  <th>Project</th>
                  <th>Hours/month</th>
                  <th>Burned/month</th>
                  <th>Burned %/month</th>
                  <th>Burned/quarter</th>
                  <th>Burned %/quarter</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($rows as $row): ?>
                <tr<?php print html_row_attribute($row['used'], $row['provisioned']); ?>>
                  <td><?php print $row['name']; ?></td>
                  <td><?php print $row['project']; ?></td>
                  <td><?php print $row['allocated']; ?></td>
                  <td><?php print round($row['used'], 2); ?></td>
                  <td><?php print round(($row['completed'] * 100), 2); ?>%</td>
                  <td<?php print html_row_attribute($row['quarter_used'], $row['quarter_provisioned']); ?>><?php print round($row['quarter_used'], 2); ?></td>
                  <td<?php print html_row_attribute($row['quarter_used'], $row['quarter_provisioned']); ?>><?php print round(($row['quarter_completed'] * 100), 2); ?>%</td>
                </tr>
                <?php endforeach; ?>
                <tr>
                  <th>Total</th>
                  <th></th>
                  <th><?php print $allocated = round(array_sum(array_column($rows, 'allocated')), 2); ?></th>
                  <th><?php print $used      = round(array_sum(array_column($rows, 'used')), 2); ?></th>
                  <th><?php print round(($used/$allocated) * 100, 2); ?>%</th>
                  <th><?php print $qused = round(array_sum(array_column($rows, 'quarter_used')), 2); ?></th>
                  <th><?php print round(($qused / array_sum(array_column($rows, 'quarter_provisioned'))) * 100, 2); ?>%</th>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

    <!-- Bootstrap core JavaScript
    ================================================== -->
    <!-- Placed at the end of the document so the pages load faster -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.1/jquery.min.js"></script>
    <script src="//maxcdn.bootstrapcdn.com/bootstrap/3.1.1/js/bootstrap.min.js"></script>
  </body>
</html>
