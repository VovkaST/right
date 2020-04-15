<?php
$need_auth = 0;
require ($_SERVER['DOCUMENT_ROOT'].'/sessions.php');

require_once('require.php');
$data = $id = $files = $ovd = null;
if (!empty($_GET['id'])) {
  $id = to_integer($_GET['id']);
}

if (!is_null($id)) {
  if (isset($_SESSION['orientation']))
    unset($_SESSION['orientation']);
  $data = new Orientation($id);
  $data->full_data();
  $_SESSION['orientation']['object'] = serialize($data);
} else {
  if (!empty($_SESSION['orientation']['object']))
    $data = unserialize($_SESSION['orientation']['object']);
}

if (empty($data)) {
  $data = new Orientation();
  if (!empty($_GET['ovd'])) {
    $ovd = to_integer($_GET['ovd']);
    $data->set_ovd($ovd);
  }
}
//var_dump($data)


$files = $data->get_files_array(null, true);
/*<pre><?= var_dump($data) ?></pre>*/

$breadcrumbs = array(
  'Главная' => '/index.php',
  'Текстовый массив' => 'index.php',
  'Ориентировки' => 'orientations.php'
);
if (!is_null($data->get_ovd())) {
  $breadcrumbs['Ориентировки по ОВД'] = 'ornts_list.php?ovd='.$data->get_ovd();
}
$breadcrumbs[''] = '';

$page_title = (empty($id)) ? 'Ввод информации "Ориентировка"' : 'Ориентировка №'.$data->get_number().' (Редактирование)';
require_once($_SERVER['DOCUMENT_ROOT'].'/header.php');
?>
<style>
</style>

<center><span style="font-size: 1.2em;"><strong><?= $page_title ?></strong></span></center>
<hr color="#C6C6C6" size="0px"/>

