<?php
$need_auth = 0;
require ($_SERVER['DOCUMENT_ROOT'].'/sessions.php');
$breadcrumbs = array(
  'Главная' => '/index.php',
  'Текстовый массив' => 'index.php',
  'Ориентировки' => 'orientations.php',
  'Отбой по ориентировке' => ''
);
require_once('require.php');

$data = $id = null;
if (!empty($_GET['id'])) {
  $id = to_integer($_GET['id']);
}
if (empty($id))
  die(header('location: orientations.php'));

$data = new Orientation($id);
$page_title = 'Отбой по ориентировке №'.$data->get_number();
if (isset($_SESSION['orientation']))
  unset($_SESSION['orientation']);
$data->full_data();
$files = $data->get_files_array();

require_once($_SERVER['DOCUMENT_ROOT'].'/header.php');
?>
<style>
</style>
<center><span style="font-size: 1.2em;"><strong>Ориентировка №<?= $data->get_number() ?> от <?= $data->get_date() ?></strong></span></center>
<hr color="#C6C6C6" size="0px"/>

<div class="work_on_the_crime orientation">
  <div class="actions_block">
    <ul class="actions_list">
      <li class="item">Действия:</li>
      <li class="item"><div class="block"><a href="ornt_view.php?id=<?= $id ?>">Просмотр</a></div></li>
      <li class="item"><div class="block"><a href="ornt_addon.php?id=<?= $id ?>">Дополнение</a></div></li>
      <?php if (is_null($data->get_recall())) : ?>
        <li class="item"><div class="block current">Отбой</div></li>
      <?php endif; ?>
      <li class="item"><div class="block"><a href="orientation.php?id=<?= $id ?>">Редактировать</a></div></li>
      <li class="item"><div class="block"><a href="mailing.php?id=<?= $id ?>">Рассылка</a></div></li>
    </ul>
  </div>
  
  <div class="input_row">
    <form type="json" id="recall_form">
      <input type="hidden" name="method" value="orientation"/>
      <input type="hidden" name="form_name" value="recall"/>
      <input type="hidden" name="id" value="<?= $id ?>"/>
      <div class="field_box">
        <span class="field_name">Отбой:</span>
        <?= my_date_field('recall', $data->get_recall()) ?>
      </div>
    </form>
  </div>
  <div class="fieldset recall_block">
    <div class="legenda">Отбой ориентировки:</div>
    <div class="block_form_with_progressbar">
      <div class="input_row">
        <form class="form_with_progressbar" id="upload_doc" file_required="true">
          <?= file_input('upload_file') ?>
          <input type="hidden" name="method" value="recall_orientation"/>
        </form>
      </div>
      <div class="response_place">
        <?php if (!empty($files[4])) echo get_added_files_list_with_info($files[4], 'recall_orientation') ?>
      </div>
    </div>
  </div>
  
  <div class="registration_block">
    <div class="add_button_box" form="recall_form">
      <div class="button_block"><span class="button_name">Сохранить</span></div>
    </div>
    <div class="response_place"></div>
  </div>
</div>
<?php require_once($_SERVER['DOCUMENT_ROOT'].'/footer.php'); ?>