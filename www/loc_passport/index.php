<?php
$need_auth = 1;
require($_SERVER['DOCUMENT_ROOT'].'/sessions.php');
$ais = is_ais(3);

require_once(KERNEL.'connection.php');
$query = mysql_query('
  SELECT
    d.`id`,
    RTRIM(d.`name`) as `name`,
    d.`code`
  FROM
    `spr_district` as d
  LEFT JOIN
    `spr_socr` as s ON
      s.`id` = d.`socr` AND
      s.`level` = 2
  WHERE
    d.`code` LIKE "43%" AND
    SUBSTRING(d.`code`, 12, 2) NOT BETWEEN "01" AND "50"
');
while($result = mysql_fetch_assoc($query)) {
  $district[] = $result;
}
mysql_free_result($query);
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
  <a href="<?= INDEX ?>">Главная</a>&nbsp;&nbsp;&nbsp;>&nbsp;&nbsp;&nbsp;<a href="<?= ACCOUNTING ?>">Формирование учетов</a>&nbsp;&nbsp;&nbsp;>&nbsp;&nbsp;&nbsp;
</div>
<center><span style="font-size: 1.2em;"><strong><?= $ais ?></strong></span></center>
<hr color="#C6C6C6" size="0px"/>
<div class="locality_passport_analysis">
  Анализ данных, результаты формирования
  <ul class="analysis_variants">
    <li><a href="analysis_general.php">Общие количественные показатели</a></li>
  </ul>
</div>
<div class="locality_passport">
  <div class="district_block">
    <div class="section_title list_header">Район</div>
    <ul class="district_list">
      <li>
        <a href="#" class="district" id="4300000100000">г. Киров</a>
        <div class="list_more district">
          <a href="analysis_district.php?district=4300000100000" class="more">Подробнее...</a>
        </div>
      </li>
    <?php for($i = 0; $i < count($district); $i++) : ?>
      <li>
        <a href="#" class="district" id="<?= $district[$i]['code'] ?>"><?= $district[$i]['name'] ?></a>
        <div class="list_more district">
          <a href="analysis_district.php?district=<?= $district[$i]['code'] ?>" class="more">Подробнее...</a>
        </div>
      </li>
    <?php endfor;?>
    <ul>
  </div>
  <div class="locality_block"></div>
  <div class="passport_block"></div>
</div>

<?php
require($_SERVER['DOCUMENT_ROOT'].'/footer.php');
?>
</body>
</html>