<div class="work_on_the_crime orientation">
  
  <?php if ($id) : ?>
    <div class="actions_block">
      <ul class="actions_list">
        <li class="item">Действия:</li>
        <li class="item"><div class="block"><a href="ornt_view.php?id=<?= $id ?>">Просмотр</a></div></li>
        <li class="item"><div class="block"><a href="ornt_addon.php?id=<?= $id ?>">Дополнение</a></div></li>
        <?php if (is_null($data->get_recall())) : ?>
          <li class="item"><div class="block"><a href="ornt_recall.php?id=<?= $id ?>">Отбой</a></div></li>
        <?php endif; ?>
        <li class="item"><div class="block current">Редактировать</div></li>
        <li class="item"><div class="block"><a href="mailing.php?id=<?= $id ?>">Рассылка</a></div></li>
      </ul>
    </div>
  <?php endif; ?>
  <form type="json" class="main_form">
    <input type="hidden" name="method" value="orientation"/>
    <input type="hidden" name="form_name" value="main_form"/>

    <div class="input_row">
      <div class="field_box">
        <span class="field_name">ОВД-инициатор:</span>
        <?php echo (is_null($data->get_ovd())) ? my_select('ovd', 'spr_ovd') : my_select('ovd', 'spr_ovd', $data->get_ovd()) ?>
      </div>
      <div class="field_box">
        <span class="field_name">Дата:</span>
        <?php echo (is_null($data->get_date())) ? my_date_field('date') : my_date_field('date', date('d.m.Y', strtotime($data->get_date()))) ?>
      </div>
      <div class="field_box">
        <span class="field_name">№:</span>
        <div class="input_field_block">
          <input type="text" id="regnumber" name="number" autocomplete="off"<?php if (!is_null($data->get_number())) echo 'value="'.$data->get_number().'"' ?>/>
        </div>
      </div>
      <div class="field_box">
        <input type="hidden" name="wonumber" value="0"/>
        <label class="field_name"><input type="checkbox" id="wonumber" name="wonumber" value="1"<?php if ($data->get_wonumber() != 0) echo ' checked' ?>/>Без номера</label>
      </div>
    </div>
    
    <div class="fieldset marking">
      <div class="legenda">Вид происшествия:</div>
      <div class="input_row">
        <div class="field_box">
          <span class="field_name">ст.:</span>
          <?php echo (is_null($data->get_uk())) ? my_select('uk', 'spr_uk', null, null, 150) : my_select('uk', 'spr_uk', $data->get_uk(), null, 150) ?>
        </div>
        <div class="marking_variants">
          <input type="hidden" name="marking" value="0"/>
          <div>
            <label><input type="radio" name="marking" value="1"<?php if ($data->get_marking() == 1) echo ' checked' ?>/>Розыск утратившего связь с родственниками</label>
            <label><input type="radio" name="marking" value="2"<?php if ($data->get_marking() == 2) echo ' checked' ?>/>Розыск без вести пропавшего</label>
          </div>
          <div>
            <label><input type="radio" name="marking" value="3"<?php if ($data->get_marking() == 3) echo ' checked' ?>/>Установление личности неопознанного трупа</label>
            <label><input type="radio" name="marking" value="4"<?php if ($data->get_marking() == 4) echo ' checked' ?>/>Адм. правонарушение</label>
            <label><input type="radio" name="marking" value="5"<?php if ($data->get_marking() == 5) echo ' checked' ?>/>Утрата вещи</label>
          </div>
        </div>
      </div>
    </div>
  
  </form>
  
  <div class="fieldset messages_block">
    <div class="legenda">По сообщениям, зарегистрированным в КУСП:</div>
    <div class="input_row">
      <form type="json">
        <input type="hidden" name="form_name" value="kusp_form"/>
        <input type="hidden" name="method" value="orientation"/>
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
      <?= related_kusp($data->get_kusp_array(), 'orientation'); ?>
    </div>
  </div>
  
  <form type="json" class="main_form">
    <input type="hidden" name="method" value="orientation"/>
    <input type="hidden" name="form_name" value="crime_case_form"/>
    <div class="fieldset messages_block">
      <div class="legenda">По уголовному делу:</div>
      <div class="input_row">
        <div class="field_box">
          <span class="field_name">ОВД:</span>
          <?php
            if (!is_null($data->get_crime_case_ovd())) {
              echo my_select('ovd', 'spr_ovd', $data->get_crime_case_ovd());
            } else {
              echo my_select('ovd', 'spr_ovd');
            }
          ?>
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
  
  <div class="fieldset orientation_block">
    <div class="legenda">Ориентировка:</div>
    <div class="block_form_with_progressbar">
      <div class="input_row">
        <form class="form_with_progressbar" id="upload_doc" file_required="true">
          <?= file_input('upload_file') ?>
          <input type="hidden" name="method" value="orientation"/>
        </form>
      </div>
      <div class="response_place">
        <?php if (!empty($files[1])) echo get_added_files_list_with_info($files[1], 'orientation') ?>
      </div>
    </div>
  </div>
  
  <div class="fieldset video_block">
    <div class="legenda">Видео-ориентировка (изъятое видео):</div>
    <div class="block_form_with_progressbar">
      <div class="input_row">
        <form class="form_with_progressbar" id="upload_video" file_required="true">
          <?= file_input('upload_file') ?>
          <input type="hidden" name="method" value="video_orientation"/>
        </form>
      </div>
      <div class="response_place">
        <?php if (!empty($files[2])) echo get_added_files_list_with_info($files[2], 'video_orientation') ?>
      </div>
    </div>
  </div>
  
  <?php if (!empty($files[3])) : ?>
    <div class="fieldset addon_block">
      <div class="legenda">Дополнение к ориентировке:</div>
      <div class="block_form_with_progressbar">
        <div class="input_row">
          <form class="form_with_progressbar" id="upload_doc" file_required="true">
            <?= file_input('upload_file') ?>
            <input type="hidden" name="method" value="addon_orientation"/>
          </form>
        </div>
        <div class="response_place">
          <?= get_added_files_list_with_info($files[3], 'addon_orientation') ?>
        </div>
      </div>
    </div>
  <?php endif; ?>
  
  <?php if (!empty($files[4]) or !empty($id)) : ?>
    <div class="fieldset recall_block">
      <div class="legenda">Отбой ориентировки:</div>
      <form type="json" class="main_form">
        <input type="hidden" name="method" value="orientation"/>
        <input type="hidden" name="form_name" value="recall_form"/>
        <div class="field_box">
          <span class="field_name">Дата:</span>
          <?= my_date_field('recall', $data->get_recall()) ?>
        </div>
      </form>
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
  <?php endif; ?>
  
  <div class="registration_block">
    <form type="json">
      <input type="hidden" name="method" value="orientation"/>
      <input type="hidden" name="form_name" value="registration"/>
      <div class="add_button_box">
        <div class="button_block"><span class="button_name">Сохранить</span></div>
      </div>
    </form>
    <div class="response_place"></div>
  </div>
  
</div>
<?php require_once($_SERVER['DOCUMENT_ROOT'].'/footer.php'); ?>