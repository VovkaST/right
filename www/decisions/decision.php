<?php
$need_auth = 0;
require ($_SERVER['DOCUMENT_ROOT'].'/sessions.php');

$breadcrumbs = array(
  'Регистрация электронных копий постановлений об отказе в возбуждении уголовного дела' => 'index.php',
  'Ввод решения' => ''
);

$page_title = 'Результаты формирования';

if (!empty($_SESSION['user']['emp_surname']) and empty($_SESSION['decision']['data']['emp_s'])) $_SESSION['decision']['data']['emp_s'] = $_SESSION['user']['emp_surname'];
if (!empty($_SESSION['user']['emp_name']) and empty($_SESSION['decision']['data']['emp_n'])) $_SESSION['decision']['data']['emp_n'] = $_SESSION['user']['emp_name'];
if (!empty($_SESSION['user']['emp_f_name']) and empty($_SESSION['decision']['data']['emp_fn'])) $_SESSION['decision']['data']['emp_fn'] = $_SESSION['user']['emp_f_name'];

require_once($_SERVER['DOCUMENT_ROOT'].'/header.php');
?>

<script type="text/javascript">
  
  /*
  var cur_date = new Date();
  var m = cur_date.getMonth() + 1;
  alert(cur_date.getDate()+'.'+((m) < 10 ? '0' + m : m)+'.'+cur_date.getFullYear());
  */
  
