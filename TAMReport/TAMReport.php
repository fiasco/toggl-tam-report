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

  public function __construct(Array $api, Array $accounts) {
    $this->api = $api;
    $this->accounts = $accounts;

    if (!isset($api['timezone'])) {
      $this->api['timezone'] = 'UTC';
    }

    date_default_timezone_set($this->api['timezone']);
  }

  public function setDateRange(\DateTime $start, \DateTime $finish) {
    $this->dateRangeStart = $start;
    $this->dateRangeFinish = $finish;
    return $this;
  }

  public function queryData() {

    $query_parameters = $this->buildQuery();

    $token = base64_encode($this->api['token'] . ':api_token');

    $client = new Client();
    $response = $client->get($this->apiUrl . '?' . http_build_query($query_parameters), [
        'headers' =>  ['Authorization' => 'Basic ' . $token],
    ]);
    return $this->reportData = $response->json();
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
    foreach ($this->reportData['data'] as $data_row) {
      $account = $accounts[$data_row['title']['client']];
      $row = array(
        'name' => $account['name'],
        'project' => $data_row['title']['project'],
        'allocated' => $account['monthly_hours'],
        'used' => $data_row['time'] / 1000 / 60 / 60,
      );
      $row['provisioned'] = ($this->workingDays(new \DateTime()) / $this->workingDays($this->dateRangeFinish)) * $row['allocated'];
      $row['completed'] = $row['used'] / $row['allocated'];

      $rows[] = $row;
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

