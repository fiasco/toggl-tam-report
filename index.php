<?php
$loader = require_once 'vendor/autoload.php';
$loader->add('TAMReport\TAMReport', __DIR__);

use Symfony\Component\Yaml\Parser;
use TAMReport\TAMReport;

// This should be instansiated as their own objects.
// But meh.
$yaml = new Parser();
$accounts = $yaml->parse(file_get_contents('accounts.yml'));
$api = $yaml->parse(file_get_contents('api.yml'));

$report = new TAMReport($api, $accounts);

// Set date time range.
$start = new DateTime('01-' . date('m-Y'));
$finish = new DateTime('01-' . date('m-Y', strtotime('next month')));

$report->setDateRange($start, $finish)
       ->queryData();
$rows = $report->getTableRows();

function html_row_attribute($value, $compare) {
  if ($value > $compare) {
    return ' class="danger"';
  }
  if ($value == $compare) {
    return ' class="success"';
  }
  if (($value / $compare) > 0.75) {
    return ' class="warning"';
  }
  return '';
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
          ['Name', 'Used', 'Provisioned'],
          <?php foreach ($rows as $row): ?>
          ['<?php print $row['name'];?>', <?php print round($row['used'], 2); ?>, <?php print round($row['provisioned'], 2); ?>],
          <?php endforeach; ?>
          []
        ];

        data.pop();
        data = google.visualization.arrayToDataTable(data);

        var options = {
          // title: 'Company Performance',
          // vAxis: {title: 'Year',  titleTextStyle: {color: 'red'}}
          colors: ['#48BBCC', '#5B5C60']
        };

        var chart = new google.visualization.BarChart(document.getElementById('chart_div'));
        chart.draw(data, options);
      }
    </script> 
  </head>

  <body>
    <!--
    <div class="navbar navbar-inverse navbar-fixed-top" role="navigation">
      <div class="container-fluid">
        <div class="navbar-header">
          <button type="button" class="navbar-toggle" data-toggle="collapse" data-target=".navbar-collapse">
            <span class="sr-only">Toggle navigation</span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
          </button>
          <a class="navbar-brand" href="#">TAM Account Dashboard</a>
        </div>
        <div class="navbar-collapse collapse">
          <ul class="nav navbar-nav navbar-right">
            <li><a href="#">Dashboard</a></li>
            <li><a href="#">Settings</a></li>
            <li><a href="#">Profile</a></li>
            <li><a href="#">Help</a></li>
          </ul>
        </div>
      </div>
    </div>
    -->
    <div class="container-fluid">
      <div class="row">
        <div class="col-md-10 col-md-offset-1 main">
          <h1 class="page-header">Dashboard</h1>

          <div class="row">
            <div class="col-sm-12">
              <h4>TAM hours for <?php print date('F, Y'); ?></h4>
              <div id="chart_div" style="width: 100%; height: 350px;"></div>
              <p>Report generated at <?php print date('H:i:s \o\n F dS'); ?></p>
            </div>
          </div>

          <h2 class="sub-header"><?php print date('F'); ?> breakdown</h2>
          <div class="table-responsive">
            <table class="table table-striped">
              <thead>
                <tr>
                  <th>Client</th>
                  <th>Project</th>
                  <th>Hours/month</th>
                  <th>Burned</th>
                  <th>Burned %</th>
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
                </tr>
                <?php endforeach; ?>
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
