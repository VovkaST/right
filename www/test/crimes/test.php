<?php

$pass = '92 12 532 691 15.08.2014 ОУФМС России по РТ в Авиастроительном районе г.казани';

function passport($pass) {
  $len = mb_strlen($pass, 'UTF-8');
  $arr = array('ser' => null, 'num' => null, 'date' => null, 'by_whom' => null);
  $date = array();
  $tmp = '';
  for($i = 0; $i <= $len; $i++) {
    $symb = mb_substr($pass, $i, 1, 'UTF-8');
    if (is_numeric($symb)) {
      $tmp .= $symb;
      $del[] = $i;
    } else {
      if ($symb == ' ') {
        if (count($date) == 0 || count($date) == 3) {
          if ((strlen($tmp) == 2) || (strlen($tmp) == 4)) {
            $arr['ser'] .= $tmp;
          } elseif (strlen($tmp) > 4) {
            $arr['num'] .= $tmp;
          }
        } else {
          if (count($date) < 3) {
            $date[] = $tmp;
          }
        }
      } elseif (($symb == '.') || ($i == $len)) {
        if (strlen($tmp) >= 2 && count($date) < 3) {
          $date[] = $tmp;
        }
        if ($symb == '.') $del[] = $i;
      }
      $tmp = '';
    }
    $str[] = mb_substr($pass, $i, 1, 'UTF-8');
  }
  foreach($del as $ind) {unset($str[$ind]);}
  $arr['date'] = date('Y-m-d', strtotime(implode('.', $date)));
  $arr['by_whom'] = implode('', $str);
  if (mb_strlen($arr['ser'], 'UTF-8') != 4) return false;
  if (mb_strlen($arr['num'], 'UTF-8') != 6) return false;
  if (mb_strlen($arr['date'], 'UTF-8') != 10) return false;
  return $arr;
}

print_r(passport($pass));

/**
 * print_r($str);
 * print_r($del);
 */
echo '</pre>';
//echo ;

?>