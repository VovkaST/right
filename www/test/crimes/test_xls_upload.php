<?php

$need_auth = 0;
require_once($_SERVER['DOCUMENT_ROOT'].'/sessions.php');

require_once(KERNEL.'Excel/reader.php');

$data = new Spreadsheet_Excel_Reader();
  $data->setOutputEncoding('utf-8');
  $data->read('Book.xls');
  //echo $data->sheets[1]['cells'][1][2];
  //print_r($data->sheets);
  $cols = $data->sheets[0]['numCols'];
  $cntr = 1;
  echo '<table rules="all" border="1">';
  for ($r = 1; $r <= $data->sheets[0]['numRows']; $r++) {
    echo '<tr>';
    echo '<td>'.$cntr++.'</td>';
    for ($c = 1; $c <= $cols; $c++) {
      echo '<td>'.$data->sheets[0]['cells'][$r][$c].'</td>';
    }
    echo '</tr>';
  }
  echo '</table>';
?>