</script>
<center><span style="font-size: 1.2em;"><strong><?= (empty($_SESSION['decision']['data']['reg'])) ? 'Регистрация электронной копии постановления об отказе в возбуждении уголовного дела' : 'Электронная копия постановления об отказе в возбуждении уголовного дела № '.$_SESSION['decision']['data']['reg'] ?></strong></span></center>
<hr color="#C6C6C6" size="0px"/>
<div class="decision_block">
  <div class="file_block">
    <form file_required="true">
      <?php if (!empty($_SESSION['decision']['data']['file_original'])) : ?>
        <?= file_input('file_upload', true) ?>
        <div class="added_row">
          <span class="added_relation"><?= $_SESSION['decision']['data']['file_original'] ?><span class="delete_relation" group="file">&times;</span></span>
          <div style="clear: left;"></div>
        </div>
      <?php else : ?>
        <?= file_input('file_upload') ?>
        <div class="added_row"><div style="clear: left;"></div></div>
      <?php endif; ?>
    </form>
  </div>
  <div class="fieldset main_block">
    <form class="main_form" type="json">
      <input type="hidden" name="form_name" value="main_form"/>
      <div class="legenda">Основные сведения:</div>
        <div class="ovd_block">
        <div class="fieldset refusal_status">
          <div class="legenda">Статус:</div>
          <label class="row">
            <input type="radio" name="status" value="1"<?php if (!empty($_SESSION['decision']['data']['status']) and $_SESSION['decision']['data']['status'] == 1) echo ' checked'?>/>Первичный
          </label>
          <label class="row">
            <input type="radio" name="status" value="2"<?php if (!empty($_SESSION['decision']['data']['status']) and $_SESSION['decision']['data']['status'] == 2) echo ' checked'?>/>По доп.проверке
          </label>
        </div>
        <div class="field_box">
          <span class="field_name">Служба:</span>
          <?= (empty($_SESSION['decision']['data']['service'])) ? my_select('service', 'spr_slujba', null, 'class="ajax_quick_search" ajax_group="employeer"', 150) : my_select('service', 'spr_slujba', $_SESSION['decision']['data']['service'], 'class="ajax_quick_search" ajax_group="employeer"', 150) ?>
        </div>
        <div class="field_box">
          <span class="field_name">ОВД:</span>
          <?= (empty($_SESSION['decision']['data']['ovd'])) ? my_select('ovd', 'spr_ovd', null, 'class="ajax_quick_search" ajax_group="employeer"') : my_select('ovd', 'spr_ovd', $_SESSION['decision']['data']['ovd'], 'class="ajax_quick_search" ajax_group="employeer"') ?>
        </div>
      </div>
      <div class="employeer_block">
        <div class="field_box" style="width: 285px;">
          <span class="field_name">Сотрудник:</span>
          <div class="input_field_block">
            <input type="text" name="emp_s" class="ajax_quick_search" ajax_group="employeer" autocomplete="off" placeholder="Фамилия"<?php if (!empty($_SESSION['decision']['data']['emp_s'])) echo ' value="'.$_SESSION['decision']['data']['emp_s'].'"' ?>/>
            <div class="ajax_search_result"></div>
          </div>
        </div>
        <div class="field_box">
          <div class="input_field_block">
            <input type="text" name="emp_n" class="ajax_quick_search" ajax_group="employeer" autocomplete="off" placeholder="Имя"<?php if (!empty($_SESSION['decision']['data']['emp_n'])) echo ' value="'.$_SESSION['decision']['data']['emp_n'].'"' ?>/>
            <div class="ajax_search_result"></div>
          </div>
        </div>
        <div class="field_box">
          <div class="input_field_block">
            <input type="text" name="emp_fn" class="ajax_quick_search" ajax_group="employeer" autocomplete="off" placeholder="Отчество"<?php if (!empty($_SESSION['decision']['data']['emp_fn'])) echo ' value="'.$_SESSION['decision']['data']['emp_fn'].'"' ?>/>
            <div class="ajax_search_result"></div>
          </div>
        </div>
      </div>
      <div class="info_block">
        <div class="field_box">
          <span class="field_name">Дата решения:</span>
          <?= (empty($_SESSION['decision']['data']['date'])) ? my_date_field('date') : my_date_field('date', date('d.m.Y', strtotime($_SESSION['decision']['data']['date']))) ?>
        </div>
        <div class="field_box">
          <span class="field_name">Основание: п.</span>
          <?= (empty($_SESSION['decision']['data']['upk'])) ? my_select('upk', 'spr_upk', null, null, 100) : my_select('upk', 'spr_upk', $_SESSION['decision']['data']['upk'], null, 100) ?>
          <span class="field_name">ч.1 ст.24 УПК РФ.</span>
        </div>
      </div>
      <div class="fieldset addititional_block">
        <div class="legenda">Дополнительно:</div>
        <input type="hidden" name="anonymous" value=""/>
        <input type="hidden" name="declarer_employeer" value=""/>
        <input type="hidden" name="missed" value=""/>
        <div class="row">
          <label><input type="checkbox" name="anonymous" value="1"<?php if (!empty($_SESSION['decision']['data']['anonymous'])) echo ' checked'?>/>Анонимное сообщение (заявитель или потерпевший не установлены)</label>
          <label><input type="checkbox" name="declarer_employeer" value="1"<?php if (!empty($_SESSION['decision']['data']['declarer_employeer'])) echo ' checked'?>/>Рапорт сотрудника/сообщение прокурора</label>
        </div>
        <div class="row">
          <label><input type="checkbox" name="missed" value="1"<?php if (!empty($_SESSION['decision']['data']['missed'])) echo ' checked'?>/>По без вести пропавшему</label>
        </div>
      </div>
    </form>
  </div>
  <div class="fieldset messages_block">
    <div class="legenda">По сообщениям, зарегистрированным в КУСП:</div>
    <div class="input_row">
      <form type="json">
        <input type="hidden" name="form_name" value="kusp_form"/>
        <div class="field_box">
          <span class="field_name">ОВД:</span>
          <?= my_select('ovd', 'spr_ovd') ?>
        </div>
        <div class="field_box">
          <span class="field_name">Рег.№:</span>
          <div class="input_field_block">
            <input type="text" name="kusp" autocomplete="off" placeholder="КУСП"/>
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
      <?php if (!empty($_SESSION['decision']['kusp']['list'])) echo related_kusp($_SESSION['decision']['kusp']['list']); ?>
    </div>
  </div>
  <div class="fieldset criminal_block"<?php if (!empty($_SESSION['decision']['data']['missed'])) echo ' style="display: none;"'?>>
    <div class="legenda">По признакам преступления (УК РФ):</div>
    <form type="json">
      <input type="hidden" name="form_name" value="uk_form"/>
      <div class="field_box">
        <span class="field_name">ст.:</span>
        <?= my_select('uk', 'spr_uk', null, null, 150) ?>
      </div>
      <div class="add_button_box">
        <div class="button_block"><span class="button_name">Добавить</span></div>
      </div>
    </form>
    <div class="added_row">
      <?php if (!empty($_SESSION['decision']['uk'])) echo related_uk(); ?>
    </div>
  </div>
  <div class="fieldset objects_block">
    <div class="legenda">Связанные объекты (лица, организации):</div>
    <div class="upk_banner">
      В соответствии с ч.1 ст.148 УПК РФ отказ в возбуждении уголовного дела по основанию, предусмотренному <b>п.2 ч.1 ст.24 УПК РФ</b>, допускается <b>лишь в отношении конкретного лица</b>.
    </div>
    <form type="json">
      <input type="hidden" name="form_name" value="objects_form"/>
      <input type="hidden" ajax_group="man" name="id"/>
      <div class="field_box" style="float: none">
        <span class="field_name">Статус:</span>
        <?= my_select('relative', 'spr_relatives_decision', null, null, 200) ?>
      </div>
      <div class="input_block">
        <div class="input_row">
          <div class="face_block">
            <div class="field_box">
              <span class="field_name">Лицо:</span>
              <div class="input_field_block">
                <input type="text" class="ajax_quick_search" ajax_group="man" name="surname" autocomplete="off" placeholder="Фамилия"/>
                <div class="ajax_search_result"></div>
              </div>
            </div>
            <div class="field_box">
              <div class="input_field_block">
                <input type="text" class="ajax_quick_search" ajax_group="man" name="name" autocomplete="off" placeholder="Имя"/>
                <div class="ajax_search_result"></div>
              </div>
            </div>
            <div class="field_box">
              <div class="input_field_block">
                <input type="text" class="ajax_quick_search" ajax_group="man" name="fath_name" autocomplete="off" placeholder="Отчество"/>
                <div class="ajax_search_result"></div>
              </div>
            </div>
            <div class="field_box">
              <?= my_date_field('borth', null, 'ajax_quick_search', 'ajax_group="man"') ?>
            </div>
          </div>
        </div>
        <div class="input_row">
          <div class="organisation_block">
            <div class="field_box">
              <span class="field_name">Организация:</span>
              <div class="input_field_block">
                <input type="text" name="title" autocomplete="off" placeholder="Организация"/>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="input_block">
        <div class="add_button_box">
          <div class="button_block"><span class="button_name">Добавить</span></div>
        </div>
      </div>
    </form>
    <div class="added_row">
      <?= related_faces_organisations() ?>
    </div>
  </div>
  <div class="registration_block">
    <form type="json">
      <input type="hidden" name="form_name" value="registration"/>
      <div class="add_button_box">
        <div class="button_block"><span class="button_name"><?= (empty($_SESSION['decision']['data']['id'])) ? 'Зарегистрировать' : 'Сохранить' ?></span></div>
      </div>
    </form>
    <div class="registration_number_block">
    </div>
  </div>
  </div>
</div>
<?php require_once($_SERVER['DOCUMENT_ROOT'].'/footer.php'); ?>