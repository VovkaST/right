<?php
$need_auth = 0;
require ($_SERVER['DOCUMENT_ROOT'].'/sessions.php');

$result = $total = $where = $variant = $ovd = $sinceD = $sinceT = null;

$page_title = 'Ориентировки';
if (isset($_GET['ovd']) and is_numeric($_GET['ovd'])) {
  $where[] = 'o.`ovd` = '.$_GET['ovd'];
  $ovd = getOvdName($_GET['ovd']);
  $page_title .= ' &mdash; '.$ovd['ovd'];
}

if (isset($_GET['recalled'])) {
  $where[] = 'o.`recall` IS NOT NULL';
  $variant = ' (Снятые)';
} elseif (isset($_GET['onrecall'])) {
  $where[] = 'o.`recall` IS NULL AND dh.`decision` IS NOT NULL AND dh.`dec_code` <> 23';
  $variant = ' (Не снятые)';
}

if (isset($_GET['today'])) {
  $where[] = 'o.`date` = CURRENT_DATE';
  $variant = ' (Сегодня)';
}

if (isset($_GET['debt'])) {
  $where[] = 'f.`id` IS NULL';
  $variant = ' (Не закрыто)';
}

if (!empty($_GET['date'])) {
  $sinceD = str_replace('.', '-', $_GET['date']);
  $sinceD = date('Y-m-d', strtotime($sinceD));
  $where[] = 'o.`date` >= "'.$sinceD.'"';
  if (!empty($_GET['time'])) {
    $sinceT = date('G:i:s', strtotime($_GET['time']));
    $where[] = 'o.`create_time` >= "'.$sinceT.'"';
  }
} else {
  $_GET['date'] = $_GET['time'] = null;
}

require_once(KERNEL.'connection.php');

$breadcrumbs = array(
  'Главная' => '/index.php',
  'Текстовый массив' => 'index.php',
  'Ориентировки' => 'orientations.php'
);
(!empty($ovd)) ? $breadcrumbs['Ориентировки по ОВД'.$variant] = '' : $breadcrumbs[''] = '';

$time = time();
$query = '
  SELECT
    o.`id`, o.`number`, DATE_FORMAT(o.`date`, "%d.%m.%Y") as `date`,
    IF(o.`wonumber` = 0, "false", "true") as `wonumber`,
    uk.`st`, ot.`type`,
    o.`crime_case` as `crime_case_id`,
    GROUP_CONCAT(
      DISTINCT
      IF(k.`ek` IS NOT NULL, CONCAT("<a href=\"ek.php?id=", k.`ek`, "\" target=\"_blank\">"), ""),
      kovd.`ovd`, ", №<b>", k.`kusp`, "</b> от <b>", DATE_FORMAT(k.`date`, "%d.%m.%Y"), "</b>",
      IF(k.`ek` IS NOT NULL, "</a>", "")
      SEPARATOR "<br />"
    ) as `kusp`,
    IF(o.`crime_case` IS NULL, NULL,
      CONCAT("У/д №<b>", cc.`crime_case_number`, "</b> от ", DATE_FORMAT(cc.`crim_case_date`, "%d.%m.%Y"))
    ) as `crime_case`,
    DATE_FORMAT(o.`recall`, "%d.%m.%Y") as `recall`,
    f.`link`, IF(f.`FileContent` IS NULL, "false", "true") as `indexed`
  FROM
    `l_orientations` as o
  LEFT JOIN
    `spr_uk` as uk ON
      uk.`id_uk` = o.`uk`
  LEFT JOIN
    `spr_orientation_types` as ot ON
      ot.`id` = o.`marking`
  LEFT JOIN
    `l_crime_cases` as cc ON
      cc.`id` = o.`crime_case`
  LEFT JOIN
    `l_orient_kusp` as ok ON
      ok.`orientation` = o.id AND
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
  LEFT JOIN
    `l_files` as f ON
      f.`orientation` = o.`id` AND
      f.`type` = 1
  WHERE
    o.`deleted` = 0
    '.((!empty($where)) ? 'AND '.implode(' AND ', $where) : null).'
  GROUP BY
    o.`id`
  ORDER BY
    o.`date` DESC, o.`number` DESC
