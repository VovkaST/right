<?php
$need_auth = 0;
require ($_SERVER['DOCUMENT_ROOT'].'/sessions.php');
if (!empty($_GET["id_ovd"]) && is_numeric($_GET["id_ovd"])) {
  $id_ovd = intval($_GET["id_ovd"]);
  $par_array["id_ovd"] = $id_ovd;
} else {
  header('Location: formation_results.php');
}
$yearList = selectRefuseYears();
if (!empty($_GET["debtYear"]) && is_numeric($_GET["debtYear"])) {
  if (in_array($_GET["debtYear"], $yearList)) {
    $year = $_GET["debtYear"];
  } else {
    $year = date('Y');
  }
} else {
  $year = date('Y');
}
$par_array["debtYear"] = $year;
$lastSwap = OVDlastSwap($id_ovd);
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset=utf-8>
  <title>Долги <?= getOvdName($id_ovd)[1] ?></title>
  <link rel="shortcut icon" href="<?= IMG ?>favicon.ico">
  <link rel="icon" href="<?= IMG ?>favicon.ico" type="image/vnd.microsoft.icon">
  <link rel="stylesheet" href="<?= CSS ?>head.css">
  <link rel="stylesheet" href="<?= CSS ?>main.css">
  <link rel="stylesheet" href="<?= CSS ?>new.css">
  <link rel="stylesheet" href="<?= CSS ?>redmond/jquery-ui-1.10.4.custom.css">
  <script src="<?= JS ?>jquery-1.10.2.js"></script>
  <script src="<?= JS ?>jquery-ui-1.10.4.custom.js"></script>
  <script src="<?= JS ?>procedures.js"></script>
</head>
<body>
<?php
require ($_SERVER['DOCUMENT_ROOT'].'/head.php');
?>
<div class="breadcrumbs">
  <a href="<?= INDEX ?>">Главная</a>&nbsp;&nbsp;&nbsp;>&nbsp;&nbsp;&nbsp;<a href="<?= ACCOUNTING ?>">Формирование учетов</a>&nbsp;&nbsp;&nbsp;>&nbsp;&nbsp;&nbsp;<a href="<?= REFUSAL_VIEW_UPLOAD ?>">Регистрация электронных копий постановлений об отказе в возбуждении уголовного дела</a>&nbsp;&nbsp;&nbsp;>&nbsp;&nbsp;&nbsp;<a href="formation_results.php?resultYear=<?= $year ?>">Долги, недостатки и результаты формирования</a>&nbsp;&nbsp;&nbsp;>&nbsp;&nbsp;&nbsp;
</div>
<h3><?= getOvdName($id_ovd)[1] ?>
</h3>
<div class="debt_date">
  Последняя выгрузка до выставления долгов в <?= date('H:i d.m.Y', strtotime($lastSwap['LAST_SWAP_DATE'].' '.$lastSwap['LAST_SWAP_TIME'])) ?>
</div>
<?php
$query = mysql_query('
  SELECT
    ld.id,
    ld.reg_num,
    DATE_FORMAT(ld.reg_date, "%d.%m.%Y") as reg_date,
    DATE_FORMAT(ld.dec_date, "%d.%m.%Y") as dec_date,
    ld.emp_s,
    ld.emp_n,
    ld.emp_fn,
    sl.slujba,
    ld.article,
    ld.article_part,
    ld.article_note,
    ld.article_proc_par
  FROM
    leg_decisions as ld
  LEFT JOIN
    kusp ON
      year(kusp.data) = year(ld.reg_date) AND
      kusp.kusp = ld.reg_num AND
      kusp.ovd = ld.id_ovd
  LEFT JOIN
    spr_ovd as ovd ON
      ovd.id_ovd = ld.id_ovd
  LEFT JOIN
    spr_slujba as sl ON
      sl.slujbaLeg = ld.emp_service
  WHERE
    ld.dec_date > "'.$year.'-01-01" AND
    ld.dec_date < "'.$year.'-12-31" AND
    ld.dec_date > "2015-01-01" AND
    kusp.id IS NULL AND
    ovd.ovd IS NOT NULL AND
    ld.id_ovd = "'.$id_ovd.'" AND
    ld.error_rec = 1
  ORDER BY
    ld.reg_date ASC,
    ld.reg_num
') or die(mysql_error());
$i = 1;
?>
<table rules="all" border="1" cellpadding="3" align="center" class="result_table">
  <tr class="table_head">
    <th>№<br/>п/п</th>
    <th>Рег.№<br/>КУСП</th>
    <th width="80px">Дата рег.</th>
    <th width="80px">Дата<br/>решения</th>
    <th>Решение вынес</th>
    <th width="70px">ст.<br/>УК РФ</th>
    <th>пункт<br/>ст.24 УПК</th>
    <th colspan="2">Служба</th>
  </tr>
  <?php while ($result = mysql_fetch_assoc($query)): ?>
  <tr>
    <td align="center"><?= $i++ ?>.</td>
    <td><?= $result['reg_num'] ?></td>
    <td align="center"><?= $result['reg_date'] ?></td>
    <td align="center"><?= $result['dec_date'] ?></td>
    <td><?= mb_convert_case($result['emp_s'].' '.$result['emp_n'].' '.$result['emp_fn'], MB_CASE_TITLE, "UTF-8") ?></td>
    <?php 
    $article = $proc = '';
    if (!empty($result["article"])) {
      $article = $result["article"];
      if (!empty($result["article_note"])) {
        $article .= '.'.$result["article_note"];
      }
      if (!empty($result["article_part"])) {
        $article .= ' ч.'.$result["article_part"];
      }
    }
    if (!empty($result["article_proc_par"])) {
      $proc = substr($result["article_proc_par"], 0, 1);
    }
    ?>
    <td align="center"><?= $article ?></td>
    <td align="center"><?php if ($proc > 0 && $proc <= 6) echo $proc; ?></td>
    <td align="center" class="link_service_cell">
      <?= $result['slujba'] ?>
    </td>
    <td class="update_link_cell">
      <input type="image" name="refuse_id" src="<?= IMG ?>plus.png" class="add_report" id ="<?= $result['id'] ?>"/>
    </td>
  </tr>
  <?php endwhile; ?>
</table>
<?php
require ($_SERVER['DOCUMENT_ROOT'].'/footer.php');
?>

</body>
</html>