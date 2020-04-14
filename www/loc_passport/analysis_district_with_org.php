<?php
$need_auth = 1;
require($_SERVER['DOCUMENT_ROOT'].'/sessions.php');
$ais = is_ais(3);

if (isset($_GET["district"]) && is_numeric($_GET["district"])) {
  $district = floor(abs($_GET["district"]));
  require_once(KERNEL.'connection.php');
  if (substr($district, 0, 8) == '43000001') {
    $code = '43000001';
    $query = mysql_query('SELECT l.`name` FROM `spr_city` as l WHERE l.`code` LIKE "'.$district.'%"');
    $district = mysql_fetch_assoc($query);
  } else {
    $code = substr($district, 0, 5);
    $query = mysql_query('SELECT l.`name` FROM `spr_district` as l WHERE l.`code` LIKE "'.$district.'%"');
    $district = mysql_fetch_assoc($query);
  }
} else {
  die('Отсутствует обязательный параметр...');
}

$query = mysql_query('
  SELECT
    lp.`locality`,
    IF(LENGTH(lp.`locality`) > 13,
      CASE
        WHEN lp.`locality` = "43000001000001" THEN "Киров, г (Ленинский р-н)"
        WHEN lp.`locality` = "43000001000002" THEN "Киров, г (Октябрьский р-н)"
        WHEN lp.`locality` = "43000001000003" THEN "Киров, г (Первомайский р-н)"
        WHEN lp.`locality` = "43000001000004" THEN "Киров, г (Нововятский р-н)"
      END,
      CASE
        WHEN SUBSTRING(lp.`locality`, 9, 3) <> "000" THEN CONCAT(RTRIM(l.`name`), ", ", RTRIM(ls.`scname`))
        WHEN SUBSTRING(lp.`locality`, 9, 3) = "000" THEN CONCAT(RTRIM(c.`name`), ", ", RTRIM(cs.`scname`))
      END
    ) as `name`,
    COUNT(DISTINCT por.`organisation`) as `org_total`
  FROM
    `locality_passport` as lp
  LEFT JOIN
    `spr_city` as c ON
      c.`code` = lp.`locality`
    LEFT JOIN
      `spr_socr` as cs ON
        cs.`id` = c.`socr` AND
        cs.`level` = 3
  LEFT JOIN
    `spr_locality` as l ON
      l.`code` = lp.`locality`
    LEFT JOIN
      `spr_socr` as ls ON
        ls.`id` = l.`socr` AND
        ls.`level` = 4
  LEFT JOIN
    `l_pass_org_relative` as por ON
      por.`locality_passport` = lp.`id`
  WHERE
    lp.`locality` LIKE "'.$code.'%"
  GROUP BY
    lp.`locality`
  HAVING
    `org_total` > 0
');
while($result = mysql_fetch_assoc($query)) {
  $org_s_q = mysql_query('
    SELECT
      org.`id`, org.`title`, ot.`type`,
      CONCAT(
        IFNULL(CONCAT(RTRIM(street.`name`), " ", RTRIM(sstr.`scname`)), ""),
        IFNULL(CONCAT(", д.", IF(adr.`house_lit` IS NOT NULL, CONCAT(adr.`house`,"/",adr.`house_lit`), adr.`house`)), ""),
        IFNULL(CONCAT(", кв.", IF(adr.`flat_lit` IS NOT NULL, CONCAT(adr.`flat`,"/",adr.`flat_lit`), adr.`flat`)), "")
      ) as `addr`
    FROM
      `o_organisations` as org
    JOIN
      `spr_org_types` as ot ON
        ot.`id` = org.`type`
    LEFT JOIN
      `l_pass_org_relative` as por ON
        por.`organisation` = org.`id`
      JOIN
        `locality_passport` as lp ON
          lp.`id` = por.`locality_passport`
    LEFT JOIN
      `l_relatives` as rel ON
        rel.`to_obj` = org.`id` AND
        rel.`to_obj_type` = 11
      LEFT JOIN
        `o_address` as adr ON
          adr.`id` = rel.`from_obj` AND
          rel.`from_obj_type` = 3
        LEFT JOIN
          `spr_street` as street ON
            street.`id` = adr.`street`
          LEFT JOIN
            `spr_socr` as sstr ON
              sstr.`id` = street.`socr` AND
              sstr.`level` = 5
    WHERE
      lp.`locality` = "'.$result['locality'].'"
    ORDER BY
      ot.`type`, `addr`
  ');
  while($org_s = mysql_fetch_assoc($org_s_q)) {
    $result['org'][] = $org_s;
  }
  $data[] = $result;
}
if (isset($_GET['to_excel'])) {
  $str = '';
  $total = 3;
  $org_cnt = 0;
  $cntr = 0;
  
  foreach($data as $k => $v) {
    $org_cnt = count($v['org']);
    $cntr++;
    if ($org_cnt > 1) {
      $org_str = $v['org'][0]['title'].'('.$v['org'][0]['type'].') &ndash; '.(($v['org'][0]['addr']) ? $v['org'][0]['addr'] : 'без адреса');
      $w = ceil(mb_strlen($org_str, "UTF-8")/75);
      $str .= '<Row ss:AutoFitHeight="0" ss:Height="'.(15 + (($w - 1)*10)).'">';
      $str .= '<Cell ss:MergeDown="'.($org_cnt - 1).'" ss:StyleID="s76"><Data ss:Type="String">'.$cntr.'.</Data></Cell>';
      $str .= '<Cell ss:MergeDown="'.($org_cnt - 1).'" ss:StyleID="s83"><Data ss:Type="String">'.$v['name'].'</Data></Cell>';
      $str .= '<Cell ss:StyleID="s86"><Data ss:Type="String">1.</Data></Cell>';
      $str .= '<Cell ss:StyleID="s87"><Data ss:Type="String">'.$org_str.'</Data></Cell>';
      $str .= '</Row>';
      $str .= "\n";
      $total++;
      for($i = 1; $i < $org_cnt; $i++) {
        $org_str = $v['org'][$i]['title'].'('.$v['org'][$i]['type'].') &ndash; '.(($v['org'][$i]['addr']) ? $v['org'][$i]['addr'] : 'без адреса');
        $w = ceil(mb_strlen($org_str, "UTF-8")/75);
        $str .= '<Row ss:AutoFitHeight="0" ss:Height="'.(15 + (($w - 1)*10)).'">';
        $str .= '<Cell ss:Index="3" ss:StyleID="s88"><Data ss:Type="String">'.($i + 1).'.</Data></Cell>';
        $str .= '<Cell ss:StyleID="s89"><Data ss:Type="String">'.$org_str.'</Data></Cell>';
        $str .= '</Row>';
        $total++;
        $str .= "\n";
      }
    } else {
      $org_str = $v['org'][0]['title'].'('.$v['org'][0]['type'].') &ndash; '.(($v['org'][0]['addr']) ? $v['org'][0]['addr'] : 'без адреса');
      $w = ceil(mb_strlen($org_str, "UTF-8")/75);
      $str .= '<Row ss:AutoFitHeight="0" ss:Height="'.(15 + (($w - 1)*10)).'">';
      $str .= '<Cell ss:StyleID="s72"><Data ss:Type="String">'.$cntr.'</Data></Cell>';
      $str .= '<Cell ss:StyleID="s79"><Data ss:Type="String">'.$v['name'].'</Data></Cell>';
      $str .= '<Cell ss:StyleID="s95"><Data ss:Type="String">1.</Data></Cell>';
      $str .= '<Cell ss:StyleID="s96"><Data ss:Type="String">'.$org_str.'</Data></Cell>';
      $str .= '</Row>';
      $str .= "\n";
      $total++;
    }
  }
  
  header("Pragma: public");
  header("Expires: 0");
  header("Cache-control: must-revalidate, post-check=0, pre-check=0");
  header("Cache-control: private", false);
  header('Content-Type: application/x-msexcel');
  header('Content-Disposition: attachment; filename="analysis_district_with_org.xml"');
  $out = file_get_contents('analysis_district_with_org_blank.xml');
  $out = str_replace(array('ss:ExpandedRowCount="2"', 'metka_district', 'metka_data'), array('ss:ExpandedRowCount="'.$total.'"', trim($district['name']), $str), $out);
  echo $out;
  die();
}
?>
<!DOCTYPE html>
<html>
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
  <title><?= $ais ?></title>
  <link rel="shortcut icon" href="/images/favicon.ico">
  <link rel="icon" href="/images/favicon.ico" type="image/vnd.microsoft.icon">
  <link rel="stylesheet" href="/css/main.css">
  <link rel="stylesheet" href="/css/new.css">
  <link rel="stylesheet" href="/css/redmond/jquery-ui-1.10.4.custom.css">
  <script type="text/javascript" src="/js/jquery-1.10.2.js"></script>
  <script type="text/javascript" src="/js/jquery-ui-1.10.4.custom.js"></script>
  <script type="text/javascript" src="/js/jquery.inputmask.js"></script>
  <script type="text/javascript" src="/js/functions.js"></script>
  <script type="text/javascript" src="/js/quick_search.js"></script>
</head>
<body>
<?php
require($_SERVER['DOCUMENT_ROOT'].'/head.php');
?>
<div class="breadcrumbs">
  <a href="<?= INDEX ?>">Главная</a>&nbsp;&nbsp;&nbsp;>&nbsp;&nbsp;&nbsp;<a href="<?= ACCOUNTING ?>">Формирование учетов</a>&nbsp;&nbsp;&nbsp;>&nbsp;&nbsp;&nbsp;<a href="index.php"><?= $ais ?></a>&nbsp;&nbsp;&nbsp;>&nbsp;&nbsp;&nbsp;<a href="analysis_general.php">Результаты формирования</a>&nbsp;&nbsp;&nbsp;>&nbsp;&nbsp;&nbsp;
</div>
<center><span style="font-size: 1.2em;"><strong><?= $district['name'] ?> (НП с организациями)</strong></span></center>
<hr color="#C6C6C6" size="0px"/>
<a href="<?= $_SERVER['PHP_SELF'] ?>?district=<?= $_GET["district"] ?>&to_excel=true" class="get_in_excel">Скачать в формате MS Excel</a>
<table rules="all" border="1" cellpadding="3" rules="all" class="result_table" width="100%">
  <tr class="table_head">
    <th width="40px">№<br/>п/п</th>
    <th width="160px">Насел.пункт</th>
    <th>Орг-ции</th>
  </tr>
  <?php
  $i = 1;
  foreach($data as $k => $v) : ?>
    <tr>
      <td align="center"><?= $i++ ?>.</td>
      <td><?= $v['name'] ?></td>
      <td>
        <ul>
        <?php foreach($v['org'] as $n => $org) : ?>
          <li><?= $n + 1 ?>. <?= $org['title'] ?> (<?= $org['type'] ?>) &ndash; <?= ($org['addr']) ? $org['addr'] : 'без адреса' ?></li>
        <?php endforeach; ?>
        </ul>
      </td>
    </tr>
  <?php endforeach; ?>
</table>
<?php
require($_SERVER['DOCUMENT_ROOT'].'/footer.php');
?>
</body>
</html>