<?php
$need_auth = 0;
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
    lp.`id`,
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
    lp.`actual`, lp.`distance`, lp.`house`, lp.`resid_house`, lp.`employable`, lp.`pensioner`, lp.`minor`, lp.`convicted`,
    COUNT(DISTINCT por.`organisation`) as `org_total`,
    COUNT(DISTINCT IF(sot.`owner` = 1, org.`id`, NULL)) as `hotels`,
    COUNT(DISTINCT IF(sot.`owner` = 11, org.`id`, NULL)) as `religion`,
    COUNT(DISTINCT IF(sot.`owner` = 19, org.`id`, NULL)) as `schools`,
    COUNT(DISTINCT IF(sot.`owner` = 27, org.`id`, NULL)) as `forest`,
    COUNT(DISTINCT IF(sot.`owner` = 31, org.`id`, NULL)) as `farming`,
    COUNT(DISTINCT IF(sot.`owner` = 38, org.`id`, NULL)) as `building`,
    lp.`mts`, lp.`beeline`, lp.`megafon`, lp.`tele2`
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
    LEFT JOIN
      `o_organisations` as org ON
        org.`id` = por.`organisation`
    LEFT JOIN
      `spr_org_types` as sot ON
        sot.`id` = org.`type`
  WHERE
    lp.`locality` LIKE "'.$code.'%"
  GROUP BY
    lp.`locality`
  ORDER BY
    `name`
');

$houses = $resid_house = $employable = $pensioner = $minor = $convicted = $org_total = $hotels = $religion = $schools = $forest = $farming = $building = 0;

