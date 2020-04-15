<?php
$need_auth = 0;
require ($_SERVER['DOCUMENT_ROOT'].'/sessions.php');

require_once('require.php');
$data = $id = $files = $ovd = null;

if (!empty($_GET['ovd']))
  $ovd = to_integer($_GET['ovd']);

if (empty($ovd)) {
  define('ERROR', 'Что-то пошло не так...');
  require_once($_SERVER['DOCUMENT_ROOT'].'/error/error.php');
}
  
if (!empty($_GET['id'])) {
  $id = to_integer($_GET['id']);
}

if (!is_null($id)) {
  if (isset($_SESSION['reference']))
    unset($_SESSION['reference']);
  $data = new Reference($id);
  $data->full_data();
  $_SESSION['reference']['object'] = serialize($data);
} else {
  if (!empty($_SESSION['reference']['object']))
    $data = unserialize($_SESSION['reference']['object']);
}

if (empty($data)) {
  $data = new Reference();
  $data->set_ovd($ovd);
  $_SESSION['reference']['object'] = serialize($data);
}
$files = $data->get_files_array(true);

$breadcrumbs = array(
  'Главная' => '/index.php',
  'Текстовый массив' => 'index.php',
  'Обзорные справки по преступлениям' => 'references.php',
  'Справка' => ''
  
);
$page_title = 'Ввод информации "Справка"';
require_once($_SERVER['DOCUMENT_ROOT'].'/header.php');
?>
<style>

</style>

<center><span style="font-size: 1.2em;"><strong>Ввод информации "Обзорная справка" &mdash; <?= $data->get_ovd_string() ?></strong></span></center>
<hr color="#C6C6C6" size="0px"/>

<div class="work_on_the_crime reference">
  
  <div class="fieldset messages_block">
    <div class="legenda">По сообщениям, зарегистрированным в КУСП:</div>
    <div class="input_row">
      <form type="json">
        <input type="hidden" name="form_name" value="kusp_form"/>
        <input type="hidden" name="method" value="reference"/>
        <div class="field_box">
          <span class="field_name">ОВД:</span>
          <?= my_select('ovd', 'spr_ovd') ?>
        </div>
        <div class="field_box">
          <span class="field_name">Рег.№:</span>
          <div class="input_field_block">
            <input type="text" name="kusp" autocomplete="off"/>
          </div>
        </div>
        <div class="field_box">
          <span class="field_name">Дата регистрации:</span>
          <?= my_date_field('date') ?>
        </div>
        
        <div class="add_button_box">
          <div class="button_block"><span class="button_name">Добавить</span></div>
        </div>
      </form>
    </div>
    <div class="added_row">
      <?= related_kusp($data->get_kusp_array(), 'reference'); ?>
    </div>
  </div>
  
  <form type="json" class="main_form">
    <input type="hidden" name="method" value="reference"/>
    <input type="hidden" name="form_name" value="crime_case_form"/>

    <div class="fieldset messages_block">
      <div class="legenda">По уголовному делу:</div>
      <div class="input_row">
        <div class="field_box">
          <span class="field_name">ОВД:</span>
          <?= (!is_null($data->get_crime_case_ovd())) ? my_select('ovd', 'spr_ovd', $data->get_crime_case_ovd()) : my_select('ovd', 'spr_ovd') ?>
        </div>
        <div class="field_box">
          <span class="field_name">Рег.№:</span>
          <div class="input_field_block">
            <input type="text" name="crim_case_number" autocomplete="off" value="<?= $data->get_crime_case_number() ?>"/>
          </div>
        </div>
        <div class="field_box">
          <span class="field_name">Дата регистрации:</span>
          <?= my_date_field('crim_case_date', $data->get_crime_case_date()) ?>
        </div>
      </div>
    </div>
  
  </form>
  
  <div class="fieldset reference_block">
    <div class="legenda">Обзорная справка:</div>
    <div class="block_form_with_progressbar">
      <div class="input_row">
        <form class="form_with_progressbar" id="upload_doc" file_required="true">
          <?= file_input('upload_file') ?>
          <input type="hidden" name="method" value="reference"/>
        </form>
      </div>
      <div class="response_place">
        <?php if (!empty($files[5])) echo get_added_files_list_with_info($files[5], 'reference') ?>
      </div>
    </div>
  </div>
  
  <div class="registration_block">
    <form type="json">
      <input type="hidden" name="method" value="reference"/>
      <input type="hidden" name="form_name" value="registration"/>
      <input type="hidden" name="ovd" value="<?= $data->get_ovd() ?>"/>
      <div class="add_button_box">
        <div class="button_block"><span class="button_name">Сохранить</span></div>
      </div>
    </form>
    <div class="response_place"></div>
  </div>
  
</div>
<?php require_once($_SERVER['DOCUMENT_ROOT'].'/footer.php'); ?>