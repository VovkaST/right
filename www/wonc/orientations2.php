<?php
$need_auth = 0;
require ($_SERVER['DOCUMENT_ROOT'].'/sessions.php');
$breadcrumbs = array(
  'Главная' => '/index.php',
  'Текстовый массив' => 'index.php',
  'Ориентировки' => ''
);

$yearList = OrientYearsList();
if (!empty($_GET["year"])) {
  if (in_array($_GET["year"], $yearList)) {
    $year = $_GET["year"];
  } else {
    $year = date('Y');
  }
} else {
  $year = date('Y');
}

$page_title = 'Ориентировки';
require_once($_SERVER['DOCUMENT_ROOT'].'/header.php');
$result = $total = $recalled = $onrecall = $debt = $today = null;
$query = '
  SELECT
    ovd.`id_ovd` as `id`, ovd.`ovd`,
    COUNT(DISTINCT IF(f.`id` IS NULL, o.`id`, NULL)) as `debt`,
    COUNT(DISTINCT o.`id`) as `total`,
    COUNT(DISTINCT IF(o.`date` = CURRENT_DATE, o.`id`, NULL)) as `today`,
    COUNT(DISTINCT IF(o.`recall` IS NULL, NULL, o.`id`)) as `recalled`,
    IF(on_recall.`cnt` IS NOT NULL, on_recall.`cnt`, 0) as `on_recall`
  FROM
    `spr_ovd` as ovd
  LEFT JOIN
    `l_orientations` as o ON
      o.`ovd` = ovd.`id_ovd` AND
      YEAR(o.`date`) = '.$year.'
    LEFT JOIN
      (
        SELECT
          o2.`ovd`, COUNT(DISTINCT o2.`id`) as `cnt`
        FROM
          `l_orient_kusp` as ok
        JOIN
          `l_kusp` as k ON
            k.`id` = ok.`kusp`
          LEFT JOIN
            `ek_kusp` as ek ON
              ek.`id` = k.`ek`
          LEFT JOIN
            `ek_dec_history` as ekh ON
              ekh.`kusp` = ek.`id`
        JOIN
          `l_orientations` as o2 ON
            o2.`id` = ok.`orientation`
        WHERE
          o2.`recall` IS NULL AND
          ok.`deleted` = 0 AND
          ekh.`id` IS NOT NULL AND
          ekh.`dec_code` IN (2,12,91,25,27)
        GROUP BY
          o2.`ovd`
      ) as `on_recall` ON
        on_recall.`ovd` = o.`ovd`
    LEFT JOIN
      `l_files` as f ON
        f.`orientation` = o.id AND
        f.`type` = 1
  WHERE
    ovd.`visuality` = 1 AND
    ovd.`id_ovd` NOT IN (9,10,59,60,65,66)
  GROUP BY
    ovd.`id_ovd`
';
require_once(KERNEL.'connection.php');
?>
<center><span style="font-size: 1.2em;"><strong>Ориентировки</strong></span></center>
<hr color="#C6C6C6" size="0px"/>

<div class="actions_block">
  <ul class="actions_list">
    <li></li>
    <?php if (!empty($_SESSION['user']['admin']) or !empty($_SESSION['user']['ornt_create'])) : ?>
      <li class="item"><div class="block"><a href="ornt_new.php">Регистрация</a></div></li>
    <?php endif; ?>
    <?php if (!empty($_SESSION['user']['admin']) or !empty($_SESSION['user']['ornt_reconcil'])) : ?>
      <li class="item"><div class="block"><a href="ornts_live.php">Контроль ориентировок</a></div></li>
    <?php endif; ?>
    <li class="item"><div class="block"><a href="ornts_list.php">Список</a></div></li>
  </ul>
</div>

<div id="yearList">
  <?php foreach ($yearList as $value) :?>
    <a class="yearListStr" <?php if ($value == $year) echo 'id="resultYearBrowse"' ?> href="<?= $_SERVER["PHP_SELF"].'?year='.$value ?>"><?= $value ?></a>
  <?php endforeach; ?>
</div>
<?php if ($result = mysql_query($query)) : ?>
  <table rules="all" border="1" cellpadding="3" align="center" class="result_table" id="myTable" cols="4">
    <tr class="table_head">
      <th width="200px" rowspan="2">ОВД</th>
      <th width="60px" rowspan="2">Всего<br /><?= $year ?></th>
      <th colspan="4">из них</th>
    </tr>
    <tr class="table_head">
      <th width="60px">Не<br />закрыто</th>
      <th width="60px">Тек.<br />сутки</th>
      <th width="60px">Снятых</th>
      <th width="60px">Не снятых</th>
    </tr>
  <?php while ($row = mysql_fetch_assoc($result)) : ?>
    <tr>
      <td><?= $row['ovd'] ?></td>
      <td align="center">
        <?php if ($row['total']) : ?>
          <a href="ornts_list.php?ovd=<?= $row['id'] ?>"><?= $row['total'] ?></a>
        <?php else : ?>
          <a href="orientation.php?ovd=<?= $row['id'] ?>">0</a>
        <?php endif; ?>
      </td><?php $total += $row['total']; ?>
      <td align="center"><?= (($row['debt']) ? '<a href="ornts_list.php?ovd='.$row['id'].'&debt">'.$row['debt'].'</a>' : 0) ?></td><?php $debt += $row['debt']; ?>
      <td align="center"><?= (($row['today']) ? '<a href="ornts_list.php?ovd='.$row['id'].'&today">'.$row['today'].'</a>' : 0) ?></td><?php $today += $row['today']; ?>
      <td align="center"><?= (($row['recalled']) ? '<a href="ornts_list.php?ovd='.$row['id'].'&recalled">'.$row['recalled'].'</a>' : 0) ?></td><?php $recalled += $row['recalled']; ?>
      <td align="center"><?= (($row['on_recall']) ? '<a href="ornts_list.php?ovd='.$row['id'].'&onrecall">'.$row['on_recall'].'</a>' : 0) ?></td><?php $onrecall += $row['on_recall']; ?>
    </tr>
  <?php endwhile; ?>
    <tr class="table_total_row">
      <th>Итого</th>
      <th><?= $total ?></th>
      <th><?= $debt ?></th>
      <th><?= $today ?></th>
      <th><?= $recalled ?></th>
      <th><?= $onrecall ?></th>
    </tr>
  </table>
<?php else : ?>
  <?= mysql_error() ?>. Query:
  <pre><?= $query ?></pre>
<?php endif; ?>

<?php require_once($_SERVER['DOCUMENT_ROOT'].'/footer.php'); ?>