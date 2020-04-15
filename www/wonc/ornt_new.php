<?php
$need_auth = 0;
require ($_SERVER['DOCUMENT_ROOT'].'/sessions.php');

if (empty($_SESSION['user']['admin']) and empty($_SESSION['user']['ornt_create'])) {
  define('ERROR', 'Недостаточно прав.');
  require_once($_SERVER['DOCUMENT_ROOT'].'/error/error.php');
}

require_once('require.php');

$data = $id = $images = null;

$breadcrumbs = array(
  'Главная' => '/index.php',
  'Текстовый массив' => 'index.php',
  'Ориентировки' => 'orientations.php',
  '' => ''
);
$page_title = 'Регистрация ориентировки';

require_once($_SERVER['DOCUMENT_ROOT'].'/header.php');
?>
<style>
</style>
<center><span style="font-size: 1.2em;"><strong>Регистрация ориентировки</strong></span></center>
<hr color="#C6C6C6" size="0px"/>

<div class="work_on_the_crime new_orientation">
  <form type="json">
    <input type="hidden" name="method" value="orientation"/>
    <input type="hidden" name="form_name" value="pre-registration"/>

    <div class="input_row">
      <div class="field_box">
        <span class="field_name">ОВД-инициатор:</span>
        <?= my_select('ovd', 'spr_ovd') ?>
      </div>
      <div class="field_box">
        <span class="field_name">Дата:</span>
        <?= my_date_field('date', date('d.m.Y')) ?>
      </div>
    </div>
    
    <div class="fieldset marking">
      <div class="legenda">Вид происшествия:</div>
      <div class="input_row">
        <div class="field_box">
          <span class="field_name">ст.:</span>
          <?= my_select('uk', 'spr_uk', null, null, 150) ?>
        </div>
        <div class="marking_variants">
          <input type="hidden" name="marking" value="0"/>
          <div>
            <label><input type="radio" name="marking" value="1"/>Розыск утратившего связь с родственниками</label>
            <label><input type="radio" name="marking" value="2"/>Розыск без вести пропавшего</label>
          </div>
          <div>
            <label><input type="radio" name="marking" value="3"/>Установление личности неопознанного трупа</label>
            <label><input type="radio" name="marking" value="4"/>Административное правонарушение</label>
          </div>
        </div>
      </div>
    </div>
    
    <div class="registration_block">
      <div class="add_button_box">
        <div class="button_block"><span class="button_name">Зарегистрировать</span></div>
      </div>
      <div class="response_place"></div>
    </div>
  </form>
</div>
<?php require_once($_SERVER['DOCUMENT_ROOT'].'/footer.php'); ?>