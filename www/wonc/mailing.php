<?php
$need_auth = 0;
require ($_SERVER['DOCUMENT_ROOT'].'/sessions.php');
require_once('require.php');

if (!empty($_GET['id'])) {
  $id = to_integer($_GET['id']);
}
if (empty($id))
  die(header('location: orientations.php'));

$data = new Orientation($id);
$data->full_data();
$files = $data->get_files_list();

require_once(KERNEL.'connection.php');
$query = '
  SELECT
    `ovd`, `dch_mail`
  FROM
    `spr_ovd`
  WHERE
    `dch_mail` IS NOT NULL
  ORDER BY
    `ovd`
';
$result = mysql_query($query) or die('<b>Error</b>: '.mysql_error().' .Query string: <pre>'.$query.'</pre>');
while ($row = mysql_fetch_assoc($result)) {
  $mails[] = $row;
}

// дополнительные ящики для рассылки
$mails[] = array( 'ovd' => 'ОВО УМВД ЦОУ', 'dch_mail' => 'pco@kir.mvd.ru');
$mails[] = array( 'ovd' => 'ЗЦКС УМВД', 'dch_mail' => 'zcksdch@kir.mvd.ru');
$mails[] = array( 'ovd' => 'ОМОН УМВД', 'dch_mail' => 'omondch@kir.mvd.ru');
$mails[] = array( 'ovd' => 'СБ ГИБДД УМВД', 'dch_mail' => 'dchobdpsgibdd@kir.mvd.ru');
$mails[] = array( 'ovd' => 'УВО при УМВД', 'dch_mail' => 'dezhuvo@kir.mvd.ru');
$mails[] = array( 'ovd' => 'Полк ППСП УМВД по г.Кирову', 'dch_mail' => 'obpps@kir.mvd.ru');
$mails[] = array( 'ovd' => '02 УМВД по г.Кирову', 'dch_mail' => '02dej@kir.mvd.ru');
// дополнительные ящики для рассылки

$rows = ceil(count($mails) / 2);
$n = 0;

$breadcrumbs = array(
  'Главная' => '/index.php',
  'Текстовый массив' => 'index.php',
  'Ориентировки' => 'orientations.php',
  'Ориентировки по ОВД' => 'ornts_list.php?ovd='.$data->get_ovd(),
  '' => ''
);
$page_title = 'Ориентировка №'.$data->get_number().' (Рассылка)';
require_once($_SERVER['DOCUMENT_ROOT'].'/header.php');
?>
<style>

</style>
<center><span style="font-size: 1.2em;"><strong>Ориентировка №<?= $data->get_number() ?> от <?= $data->get_date() ?></strong></span></center>
<hr color="#C6C6C6" size="0px"/>
<div class="work_on_the_crime mailing">

  <div class="actions_block">
    <ul class="actions_list">
      <li class="item">Действия:</li>
      <li class="item"><div class="block"><a href="ornt_view.php?id=<?= $id ?>">Просмотр</a></div></li>
      <li class="item"><div class="block"><a href="ornt_addon.php?id=<?= $id ?>">Дополнение</a></div></li>
      <?php if (is_null($data->get_recall())) : ?>
        <li class="item"><div class="block"><a href="ornt_recall.php?id=<?= $id ?>">Отбой</a></div></li>
      <?php endif; ?>
      <li class="item"><div class="block"><a href="orientation.php?id=<?= $id ?>">Редактировать</a></div></li>
      <li class="item"><div class="block current">Рассылка</div></li>
    </ul>
  </div>
  <form type="json">
    <input type="hidden" name="method" value="mailing"/>
    <input type="hidden" name="id" value="<?= $data->get_id() ?>"/>
    <div class="fieldset">
      <div class="legenda">Файлы для отправки</div>
      <table rules="none" border="0" cellpadding="3" class="result_table woborders" width="100%">
      <?php foreach ($files as $n => $file) : ?>
        <?php if (!in_array($file->get_type(), array(1, 3, 4))) continue; ?>
        <tr>
          <td>
            <label>
              <input type="checkbox" name="files[]" value="<?= $file->get_link() ?>"/><?= $file->get_type_string() ?> <i>(добавлен <?= $file->get_create_date() ?>)</i>
            </label>
            <span class="actions_list">
              <a href="#" method="file_preview" file="<?= $file->get_link() ?>" title="Предпросмотр">Предпросмотр</a>
            </span>
          </td>
        </tr>
      <?php endforeach; ?>
      </table>
    </div>
    
    <div class="fieldset mails checkbox">
      <div class="legenda">Адресаты (ДЧ территориальных ОВД и подразделения УМВД)</div>
      <div class="row left-align"><a href="#" class="check_all">Выделить все</a></div>
      <div style="display: table; width: 100%;">
        <?php for ($i = 1; $i <= 2; $i++) : ?>
          <table rules="none" border="0" cellpadding="3" class="result_table woborders" width="49%">
            <?php while ($n < count($mails)) : ?>
              <tr>
                <td>
                  <label>
                    <input type="checkbox" name="mails[]" value="<?= $mails[$n]['dch_mail'] ?>"/><?= $mails[$n]['ovd'] ?> (<i><?= $mails[$n]['dch_mail'] ?></i>)
                  </label>
                </td>
              </tr>
              <?php $n++; ?>
              <?php if ($n == $rows) break; ?>
            <?php endwhile; ?>
          </table>
        <?php endfor; ?>
      </div>
    </div>
    
    <div class="registration_block">
      <div class="add_button_box">
        <div class="button_block"><span class="button_name">Отправить</span></div>
      </div>
      <div class="response_place"></div>
    </div>
  </form>
  <div id="file_preview"></div>
</div>


<?php require_once($_SERVER['DOCUMENT_ROOT'].'/footer.php'); ?>