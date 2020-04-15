<?php
$need_auth = 1;
require ($_SERVER['DOCUMENT_ROOT'].'/sessions.php');

if (empty($_SESSION['user']['admin']) and empty($_SESSION['user']['ornt_reconcil'])) {
  define('ERROR', 'Недостаточно прав.');
  require_once($_SERVER['DOCUMENT_ROOT'].'/error/error.php');
}

$result = $query = null;

$page_title = 'Вновь поступившие ориентировки';
$breadcrumbs = array(
  'Главная' => '/index.php',
  'Текстовый массив' => 'index.php',
  'Ориентировки' => 'orientations.php',
  '' => ''
);
$time = time();

require_once(KERNEL.'connection.php');
$query = '
  SELECT
    o.`id`, o.`number`, DATE_FORMAT(o.`date`, "%d.%m.%Y") as `date`,
    IF(o.`wonumber` = 0, "false", "true") as `wonumber`,
    uk.`st`, ot.`type`,
    GROUP_CONCAT(
      DISTINCT
      kovd.`ovd`, ", №<b>", k.`kusp`, "</b> от <b>", DATE_FORMAT(k.`date`, "%d.%m.%Y"), "</b>"
      SEPARATOR "<br />"
    ) as `kusp`,
    o.`crime_case` as `crime_case_id`,
    IF(o.`crime_case` IS NULL, NULL,
      CONCAT("У/д №<b>", cc.`crime_case_number`, "</b> от ", DATE_FORMAT(cc.`crim_case_date`, "%d.%m.%Y"))
    ) as `crime_case`,
    f.`link`
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
    o.`reconciled` = 0
  GROUP BY
    o.`id`
  ORDER BY
    o.`number` DESC
';
$result = mysql_query($query);

require_once($_SERVER['DOCUMENT_ROOT'].'/header.php');

?>
<center><span style="font-size: 1.2em;"><strong>Вновь поступившие</strong></span></center>
<hr color="#C6C6C6" size="0px"/>
<div class="result_headers">
  <div class="result_cell number">Рег.№</div>
  <div class="result_cell date">Дата</div>
  <div class="result_cell text">Тип, у/д</div>
  <div class="result_cell text">КУСП</div>
  <div class="result_cell text">Доп.</div>
</div>
<div class="online_addons" group="online-further-query">
  <?php if ($result = mysql_query($query)) : ?>
    <?php while ($row = mysql_fetch_assoc($result)) : 
      foreach ($row as $k => $v)
        $row[$k] = get_var_in_data_type($v);
    ?>
      <div class="result_row" id="<?= $row['id'] ?>">
        <div class="result_cell number"><a href="ornt_view.php?id=<?= $row['id'] ?>" target="_blank"><?= (($row['wonumber']) ? 'б/н' : $row['number']) ?></a></div>
        <div class="result_cell date"><?= $row['date'] ?></div>
        <div class="result_cell text">
          <?= (($row['type']) ? $row['type'].'<br />' : '') ?>
          <?= (($row['st']) ? 'Розыск преступника (ст.'.$row['st'].' УК РФ)<br />' : '') ?>
          <?= $row['crime_case'] ?>
        </div>
        <div class="result_cell text">
          <?= $row['kusp'] ?>
        </div>
        <div class="result_cell text">
          <span class="actions_list">
            <a href="#" method="file_preview" file="<?= $row['link'] ?>" title="Предпросмотр">Предпросмотр</a>
            <a href="#" method="ornt_reconcile" id="<?= $row['id'] ?>" title="Отметить как проверенную">Проверено</a>
          </span>
        </div>
      </div>
    <?php endwhile; ?>
  <?php else : ?>
    <?= mysql_error() ?>. Query:
    <pre><?= $query ?></pre>
  <?php endif; ?>
</div>
<div id="file_preview"></div>
<script type="text/javascript">
  online_changes(<?= $time ?>, 15);
</script>
<?php require_once($_SERVER['DOCUMENT_ROOT'].'/footer.php'); ?>