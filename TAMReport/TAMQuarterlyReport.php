<?php

namespace TAMReport;

class TAMQuarterlyReport extends TAMReport {

  protected $currentMonth;

  public function __construct(TAMReport $monthly) {
    $this->currentMonth = $monthly;

    parent::__construct($monthly->getAPI(), $monthly->getAccounts());

    $range = $monthly->getDateRange();
    $start = clone $range->start;
    $start->modify('-' . (($start->format('n') % 3) - 1) . ' months');
    $finish = clone $start;
    $finish->modify('+3 months');
    $this->setDateRange($start, $finish);
  }

  public function queryData() {
    $this->currentMonth->queryData();
    return parent::queryData();
  }  

  public function getTableRows() {
    $quarter_data = parent::getTableRows();
    $month_data = $this->currentMonth->getTableRows();

    foreach ($month_data as $name => $row) {
      $month_data[$name]['quarter_used'] = $quarter_data[$name]['used'];
      $month_data[$name]['quarter_provisioned'] = $quarter_data[$name]['provisioned'];
      $month_data[$name]['quarter_completed'] = $quarter_data[$name]['completed'];
    }
    return $month_data;
  }

}
