<?php
$need_auth = 0;
require ($_SERVER['DOCUMENT_ROOT'].'/sessions.php');
if (!empty($_GET["ovd"]) && is_numeric($_GET["ovd"])) {
  $id_ovd = intval($_REQUEST["ovd"]);
  $par_array["ovd"] = $id_ovd;
} else {
  header('Location: index.php');
}

$yearList = decisionYears();
if (!empty($_GET["year"]) && is_numeric($_GET["year"])) {
  if (in_array($_GET["year"], $yearList)) {
    $year = $_GET["year"];
  } else {
    $year = date('Y');
  }
} else {
  $year = date('Y');
}

$par_array["resultYear"] = $year;
if (!empty($_GET["month"])) {
  $mthList = array(1 => 'Январь', 'Февраль', 'Март', 'Апрель', 'Май', 'Июнь', 'Июль', 'Август', 'Сентябрь', 'Октябрь', 'Ноябрь', 'Декабрь');
  $month = $par_array["month"] = $_GET["month"];
  $addQuery = ' AND MONTH(d.`date`) = "'.$month.'"';
  $resMonth = abs($month) > 12 ? '('.$mthList[12].')' : '('.$mthList[intval(abs($month))].')';
} else {
  $addQuery = null;
}

$page_title = 'Результаты формирования';

$breadcrumbs = array(
  'Регистрация электронных копий постановлений об отказе в возбуждении уголовного дела' => 'index.php',
  'Результаты формирования' => ''
);

$query = '
  SELECT
    d.`id`,
    IF(d.`status` = 2, CONCAT(d.`reg`, "<br />Доп."), d.`reg`) as `reg`,
    serv.`slujba`, d.`anonymous`, d.`declarer_employeer`,
    DATE_FORMAT(d.`date`, "%d.%m.%Y") as `date`, d.`upk`, d.`is_file`,
    CONCAT(
      SUBSTRING(UPPER(d.`emp_s`), 1, 1), SUBSTRING(LOWER(d.`emp_s`), 2, LENGTH(d.`emp_s`)), " ",
      SUBSTRING(d.`emp_n`, 1, 1), ".",
      SUBSTRING(d.`emp_fn`, 1, 1), ".") as `emp`,
    GROUP_CONCAT(
      DISTINCT
        uuk.`st`
      ORDER BY
        uuk.`st`
      SEPARATOR "<br />"
    ) as `uk`,
    GROUP_CONCAT(
      DISTINCT
        IF(k.`ek` IS NOT NULL, CONCAT("<a href=\"/wonc/ek.php?id=", k.`ek`, "\" target=\"_blank\" title=\"Электронный КУСП\">"), ""),
          kovd.`ovd`, ", КУСП №" , k.`kusp`, " от ", DATE_FORMAT(k.`date`, "%d.%m.%Y"),
        IF(k.`ek` IS NOT NULL, "</a>", "")
      ORDER BY
        k.`ovd`, k.`date`, k.`kusp`
      SEPARATOR "<br />"
    ) as `kusp`,
    GROUP_CONCAT(
      DISTINCT
        rell.`type`, " - ",
        SUBSTRING(l.`surname`, 1, 1), SUBSTRING(LOWER(l.`surname`), 2, LENGTH(l.`surname`)), " ",
        SUBSTRING(l.`name`, 1, 1), SUBSTRING(LOWER(l.`name`), 2, LENGTH(l.`name`)), " ",
        SUBSTRING(l.`fath_name`, 1, 1), SUBSTRING(LOWER(l.`fath_name`), 2, LENGTH(l.`fath_name`)), " ",
        DATE_FORMAT(l.`borth`, "%d.%m.%Y")
      ORDER BY
        rell.`id`, l.`surname`, l.`name`, l.`fath_name`, l.`borth`
      SEPARATOR "<br />"
    ) as `faces`,
    GROUP_CONCAT(
      DISTINCT
        relo.`type`, " - ", org.`title`
      ORDER BY
        relo.`id`, org.`title`
      SEPARATOR "<br />"
    ) as `orgs`
  FROM
    `l_decisions` as d
  JOIN
    `spr_slujba` as serv ON
      serv.`id_slujba` = d.`service`
  LEFT JOIN
    `l_dec_uk` as du ON
      du.`decision` = d.`id` AND
      du.`deleted` = 0
    LEFT JOIN
      `spr_uk` as uuk ON
        uuk.`id_uk` = du.`uk`
  LEFT JOIN
    `l_dec_kusp` as dk ON
      dk.`decision` = d.`id` AND
      dk.`deleted` = 0
    LEFT JOIN
      `l_kusp` as k ON
        k.`id` = dk.`kusp`
    LEFT JOIN
      `spr_ovd` as kovd ON
        kovd.`id_ovd` = k.`ovd`
  LEFT JOIN
    `l_dec_lico` as dl ON
      dl.`decision` = d.`id` AND
      dl.`deleted` = 0
    LEFT JOIN
      `spr_relatives` as rell ON
        rell.`id` = dl.`type`
    LEFT JOIN
      `o_lico` as l ON
        l.`id` = dl.`face`
  LEFT JOIN
    `l_dec_org` as dorg ON
      dorg.`decision` = d.`id` AND
      dorg.`deleted` = 0
    LEFT JOIN
      `spr_relatives` as relo ON
        relo.`id` = dorg.`type`
    LEFT JOIN
      `o_organisations` as org ON
        org.`id` = dorg.`organisation`
  WHERE
    d.`ovd` = '.$id_ovd.' AND
    YEAR(d.`date`) = '.$year.' AND
    d.`deleted` = 0
    '.$addQuery.'
  GROUP BY
    d.`id`
  ORDER BY
    d.`date` DESC, d.`reg` DESC';