';
require_once($_SERVER['DOCUMENT_ROOT'].'/header.php');
/* <pre><?= $query ?></pre> */
?>
<div class="actions_list">
  <?php if (!isset($_GET['debt'])) : ?>
    <div class="add_box">
      <a href="orientation.php<?= (!empty($ovd)) ? '?ovd='.$_GET['ovd'] : null ?>" method="orientation_add">Добавить</a>
    </div>
  <?php endif; ?>
  <?php if (!empty($_SESSION['orientation'])) : ?>
    <div class="continue_box">
      <a href="orientation.php" method="orientation_continue">Продолжить ввод</a>
    </div>
  <?php endif; ?>
</div>
<center><span style="font-size: 1.2em;"><strong><?= (!empty($ovd)) ? $ovd['ovd'] : 'Область' ?></strong></span></center>
<hr color="#C6C6C6" size="0px"/>
<?php if ($result = mysql_query($query)) : ?>
  <form method="GET" id="period_form" action="<?= $_SERVER['PHP_SELF'] ?>">
    <?= (!empty($ovd)) ? '<input type="hidden" name="ovd" value="'.$_GET['ovd'].'"/>' : null ?>
    <div class="input_row">
      <div class="field_box">
        <span class="field_name">Поступившие с</span>
        <?= my_date_field('date', ((!empty($sinceD) ? date('d.m.Y', strtotime($_GET['date'])) : null)), null, 'onchange=" document.getElementById(\'period_form\').submit(); "') ?>
      </div>
      <div class="field_box">
        <div class="input_field_block time">
          <input type="text" name="time" class="time" autocomplete="off" onchange=" document.getElementById('period_form').submit(); "/>
        </div>
      </div>
    </div>
  </form>
  <div class="result_headers">
    <div class="result_cell number">Рег.№</div>
    <div class="result_cell date">Дата &darr;</div>
    <?php if (isset($_GET['recalled'])) : ?>
      <div class="result_cell date">Дата снятия</div>
    <?php endif; ?>
    <div class="result_cell text">Тип, у/д</div>
    <div class="result_cell text">КУСП</div>
    <div class="result_cell text">Доп.</div>
  </div>
  <div class="online_addons" group="online-further-query_public" <?= (!empty($ovd)) ? 'ovd="'.$_GET['ovd'].'"' : null ?>>
  <?php while ($row = mysql_fetch_assoc($result)) : 
    foreach ($row as $k => $v)
      $row[$k] = get_var_in_data_type($v);
  ?>
    <div class="result_row">
      <div class="result_cell number"><a href="<?= (isset($_GET['debt']) ? 'orientation' : 'ornt_view') ?>.php?id=<?= $row['id'] ?>"><?= (($row['wonumber']) ? 'б/н' : $row['number']) ?></a></div>
      <div class="result_cell date"><?= $row['date'] ?></div>
      <?php if (isset($_GET['recalled'])) : ?>
        <div class="result_cell date"><?= $row['recall'] ?></div>
      <?php endif; ?>
      <div class="result_cell text">
        <?= (($row['type']) ? $row['type'].'<br />' : '') ?>
        <?= (($row['st']) ? 'Розыск преступника<br />(ст.'.$row['st'].' УК РФ)<br />' : '') ?>
        <?= $row['crime_case'] ?>
      </div>
      <div class="result_cell text">
        <?= $row['kusp'] ?>
      </div>
      <div class="result_cell text">
        <span class="actions_list">
          <?php if ($row['indexed']) : ?>
            <a href="#" method="file_preview" file="<?= $row['link'] ?>" title="Предпросмотр">Предпросмотр</a>
          <?php else : ?>
            <i>В обработке...</i>
          <?php endif; ?>
        </span>
      </div>
    </div>
  <?php endwhile; ?>
  </div>
  <div id="file_preview"></div>
<?php else : ?>
  <?= mysql_error() ?>. Query:
  <pre><?= $query ?></pre>
<?php endif; ?>
<?php if (!isset($_GET['debt'])) : ?>
  <script type="text/javascript">
    online_changes(<?= $time ?>, 15);
  </script>
<?php endif; ?>
<?php require_once($_SERVER['DOCUMENT_ROOT'].'/footer.php'); ?>