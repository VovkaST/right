<?php
$need_auth = 0;
require ($_SERVER['DOCUMENT_ROOT'].'/sessions.php');
$breadcrumbs = array(
  'Главная' => '/index.php',
  'Текстовый массив' => 'index.php',
  'Обзорные справки по преступлениям' => ''
);

$yearList = ReferencesYearsList();
if (!empty($_GET["year"])) {
  if (in_array($_GET["year"], $yearList)) {
    $year = $_GET["year"];
  } else {
    $year = date('Y');
  }
} else {
  $year = date('Y');
}

$page_title = 'Обзорные справки по преступлениям';
require_once($_SERVER['DOCUMENT_ROOT'].'/header.php');
$result = $total = $today = null;
$query = '
  SELECT
    ovd.`id_ovd` as `id`, ovd.`ovd`,
    COUNT(DISTINCT r.`id`) as `total`,
    COUNT(DISTINCT IF(r.`create_date` = CURRENT_DATE, r.`id`, NULL)) as `today`
  FROM
    `spr_ovd` as ovd
  LEFT JOIN
    `l_references` as r ON
      r.`ovd` = ovd.`id_ovd` AND
      YEAR(r.`create_date`) = '.$year.' AND
      r.`deleted` = 0
  WHERE
    ovd.`visuality` = 1 AND
    ovd.`id_ovd` NOT IN (9,10,59,60,65,66)
  GROUP BY
    ovd.`id_ovd`
';
require_once(KERNEL.'connection.php');
?>


<center><span style="font-size: 1.2em;"><strong>Обзорные справки по преступлениям</strong></span></center>
<hr color="#C6C6C6" size="0px"/>
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
      <th colspan="3">из них</th>
    </tr>
    <tr class="table_head">
      <th width="60px">тек.<br />сутки</th>
    </tr>
  <?php while ($row = mysql_fetch_assoc($result)) : ?>
    <tr>
      <td><?= $row['ovd'] ?></td>
      <td align="center">
        <?php if ($row['total']) : ?>
          <a href="ref_list.php?ovd=<?= $row['id'] ?>"><?= $row['total'] ?></a>
        <?php else : ?>
          <a href="reference.php?ovd=<?= $row['id'] ?>">0</a>
        <?php endif; ?>
      </td><?php $total += $row['total']; ?>
      <td align="center"><?= (($row['today']) ? '<a href="ref_list.php?ovd='.$row['id'].'&today">'.$row['today'].'</a>' : 0) ?></td><?php $today += $row['today']; ?>
    </tr>
  <?php endwhile; ?>
    <tr class="table_total_row">
      <th>Итого</th>
      <th><?= $total ?></th>
      <th><?= $today ?></th>
    </tr>
  </table>
<?php else : ?>
  <?= mysql_error() ?>. Query:
  <pre><?= $query ?></pre>
<?php endif; ?>

<?php require_once($_SERVER['DOCUMENT_ROOT'].'/footer.php'); ?>