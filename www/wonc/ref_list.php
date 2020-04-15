<?php
$need_auth = 0;
require ($_SERVER['DOCUMENT_ROOT'].'/sessions.php');

$result = $total = $add = $variant = null;

if (!empty($_GET['ovd']))
  $ovd_id = to_integer($_GET['ovd']);

if (empty($ovd_id)) {
  define('ERROR', 'Что-то пошло не так...');
  require_once($_SERVER['DOCUMENT_ROOT'].'/error/error.php');
}

if (isset($_GET['today'])) {
  $add .= ' AND r.`create_date` = CURRENT_DATE';
  $variant = ' (Сегодня)';
}
  
  
require_once(KERNEL.'connection.php');
$ovd = getOvdName($ovd_id);
$breadcrumbs = array(
  'Главная' => '/index.php',
  'Текстовый массив' => 'index.php',
  'Обзорные справки по преступлениям' => 'references.php',
  'Обзорные справки по преступлениям по ОВД'.$variant => ''
);

$page_title = 'Обзорные справки по преступлениям &mdash; '.$ovd['ovd'];
require_once($_SERVER['DOCUMENT_ROOT'].'/header.php');
$query = '
  SELECT
    r.`id`, DATE_FORMAT(r.`create_date`, "%d.%m.%Y") as `date`,
    r.`crime_case` as `crime_case_id`,
    GROUP_CONCAT(
      DISTINCT
      kovd.`ovd`, ", №<b>", k.`kusp`, "</b> от <b>", DATE_FORMAT(k.`date`, "%d.%m.%Y"), "</b>"
      SEPARATOR "<br />"
    ) as `kusp`,
    IF(r.`crime_case` IS NULL, NULL,
      CONCAT("У/д №<b>", cc.`crime_case_number`, "</b> от ", DATE_FORMAT(cc.`crim_case_date`, "%d.%m.%Y"))
    ) as `crime_case`
  FROM
    `l_references` as r
  LEFT JOIN
    `l_crime_cases` as cc ON
      cc.`id` = r.`crime_case`
  LEFT JOIN
    `l_reference_kusp` as ok ON
      ok.`reference` = r.id AND
      ok.`deleted` = 0
    LEFT JOIN
      `l_kusp` as k ON
        k.`id` = ok.`kusp`
    LEFT JOIN
      `spr_ovd` as kovd ON
        kovd.`id_ovd` = k.`ovd`
      LEFT JOIN
        `ek_kusp` as ek ON
          ek.`reg_number` = k.`kusp` AND
          ek.`reg_date` = k.`date` AND
          ek.`ovd` = k.`ovd`
        LEFT JOIN
          `ek_dec_history` as dh ON
            dh.`id` = ek.`last_decision`
  WHERE
    r.`ovd` = '.$ovd_id.' AND
    r.`deleted` = 0
    '.$add.'
  GROUP BY
    r.`id`
  ORDER BY
    r.`create_date` desc, `kusp` desc
';
$n = 1;
?>
<div class="actions_list">
  <div class="add_box">
    <a href="reference.php?ovd=<?= $_GET['ovd'] ?>" method="reference_add">Добавить</a>
  </div>
  <?php if (!empty($_SESSION['reference']['object'])) : ?>
    <div class="continue_box">
      <a href="reference.php?ovd=<?= $ovd['ovd'] ?>" method="reference_continue">Продолжить ввод</a>
    </div>
  <?php endif; ?>
</div>
<center><span style="font-size: 1.2em;"><strong><?= $ovd['ovd'] ?></strong></span></center>
<hr color="#C6C6C6" size="0px"/>
<?php if ($result = mysql_query($query)) : ?>
  <table rules="all" border="1" cellpadding="5" align="center" class="result_table" id="myTable" width="100%">
    <tr class="table_head">
      <th width="30px">№<br />п/п</th>
      <th width="80px">Дата<br />ввода &darr;</th>
      <th width="330px">У/д</th>
      <th>КУСП</th>
    </tr>
  <?php while ($row = mysql_fetch_assoc($result)) : 
    foreach ($row as $k => $v)
      $row[$k] = get_var_in_data_type($v);
  ?>
    <tr>
      <td align="center"><?= $n++ ?>.</td>
      <td align="center"><a href="ref_view.php?id=<?= $row['id'] ?>"><?= $row['date'] ?></a></td>
      <td><?= $row['crime_case'] ?></td>
      <td><?= $row['kusp'] ?></td>
    </tr>
  <?php endwhile; ?>
  </table>
<?php else : ?>
  <?= mysql_error() ?>. Query:
  <pre><?= $query ?></pre>
<?php endif; ?>

<?php require_once($_SERVER['DOCUMENT_ROOT'].'/footer.php'); ?>