if (isset($_GET['to_excel'])) {
  $str = '';
  $cnt = 3;
  while ($result = mysql_fetch_assoc($query)) {
    if ($result['actual'] == 0) {
      $str .= '<Row ss:AutoFitHeight="0" ss:Height="15.75">';
      $str .= '<Cell ss:StyleID="s80"><Data ss:Type="String">'.($cnt-2).'.</Data></Cell>';
      $str .= '<Cell ss:StyleID="s92"><Data ss:Type="String">'.$result['name'].'</Data></Cell>';
      $str .= '<Cell ss:MergeAcross="16" ss:StyleID="s79"><Data ss:Type="String">Нежилой (расстояние до районного центра '.(($result['distance']) ? $result['distance'].' км' : 'не указано').')</Data></Cell>';
      $str .= '</Row>';
      $str .= "\n";
    } else {
      $str .= '<Row ss:AutoFitHeight="0" ss:Height="15">';
      $str .= '<Cell ss:StyleID="s80"><Data ss:Type="String">'.($cnt-2).'.</Data></Cell>';
      $str .= '<Cell ss:StyleID="s92"><Data ss:Type="String">'.$result['name'].'</Data></Cell>';
      $str .= '<Cell ss:StyleID="s80">'.(($result['house'] > 0) ? '<Data ss:Type="Number">'.$result['house'].'</Data>' : '').'</Cell>';
      $str .= '<Cell ss:StyleID="s80">'.(($result['resid_house'] > 0) ? '<Data ss:Type="Number">'.$result['resid_house'].'</Data>' : '').'</Cell>';
      $str .= '<Cell ss:StyleID="s80">'.(($result['employable'] > 0) ? '<Data ss:Type="Number">'.$result['employable'].'</Data>' : '').'</Cell>';
      $str .= '<Cell ss:StyleID="s80">'.(($result['pensioner'] > 0) ? '<Data ss:Type="Number">'.$result['pensioner'].'</Data>' : '').'</Cell>';
      $str .= '<Cell ss:StyleID="s80">'.(($result['minor'] > 0) ? '<Data ss:Type="Number">'.$result['minor'].'</Data>' : '').'</Cell>';
      $str .= '<Cell ss:StyleID="s80">'.(($result['convicted'] > 0) ? '<Data ss:Type="Number">'.$result['convicted'].'</Data>' : '').'</Cell>';
      $str .= '<Cell ss:StyleID="s80">'.(($result['org_total'] > 0) ? '<Data ss:Type="Number">'.$result['org_total'].'</Data>' : '').'</Cell>';
      $str .= '<Cell ss:StyleID="s80">'.(($result['hotels'] > 0) ? '<Data ss:Type="Number">'.$result['hotels'].'</Data>' : '').'</Cell>';
      $str .= '<Cell ss:StyleID="s80">'.(($result['religion'] > 0) ? '<Data ss:Type="Number">'.$result['religion'].'</Data>' : '').'</Cell>';
      $str .= '<Cell ss:StyleID="s80">'.(($result['schools'] > 0) ? '<Data ss:Type="Number">'.$result['schools'].'</Data>' : '').'</Cell>';
      $str .= '<Cell ss:StyleID="s80">'.(($result['forest'] > 0) ? '<Data ss:Type="Number">'.$result['forest'].'</Data>' : '').'</Cell>';
      $str .= '<Cell ss:StyleID="s80">'.(($result['farming'] > 0) ? '<Data ss:Type="Number">'.$result['farming'].'</Data>' : '').'</Cell>';
      $str .= '<Cell ss:StyleID="s80">'.(($result['building'] > 0) ? '<Data ss:Type="Number">'.$result['building'].'</Data>' : '').'</Cell>';
      $str .= '<Cell ss:StyleID="s80">'.(($result['mts'] > 0) ? '<Data ss:Type="String">√</Data>' : '').'</Cell>';
      $str .= '<Cell ss:StyleID="s80">'.(($result['beeline'] > 0) ? '<Data ss:Type="String">√</Data>' : '').'</Cell>';
      $str .= '<Cell ss:StyleID="s80">'.(($result['megafon'] > 0) ? '<Data ss:Type="String">√</Data>' : '').'</Cell>';
      $str .= '<Cell ss:StyleID="s80">'.(($result['tele2'] > 0) ? '<Data ss:Type="String">√</Data>' : '').'</Cell>';
      $str .= '</Row>';
      $str .= "\n";
    }


    $houses += $result['house'];
    $resid_house += $result['resid_house'];
    $employable += $result['employable'];
    $pensioner += $result['pensioner'];
    $minor += $result['minor'];
    $convicted += $result['convicted'];
    $org_total += $result['org_total'];
    $hotels += $result['hotels'];
    $religion += $result['religion'];
    $schools += $result['schools'];
    $forest += $result['forest'];
    $farming += $result['farming'];
    $building += $result['building'];
    $cnt++;
  }
  
  $str .= '<Row ss:AutoFitHeight="0" ss:Height="15">';
  $str .= '<Cell ss:MergeAcross="1" ss:StyleID="s89"><Data ss:Type="String">Итого:</Data></Cell>';
  $str .= '<Cell ss:StyleID="s90"><Data ss:Type="Number">'.$houses.'</Data></Cell>';
  $str .= '<Cell ss:StyleID="s90"><Data ss:Type="Number">'.$resid_house.'</Data></Cell>';
  $str .= '<Cell ss:StyleID="s90"><Data ss:Type="Number">'.$employable.'</Data></Cell>';
  $str .= '<Cell ss:StyleID="s90"><Data ss:Type="Number">'.$pensioner.'</Data></Cell>';
  $str .= '<Cell ss:StyleID="s90"><Data ss:Type="Number">'.$minor.'</Data></Cell>';
  $str .= '<Cell ss:StyleID="s90"><Data ss:Type="Number">'.$convicted.'</Data></Cell>';
  $str .= '<Cell ss:StyleID="s90"><Data ss:Type="Number">'.$org_total.'</Data></Cell>';
  $str .= '<Cell ss:StyleID="s90"><Data ss:Type="Number">'.$hotels.'</Data></Cell>';
  $str .= '<Cell ss:StyleID="s90"><Data ss:Type="Number">'.$religion.'</Data></Cell>';
  $str .= '<Cell ss:StyleID="s90"><Data ss:Type="Number">'.$schools.'</Data></Cell>';
  $str .= '<Cell ss:StyleID="s90"><Data ss:Type="Number">'.$forest.'</Data></Cell>';
  $str .= '<Cell ss:StyleID="s90"><Data ss:Type="Number">'.$farming.'</Data></Cell>';
  $str .= '<Cell ss:StyleID="s90"><Data ss:Type="Number">'.$building.'</Data></Cell>';
  $str .= '<Cell ss:StyleID="s90"><Data ss:Type="String">-</Data></Cell>';
  $str .= '<Cell ss:StyleID="s90"><Data ss:Type="String">-</Data></Cell>';
  $str .= '<Cell ss:StyleID="s90"><Data ss:Type="String">-</Data></Cell>';
  $str .= '<Cell ss:StyleID="s90"><Data ss:Type="String">-</Data></Cell>';
  $str .= '</Row>';
  $str .= "\n";
  $cnt++;
  
  header("Pragma: public");
  header("Expires: 0");
  header("Cache-control: must-revalidate, post-check=0, pre-check=0");
  header("Cache-control: private", false);
  header('Content-Type: application/x-msexcel');
  header('Content-Disposition: attachment; filename="analysis_district.xml"');
  $out = file_get_contents('analysis_district_blank.xml');
  $out = str_replace(array('ss:ExpandedRowCount="2"', 'metka_data'), array('ss:ExpandedRowCount="'.$cnt.'"', $str), $out);
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
<center><span style="font-size: 1.2em;"><strong><?= $district['name'] ?></strong></span></center>
<hr color="#C6C6C6" size="0px"/>
<a href="<?= $_SERVER['PHP_SELF'] ?>?district=<?= $_GET["district"] ?>&to_excel=true" class="get_in_excel">Скачать в формате MS Excel</a>
<table rules="all" border="1" cellpadding="3" rules="all" class="result_table" style="margin-left: -17%; width: 134%">
  <tr class="table_head">
    <th width="40px" rowspan="2">№<br/>п/п</th>
    <th width="160px" rowspan="2">Насел.пункт</th>
    <th colspan="2">Домов</th>
    <th colspan="4">Население</th>
    <th rowspan="2">Орг-ции</th>
    <th colspan="6">из них:</th>
    <th colspan="6">Покрытие</th>
  </tr>
  <tr class="table_head">
    <th width="60px">Жилых</th>
    <th width="60px">Нежилых</th>
    <th width="60px">Труд.</th>
    <th width="60px">Пенс.</th>
    <th width="60px">Н/л</th>
    <th width="60px">Судимые</th>
    <th width="60px">Гостин.</th>
    <th width="60px">Религ.</th>
    <th width="60px">Уч.зав.</th>
    <th width="60px">Лес.</th>
    <th width="60px">С/х</th>
    <th width="60px">Строит.</th>
    <th><img src="/images/mob_operators/mts.png" width="20px"/></th>
    <th><img src="/images/mob_operators/beeline.png" width="20px"/></th>
    <th><img src="/images/mob_operators/megafon.png" width="20px"/></th>
    <th><img src="/images/mob_operators/tele2.png" width="20px"/></th>
  </tr>
  <?php
  $i = 0;
  while ($result = mysql_fetch_assoc($query)): ?>
    <?php if ($result['actual'] == 0) : ?>
      <tr class="not_actual">
        <td align="center"><?= ++$i ?>.</td>
        <td><?= $result['name'] ?></td>
        <td colspan="17" align="center">Нежилой (расстояние до районного центра <?= ($result['distance']) ? $result['distance'].' км' : 'не указано' ?>)</td>
      </tr>
    <?php else : ?>
      <tr>
        <td align="center"><?= ++$i ?>.</td>
        <td><?= $result['name'] ?></td>
        <td align="center"><?= ($result['house'] > 0) ? $result['house'] : '' ?></td><?php $houses += $result['house']; ?>
        <td align="center"><?= ($result['resid_house'] > 0) ? $result['resid_house'] : '' ?></td><?php $resid_house += $result['resid_house']; ?>
        <td align="center"><?= ($result['employable'] > 0) ? $result['employable'] : '' ?></td><?php $employable += $result['employable']; ?>
        <td align="center"><?= ($result['pensioner'] > 0) ? $result['pensioner'] : '' ?></td><?php $pensioner += $result['pensioner']; ?>
        <td align="center"><?= ($result['minor'] > 0) ? $result['minor'] : '' ?></td><?php $minor += $result['minor']; ?>
        <td align="center"><?= ($result['convicted'] > 0) ? $result['convicted'] : '' ?></td><?php $convicted += $result['convicted']; ?>
        <td align="center"><?= ($result['org_total'] > 0) ? $result['org_total'] : '' ?></td><?php $org_total += $result['org_total']; ?>
        <td align="center"><?= ($result['hotels'] > 0) ? $result['hotels'] : '' ?></td><?php $hotels += $result['hotels']; ?>
        <td align="center"><?= ($result['religion'] > 0) ? $result['religion'] : '' ?></td><?php $religion += $result['religion']; ?>
        <td align="center"><?= ($result['schools'] > 0) ? $result['schools'] : '' ?></td><?php $schools += $result['schools']; ?>
        <td align="center"><?= ($result['forest'] > 0) ? $result['forest'] : '' ?></td><?php $forest += $result['forest']; ?>
        <td align="center"><?= ($result['farming'] > 0) ? $result['farming'] : '' ?></td><?php $farming += $result['farming']; ?>
        <td align="center"><?= ($result['building'] > 0) ? $result['building'] : '' ?></td><?php $building += $result['building']; ?>
        <td align="center"><?= ($result['mts']) ? '&#10004;' : '' ?></td>
        <td align="center"><?= ($result['beeline']) ? '&#10004;' : '' ?></td>
        <td align="center"><?= ($result['megafon']) ? '&#10004;' : '' ?></td>
        <td align="center"><?= ($result['tele2']) ? '&#10004;' : '' ?></td>
      </tr>
    <?php endif; ?>
  <?php endwhile; ?>
  <tr style="background: #F4F4F4;">
    <th colspan="2">Итого</th>
    <th><?= $houses ?></th>
    <th><?= $resid_house ?></th>
    <th><?= $employable ?></th>
    <th><?= $pensioner ?></th>
    <th><?= $minor ?></th>
    <th><?= $convicted ?></th>
    <th><?= $org_total ?></th>
    <th><?= $hotels ?></th>
    <th><?= $religion ?></th>
    <th><?= $schools ?></th>
    <th><?= $forest ?></th>
    <th><?= $farming ?></th>
    <th><?= $building ?></th>
    <th>-</th>
    <th>-</th>
    <th>-</th>
    <th>-</th>
  </tr>
</table>
<?php
require($_SERVER['DOCUMENT_ROOT'].'/footer.php');
?>
</body>
</html>