$kol_rec = mysql_num_rows(mysql_query($query));

require_once($_SERVER['DOCUMENT_ROOT'].'/header.php');
?>
<center><span style="font-size: 1.2em;"><strong><?= getOvdName($id_ovd)[1]; ?> <?php if (isset($resMonth)) echo $resMonth ?></strong></span></center>
<hr color="#C6C6C6" size="0px"/>
  <span class="result_count">Показано результатов: <?= $kol_rec ?></span>
  <table class="result_table" border="1" rules="all" cols="7" width="100%" cellpadding="0">
    <tr class="table_head">
      <th height="40px" width="60px">Рег.№</th>
      <th width="120px">Дата решения</th>
      <th width="90px">Служба</th>
      <th width="170px">Сотрудник</th>
      <th width="80px">статья<br>УК</th>
      <th width="40px">п.<br>УПК</th>
      <th colspan="2">КУСП</th>
    </tr>
  <?php

  $query = (mysql_query($query));

  while ($result = mysql_fetch_array($query)): ?>
    <tr>
      <td colspan="8" class="info_row">
        <table border="0" rules="cols" cols="7" width="100%" cellpadding="5">
          <tr>
            <td width="50px" align="center"<?php if (!empty($result["faces"])) echo ' rowspan="2"'; ?>><b><?= $result["reg"]; ?></b></td>
            <td width="110px" align="center"><b><?= $result["date"] ?></b></td>
            <td width="80px" align="center"><b><?= $result["slujba"]; ?></b></td>
            <td width="160px"><b><?= $result["emp"] ?></b></td>
            <td width="70px" align="center"><b><?= $result["uk"] ?></b></td>
            <td width="30px" align="center"><b><?= $result["upk"]; ?></b></td>
            <td colspan="2"><?= $result["kusp"] ?></td>
          </tr>
          <?php if (!empty($_SESSION['user'])) : ?>
          <tr>
            <td class="face_cell" colspan="7">
              <div class="info_block">
                <?php if (!empty($result["anonymous"])) echo 'Анонимное сообщение (заявитель или потерпевший не установлены)<br />' ?>
                <?php if (!empty($result["declarer_employeer"])) echo 'Рапорт сотрудника/сообщение прокурора<br />' ?>
                <?php if (!empty($result["faces"])) echo $result["faces"] ?>
                <?php if (!empty($result["orgs"])) echo $result["orgs"] ?>
              </div>
              <div class="links_block">
                <a href="download.php?id=<?= $result["id"] ?>" target="_blank" class="download_link">
                  <img src="/images/filesave.png" height="20px" border="none" alt="Скачать"/>
                  <span class="hint">Скачать</span>
                </a>
                <a href="#" class="delete_link" id="<?= $result["id"]; ?>">
                  <img src="/images/delete.png" height="20px" border="none" alt="Удалить"/>
                  <span class="hint">Удалить</span>
                </a>
                <a href="decision.php" class="edit_link" id="<?= $result["id"]; ?>" method="edit">
                  <img src="/images/update.png" height="20px" border="none" alt="Редактировать"/>
                  <span class="hint">Редактировать</span>
                </a>
              </div>
            </td>
          </tr>
          <?php endif; ?>
        </table>
      </td>
    </tr>


  <?php endwhile;?>
  </table>
<?php require_once($_SERVER['DOCUMENT_ROOT'].'/footer.php'); ?>
