<?php
$need_auth = 0;
require($_SERVER['DOCUMENT_ROOT'].'/sessions.php');
$ais = is_ais(3);

$localities = $not_actual = $houses = $resid_house = $employable = $pensioner = $minor = $convicted = $org_total = $hotels = $religion = $schools = $forest = $farming = $building = $without_work = 0;
require_once(KERNEL.'connection.php');
$query = mysql_query('
  SELECT
    "4300000100000" as `code`,
    "Киров" as `district`,
    COUNT(DISTINCT tmp.`locality`) as `localities`,
    COUNT(DISTINCT IF(tmp.`actual` = 0, tmp.`locality`, NULL)) as `not_actual`,
    SUM(tmp.`house`) as `houses`,
    SUM(tmp.`resid_house`) as `resid_house`,
    SUM(tmp.`employable`) as `employable`,
    SUM(tmp.`pensioner`) as `pensioner`,
    SUM(tmp.`minor`) as `minor`,
    SUM(tmp.`convicted`) as `convicted`,
    SUM(tmp.`org_total`) as `org_total`, 
    SUM(tmp.`hotels`) as `hotels`, 
    SUM(tmp.`religion`) as `religion`, 
    SUM(tmp.`schools`) as `schools`, 
    SUM(tmp.`forest`) as `forest`, 
    SUM(tmp.`farming`) as `farming`, 
    SUM(tmp.`building`) as `building`,
    COUNT(IF(tmp.`without_work` = 1, 1, 0)) as `without_work`
  FROM
    (
      SELECT
        lp.`id`, lp.`locality`, lp.`actual`, lp.`house`, lp.`resid_house`, lp.`employable`, lp.`pensioner`, lp.`minor`, lp.`convicted`,
        COUNT(DISTINCT por.`organisation`) as `org_total`,
        COUNT(DISTINCT IF(sot.`owner` = 1, org.`id`, NULL)) as `hotels`,
        COUNT(DISTINCT IF(sot.`owner` = 11, org.`id`, NULL)) as `religion`,
        COUNT(DISTINCT IF(sot.`owner` = 19, org.`id`, NULL)) as `schools`,
        COUNT(DISTINCT IF(sot.`owner` = 27, org.`id`, NULL)) as `forest`,
        COUNT(DISTINCT IF(sot.`owner` = 31, org.`id`, NULL)) as `farming`,
        COUNT(DISTINCT IF(sot.`owner` = 38, org.`id`, NULL)) as `building`,
        IF(
          ((lp.`update_date` IS NULL OR 
            lp.`house` IS NULL OR
            lp.`resid_house` IS NULL OR
            lp.`employable` IS NULL OR
            lp.`pensioner` IS NULL OR
            lp.`minor` IS NULL OR
            lp.`convicted` IS NULL OR
            por.`organisation` IS NULL
          ) AND lp.`actual` = 1), 
        1, 0) as `without_work`
      FROM
        `locality_passport` as lp
      LEFT JOIN
        `l_pass_org_relative` as por ON
          por.`locality_passport` = lp.`id`
        LEFT JOIN
          `o_organisations` as org ON
            org.`id` = por.`organisation`
        LEFT JOIN
          `spr_org_types` as sot ON
            sot.`id` = org.`type`
      GROUP BY
        lp.`locality`
    ) as tmp
  WHERE
    tmp.`locality` LIKE "43000%"

  UNION

  SELECT
    d.`code`,
    RTRIM(d.`name`) as `district`,
    COUNT(DISTINCT tmp.`locality`) as `localities`,
    COUNT(DISTINCT IF(tmp.`actual` = 0, tmp.`locality`, NULL)) as `not_actual`,
    SUM(tmp.`house`) as `houses`,
    SUM(tmp.`resid_house`) as `resid_house`,
    SUM(tmp.`employable`) as `employable`,
    SUM(tmp.`pensioner`) as `pensioner`,
    SUM(tmp.`minor`) as `minor`,
    SUM(tmp.`convicted`) as `convicted`,
    SUM(tmp.`org_total`) as `org_total`, 
    SUM(tmp.`hotels`) as `hotels`, 
    SUM(tmp.`religion`) as `religion`, 
    SUM(tmp.`schools`) as `schools`, 
    SUM(tmp.`forest`) as `forest`, 
    SUM(tmp.`farming`) as `farming`, 
    SUM(tmp.`building`) as `building`,
    COUNT(IF(tmp.`without_work` = 1, 1, 0)) as `without_work`
  FROM
    `spr_district` as d
  LEFT JOIN
    (
      SELECT
        lp.`locality`, lp.`actual`, lp.`house`, lp.`resid_house`, lp.`employable`, lp.`pensioner`, lp.`minor`, lp.`convicted`,
        COUNT(DISTINCT por.`organisation`) as `org_total`,
        COUNT(DISTINCT IF(sot.`owner` = 1, org.`id`, NULL)) as `hotels`,
        COUNT(DISTINCT IF(sot.`owner` = 11, org.`id`, NULL)) as `religion`,
        COUNT(DISTINCT IF(sot.`owner` = 19, org.`id`, NULL)) as `schools`,
        COUNT(DISTINCT IF(sot.`owner` = 27, org.`id`, NULL)) as `forest`,
        COUNT(DISTINCT IF(sot.`owner` = 31, org.`id`, NULL)) as `farming`,
        COUNT(DISTINCT IF(sot.`owner` = 38, org.`id`, NULL)) as `building`,
        IF(
          ((lp.`update_date` IS NULL OR 
            lp.`house` IS NULL OR
            lp.`resid_house` IS NULL OR
            lp.`employable` IS NULL OR
            lp.`pensioner` IS NULL OR
            lp.`minor` IS NULL OR
            lp.`convicted` IS NULL OR
            por.`organisation` IS NULL
          ) AND lp.`actual` = 1), 
        1, 0) as `without_work`
      FROM
        `locality_passport` as lp
      LEFT JOIN
        `l_pass_org_relative` as por ON
          por.`locality_passport` = lp.`id`
        LEFT JOIN
          `o_organisations` as org ON
            org.`id` = por.`organisation`
        LEFT JOIN
          `spr_org_types` as sot ON
            sot.`id` = org.`type`
      GROUP BY
        lp.`locality`
    ) as tmp ON
    SUBSTRING(d.`code`, 1, 5) LIKE SUBSTRING(tmp.`locality`, 1, 5)
  WHERE
    d.`code` LIKE "43%" AND
    SUBSTRING(d.`code`, 12, 2) NOT BETWEEN "01" AND "50"
  GROUP BY
    d.`id`
');

if (isset($_GET['to_excel'])) {
  $str = '';
  $cnt = 3;
  while ($result = mysql_fetch_assoc($query)) {
    $str .= '<Row ss:AutoFitHeight="0" ss:Height="15" ss:StyleID="s76">';
    $str .= '<Cell ss:StyleID="s77"><Data ss:Type="String">'.$result['district'].'</Data></Cell>';
    $str .= '<Cell ss:StyleID="s78">'.(($result['localities'] > 0) ? '<Data ss:Type="Number">'.$result['localities'].'</Data>' : '').'</Cell>';
    $str .= '<Cell ss:StyleID="s78">'.(($result['not_actual'] > 0) ? '<Data ss:Type="Number">'.$result['not_actual'].'</Data>' : '').'</Cell>';
    $str .= '<Cell ss:StyleID="s78">'.(($result['houses'] > 0) ? '<Data ss:Type="Number">'.$result['houses'].'</Data>' : '').'</Cell>';
    $str .= '<Cell ss:StyleID="s78">'.(($result['resid_house'] > 0) ? '<Data ss:Type="Number">'.$result['resid_house'].'</Data>' : '').'</Cell>';
    $str .= '<Cell ss:StyleID="s78">'.(($result['employable'] > 0) ? '<Data ss:Type="Number">'.$result['employable'].'</Data>' : '').'</Cell>';
    $str .= '<Cell ss:StyleID="s78">'.(($result['pensioner'] > 0) ? '<Data ss:Type="Number">'.$result['pensioner'].'</Data>' : '').'</Cell>';
    $str .= '<Cell ss:StyleID="s78">'.(($result['minor'] > 0) ? '<Data ss:Type="Number">'.$result['minor'].'</Data>' : '').'</Cell>';
    $str .= '<Cell ss:StyleID="s78">'.(($result['convicted'] > 0) ? '<Data ss:Type="Number">'.$result['convicted'].'</Data>' : '').'</Cell>';
    $str .= '<Cell ss:StyleID="s78">'.(($result['org_total'] > 0) ? '<Data ss:Type="Number">'.$result['org_total'].'</Data>' : '').'</Cell>';
    $str .= '<Cell ss:StyleID="s78">'.(($result['hotels'] > 0) ? '<Data ss:Type="Number">'.$result['hotels'].'</Data>' : '').'</Cell>';
    $str .= '<Cell ss:StyleID="s78">'.(($result['religion'] > 0) ? '<Data ss:Type="Number">'.$result['religion'].'</Data>' : '').'</Cell>';
    $str .= '<Cell ss:StyleID="s78">'.(($result['schools'] > 0) ? '<Data ss:Type="Number">'.$result['schools'].'</Data>' : '').'</Cell>';
    $str .= '<Cell ss:StyleID="s78">'.(($result['forest'] > 0) ? '<Data ss:Type="Number">'.$result['forest'].'</Data>' : '').'</Cell>';
    $str .= '<Cell ss:StyleID="s78">'.(($result['farming'] > 0) ? '<Data ss:Type="Number">'.$result['farming'].'</Data>' : '').'</Cell>';
    $str .= '<Cell ss:StyleID="s78">'.(($result['building'] > 0) ? '<Data ss:Type="Number">'.$result['building'].'</Data>' : '').'</Cell>';
    $str .= '<Cell ss:StyleID="s78">'.(($result['without_work'] > 0) ? '<Data ss:Type="Number">'.$result['without_work'].'</Data>' : '').'</Cell>';
    $str .= '</Row>';
    $str .= "\n";
  
    $localities += $result['localities'];
    $not_actual += $result['not_actual'];
    $houses += $result['houses'];
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
    $without_work += $result['without_work'];
    $cnt++;
  }
  
  $str .= '<Row ss:AutoFitHeight="0" ss:Height="15" ss:StyleID="s76">';
  $str .= '<Cell ss:StyleID="s96"><Data ss:Type="String">Итого:</Data></Cell>';
  $str .= '<Cell ss:StyleID="s97"><Data ss:Type="Number">'.$localities.'</Data></Cell>';
  $str .= '<Cell ss:StyleID="s97"><Data ss:Type="Number">'.$not_actual.'</Data></Cell>';
  $str .= '<Cell ss:StyleID="s97"><Data ss:Type="Number">'.$houses.'</Data></Cell>';
  $str .= '<Cell ss:StyleID="s97"><Data ss:Type="Number">'.$resid_house.'</Data></Cell>';
  $str .= '<Cell ss:StyleID="s97"><Data ss:Type="Number">'.$employable.'</Data></Cell>';
  $str .= '<Cell ss:StyleID="s97"><Data ss:Type="Number">'.$pensioner.'</Data></Cell>';
  $str .= '<Cell ss:StyleID="s97"><Data ss:Type="Number">'.$minor.'</Data></Cell>';
  $str .= '<Cell ss:StyleID="s97"><Data ss:Type="Number">'.$convicted.'</Data></Cell>';
  $str .= '<Cell ss:StyleID="s97"><Data ss:Type="Number">'.$org_total.'</Data></Cell>';
  $str .= '<Cell ss:StyleID="s97"><Data ss:Type="Number">'.$hotels.'</Data></Cell>';
  $str .= '<Cell ss:StyleID="s97"><Data ss:Type="Number">'.$religion.'</Data></Cell>';
  $str .= '<Cell ss:StyleID="s97"><Data ss:Type="Number">'.$schools.'</Data></Cell>';
  $str .= '<Cell ss:StyleID="s97"><Data ss:Type="Number">'.$forest.'</Data></Cell>';
  $str .= '<Cell ss:StyleID="s97"><Data ss:Type="Number">'.$farming.'</Data></Cell>';
  $str .= '<Cell ss:StyleID="s97"><Data ss:Type="Number">'.$building.'</Data></Cell>';
  $str .= '<Cell ss:StyleID="s97"><Data ss:Type="Number">'.$without_work.'</Data></Cell>';
  $str .= '</Row>';
  $str .= "\n";
  $cnt++;
    
  
  header("Pragma: public");
  header("Expires: 0");
  header("Cache-control: must-revalidate, post-check=0, pre-check=0");
  header("Cache-control: private", false);
  header('Content-Type: application/x-msexcel');
  header('Content-Disposition: attachment; filename="analysis_general.xml"');
  //header('Content-Transformer-encoding: binary');
  $out = file_get_contents('analysis_general_blank.xml');
  $out = str_replace(array('ss:ExpandedRowCount="3"', 'metka_data'), array('ss:ExpandedRowCount="'.$cnt.'"', $str), $out);
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
<style>

</style>
<body>
<?php
require($_SERVER['DOCUMENT_ROOT'].'/head.php');
?>
<div class="breadcrumbs">
  <a href="<?= INDEX ?>">Главная</a>&nbsp;&nbsp;&nbsp;>&nbsp;&nbsp;&nbsp;<a href="<?= ACCOUNTING ?>">Формирование учетов</a>&nbsp;&nbsp;&nbsp;>&nbsp;&nbsp;&nbsp;<a href="index.php"><?= $ais ?></a>&nbsp;&nbsp;&nbsp;>&nbsp;&nbsp;&nbsp;
</div>
<center><span style="font-size: 1.2em;"><strong>Результаты формирования</strong></span></center>
<hr color="#C6C6C6" size="0px"/>
<a href="<?= $_SERVER['PHP_SELF'] ?>?to_excel=true" class="get_in_excel">Скачать в формате MS Excel</a>
<table rules="all" border="1" cellpadding="3" rules="all" class="result_table" style="margin-left: -17%; width: 134%">
  <tr class="table_head">
    <th width="160px" rowspan="3">Район</th>
    <th width="60px" rowspan="3">Всего<br/>НП</th>
    <th>из них:</th>
    <th colspan="13">В том числе:</th>
    <th width="60px" rowspan="3">Без<br/>работы</th>
  </tr>
  <tr class="table_head">
    <th rowspan="2" width="60px">Нежилых</th>
    <th colspan="2">Домов</th>
    <th width="40px" colspan="4">Население</th>
    <th width="60px" rowspan="2">Орг-ции</th>
    <th width="40px" colspan="6">из них:</th>
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
  </tr>
  <?php
  while ($result = mysql_fetch_assoc($query)): ?>
    <tr>
      <td><?= $result['district'] ?></td>
      <td align="center">
      <?php if ($result['localities'] > 0) : ?>
        <a href="analysis_district.php?district=<?= $result['code'] ?>"><?= $result['localities'] ?></a>
      <?php else : ?>
      <?php endif; ?>
      </td>
      <?php $localities += $result['localities']; ?>
      <td align="center"><?= ($result['not_actual'] > 0) ? $result['not_actual'] : '' ?></td><?php $not_actual += $result['not_actual']; ?>
      <td align="center"><?= ($result['houses'] > 0) ? $result['houses'] : '' ?></td><?php $houses += $result['houses']; ?>
      <td align="center"><?= ($result['resid_house'] > 0) ? $result['resid_house'] : '' ?></td><?php $resid_house += $result['resid_house']; ?>
      <td align="center"><?= ($result['employable'] > 0) ? $result['employable'] : '' ?></td><?php $employable += $result['employable']; ?>
      <td align="center"><?= ($result['pensioner'] > 0) ? $result['pensioner'] : '' ?></td><?php $pensioner += $result['pensioner']; ?>
      <td align="center"><?= ($result['minor'] > 0) ? $result['minor'] : '' ?></td><?php $minor += $result['minor']; ?>
      <td align="center"><?= ($result['convicted'] > 0) ? $result['convicted'] : '' ?></td><?php $convicted += $result['convicted']; ?>
      <td align="center">
        <?php if ($result['org_total'] > 0) : ?>
          <a href="analysis_district_with_org.php?district=<?= $result['code'] ?>"><?= $result['org_total'] ?></a>
        <?php else : ?>
        <?php endif; ?>
      </td>
      <?php $org_total += $result['org_total']; ?>
      <td align="center"><?= ($result['hotels'] > 0) ? $result['hotels'] : '' ?></td><?php $hotels += $result['hotels']; ?>
      <td align="center"><?= ($result['religion'] > 0) ? $result['religion'] : '' ?></td><?php $religion += $result['religion']; ?>
      <td align="center"><?= ($result['schools'] > 0) ? $result['schools'] : '' ?></td><?php $schools += $result['schools']; ?>
      <td align="center"><?= ($result['forest'] > 0) ? $result['forest'] : '' ?></td><?php $forest += $result['forest']; ?>
      <td align="center"><?= ($result['farming'] > 0) ? $result['farming'] : '' ?></td><?php $farming += $result['farming']; ?>
      <td align="center"><?= ($result['building'] > 0) ? $result['building'] : '' ?></td><?php $building += $result['building']; ?>
      <td align="center"><?= ($result['without_work'] > 0) ? $result['without_work'] : '-' ?></td><?php $without_work += $result['without_work']; ?>
    </tr>
  <?php endwhile; ?>
  <tr class="table_total_row">
    <th>Итого</th>
    <th><?= $localities ?></th>
    <th><?= $not_actual ?></th>
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
    <th><?= $without_work ?></th>
  </tr>
</table>
<?php
require($_SERVER['DOCUMENT_ROOT'].'/footer.php');
?>
</body>
</html>