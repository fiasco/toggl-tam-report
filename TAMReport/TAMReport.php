<?php

namespace TAMReport;

use GuzzleHttp\Client;

class TAMReport {

  protected $api;

  protected $accounts;

  protected $dateRangeStart;

  protected $dateRangeFinish;

  protected $apiUrl = 'https://www.toggl.com/reports/api/v2/summary';

  protected $reportData = NULL;

  protected $cacheFileStore = '/tmp';

  public function __construct(Array $api, Array $accounts) {
    $this->api = $api;
    $this->accounts = $accounts;

    if (!isset($api['timezone'])) {
      $this->api['timezone'] = 'UTC';
    }

    date_default_timezone_set($this->api['timezone']);
  }

  public function getAPI() {
    return $this->api;
  }

  public function setDateRange(\DateTime $start, \DateTime $finish) {
    $this->dateRangeStart = $start;
    $this->dateRangeFinish = $finish;

    $multiplier = $finish->format('n') - $start->format('n');

    foreach ($this->accounts as &$account) {
      $account['timeframe_hours'] = $account['monthly_hours'] * $multiplier;
    }
    return $this;
  }

  public function getDateRange() {
    return (object) array(
      'start' => $this->dateRangeStart,
      'finish' => $this->dateRangeFinish,
    );
  }

  protected function cacheFilename() {
    $cid = serialize(array($this->getAccounts(), $this->getAPI()));
    $cid = substr(md5($cid), 8);
    $name = strtr('TAMReport-@start-@finish-@cid', array(
      '@start' => $this->dateRangeStart->format('YmdH'),
      '@finish' => $this->dateRangeFinish->format('YmdH'),
      '@cid' => $cid,
    ));
    return $this->cacheFileStore . '/' . $name . '.data';
  }

  protected function cacheSet($data) {
    return file_put_contents($this->cacheFilename(), serialize($data));
  }

  protected function cacheGet() {
    if (!file_exists($this->cacheFilename())) {
      return FALSE;
    }
    if ($data = file_get_contents($this->cacheFilename())) {
      return unserialize($data);
    } 
    return FALSE;
  }

  public function queryData() {
    if (!empty($this->reportData)) {
      return $this->reportData;
    }
    if (!$this->reportData = $this->cacheGet()) {
      $query_parameters = $this->buildQuery();

      $token = base64_encode($this->api['token'] . ':api_token');

      $client = new Client();
      $response = $client->get($this->apiUrl . '?' . http_build_query($query_parameters), [
          'headers' =>  ['Authorization' => 'Basic ' . $token],
      ]);
      $this->reportData = $response->json();
      $this->reportData['date_queried'] = time();
      $this->cacheSet($this->reportData);
    }
    return $this->reportData;
  }

  protected function buildQuery() {
    $parameters = array(
      'workspace_id' => $this->api['workspace_id'],
      'user_agent' => $this->api['user_agent'],
      'billable' => 'yes',
      'display_hours' => 'decimal',
      'since' => $this->dateRangeStart->format(\DateTime::ISO8601),
      'until' => $this->dateRangeFinish->format(\DateTime::ISO8601),
      'client_ids' => implode(',', array_map(function ($account) {
        return $account['client_id'];
      }, $this->accounts)),
    );

    if (isset($this->api['user_ids'])) {
      $parameters['user_ids'] = $this->api['user_ids'];
    }
    return $parameters;
  }

  protected function getAccounts() {
    $accounts = array();
    foreach ($this->accounts as $account) {
      $accounts[$account['name']] = $account;
    } 
    return $accounts;
  }

  public function getTableRows() {
    if (empty($this->reportData)) {
      throw new \Exception("No report data to build query from");
    }
    $accounts = $this->getAccounts();
    $rows = array();
    $latest_day = new \DateTime();
    if ($latest_day->getTimestamp() > $this->dateRangeFinish->getTimestamp()) {
      $latest_day = clone $this->dateRangeFinish;
    }
    foreach ($accounts as $name => $account) {
      $rows[$name] = array(
        'name' => $name,
        'allocated' => $account['timeframe_hours'],
        'project' => array(),
        'used' => 0,
        'provisioned' => ($this->workingDays($latest_day) / $this->workingDays($this->dateRangeFinish)) * $account['timeframe_hours'],
        'completed' => 0,
      );
    }
    foreach ($this->reportData['data'] as $data_row) {
      $account = $accounts[$data_row['title']['client']];
      $row = &$rows[$account['name']];
      $row['project'][] = $data_row['title']['project'];
      $row['used'] += ($data_row['time'] / 1000 / 60 / 60);
      $row['completed'] = $row['used'] / $row['allocated'];
    }

    foreach ($rows as &$row) {
      $row['project'] = implode(', ', $row['project']);
    }
    return $rows;
  }

  protected function workingDays($end = NULL) {
    $day = clone $this->dateRangeStart;
    $end = empty($end) ? $this->dateRangeFinish : $end;
    $working_days = 0;
    while ($day->getTimestamp() < $end->getTimestamp()) {
      if ($day->format('N') < 6) {
        $working_days++; 
      }
      $day->setTimestamp($day->getTimestamp() + (60 * 60 * 24));
    }
    return $working_days;
  }
}

