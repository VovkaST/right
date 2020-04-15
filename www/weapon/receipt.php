<?php
$need_auth = 0;
require ($_SERVER['DOCUMENT_ROOT'].'/sessions.php');

if (empty($_SESSION['user']['admin']) and empty($_SESSION['user']['weapon'])) {
  define('ERROR', 'Недостаточно прав.');
  require_once($_SERVER['DOCUMENT_ROOT'].'/error/error.php');
}

$breadcrumbs = array(
  'Главная' => '/index.php',
  'Учет "Оружие"' => 'index.php',
  'Квитанция' => ''
);

$page_title = 'Квитанция "Оружие"';

require_once('require.php');

if (isset($_GET['id']) and is_numeric($_GET['id'])) {
  $data = new WeaponAccount(floor(abs($_GET['id'])));
  
  if (is_null($data->get_id())) {
    header('Location: /error/404.php');
  }
  
  $data->full_data();
  if (isset($_SESSION['WeaponAccount']))
    unset($_SESSION['WeaponAccount']);
  $_COOKIE['weapon_page'] = 'firearms';
  $_SESSION['WeaponAccount']['object'] = serialize($data);
} else {
  if (isset($_SESSION['WeaponAccount']['object'])) {
    $data = unserialize($_SESSION['WeaponAccount']['object']);
    if (!is_null($data->get_id())) {
      $data = new WeaponAccount($data->get_id());
      $data->full_data();
    }
  } else {
    $data = new WeaponAccount();
    $_COOKIE['weapon_page'] = 'firearms';
  }
}
$kusp = $data->get_kusp();
$cc = $data->get_crime_case();

require_once($_SERVER['DOCUMENT_ROOT'].'/header.php');
?>
<script>
</script>
<style>

</style>
<div class="header_row"><?= $_SESSION['user']['ovd'] ?></div>
<div class="weapons">
  <div class="fieldset main_block">
    <form class="sess_form" type="json">
      
      <input type="hidden" name="form_name" value="main_form"/>
      <div class="input_row">
        <div class="field_box">
          <span class="field_name">№ квитанции:</span>
          <div class="input_field_block">
            <input type="text" id="reg_number" name="number" autocomplete="off" value="<?php if (!is_null($data->get_reg_number())) echo $data->get_reg_number() ?>"/>
          </div>
        </div>
        <div class="field_box">
          <span class="field_name">от </span>
          <?= (is_null($data->get_reg_date())) ? my_date_field('reg_date') : my_date_field('reg_date', $data->get_reg_date()) ?>
        </div>
        
        <div class="field_box">
          <span class="field_name">№ КУСП:</span>
          <div class="input_field_block">
            <input type="text" name="kusp[number]" autocomplete="off" value="<?= (!empty($kusp)) ? $kusp->get_kusp() : null ?>"/>
          </div>
        </div>
        <div class="field_box">
          <span class="field_name">от </span>
          <?= my_date_field('kusp[date]', ((!empty($kusp)) ? $kusp->get_date() : null)) ?>
        </div>
      </div>

      <div class="input_row">
        <div class="field_box">
          <span class="field_name">Основание принятия:</span>
          <?= my_select('base_receiving', 'spr_base_receiving_weapons', ((!is_null($data->get_base_receiving())) ? $data->get_base_receiving() : null)) ?>
        </div>
        <div class="field_box">
          <span class="field_name">Цель помещения:</span>
          <?= my_select('purpose_placing', 'spr_purpose_placing', ((!is_null($data->get_purpose_placing())) ? $data->get_purpose_placing() : null)) ?>
        </div>
      </div>
      
      
      <div class="input_row">
        <div class="field_box" title="Обязательно для ввода при приеме вещественных доказательств по уг.делу">
          <span class="field_name">№ уг.дела:*</span>
          <div class="input_field_block">
            <input type="text" name="cc[crime_case_number]" autocomplete="off" maxlength="17" value="<?= (!empty($cc)) ? $cc->get_number() : null ?>"/>
          </div>
        </div>
        <div class="field_box" title="Обязательно для ввода при приеме вещественных доказательств по уг.делу">
          <span class="field_name">от </span>
          <?= my_date_field('cc[crim_case_date]', ((!empty($cc)) ? $cc->get_date() : null)) ?>
        </div>
        
        <div class="field_box">
          <span class="field_name">Вх.ДИР:</span>
          <div class="input_field_block">
            <input type="text" name="incoming_number" autocomplete="off" value="<?= (!is_null($data->get_incoming_number()) ? $data->get_incoming_number() : null) ?>"/>
          </div>
        </div>
        <div class="field_box">
          <span class="field_name">от </span>
          <?= my_date_field('incoming_date', (!is_null($data->get_incoming_date()) ? $data->get_incoming_date() : null)) ?>
        </div>
      </div>
      
      <hr color="#C6C6C6" size="0px">
      <div>
        <ul class="prim">
          <li>* Обязательно для ввода при приеме вещественных доказательств по уг.делу. <span style="color: red"> С 2018 года - 17 цифр </span> </li>
        </ul>
      </div>
    </form>
  </div>
  
  <div class="actions_block links_block">
    <ul class="actions_list">
      <li class="item">Разделы:</li>
      <li class="item firearms">
        <div class="block">
          <a href="#" method="pages" id="firearms">
            <span class="page_title">Огнестрельное оружие<span class="count"><?php if ($data->get_count(1)) echo ' ('.$data->get_count(1).')' ?></span></span>
          </a>
        </div>
      </li>
      <li class="item ammunition">
        <div class="block">
          <a href="#" method="pages" id="ammunition">
            <span class="page_title">Боеприпасы<span class="count"><?php if ($data->get_count(2)) echo ' ('.$data->get_count(2).')' ?></span></span>
          </a>
        </div>
      </li>
      <li class="item explosives">
        <div class="block">
          <a href="#" method="pages" id="explosives">
            <span class="page_title">Взрывные устр-ва (вещества)<span class="count"><?php if ($data->get_count(3)) echo ' ('.$data->get_count(3).')' ?></span></span>
          </a>
        </div>
      </li>
      <li class="item steelarms">
        <div class="block">
          <a href="#" method="pages" id="steelarms">
            <span class="page_title">Холодное оружие<span class="count"><?php if ($data->get_count(4)) echo ' ('.$data->get_count(4).')' ?></span></span>
          </a>
        </div>
      </li>
    </ul>
  </div>
  
  <div id="pages">
  
    <?php if (isset($_COOKIE['weapon_page'])) : ?>
      <script>$(window).load( function(){ egrul_ip_data( '<?= 'method=pages&id='.$_COOKIE['weapon_page'] ?>' ); } );</script>
    <?php endif; ?>
  
  </div>
  <?php if ($_SESSION['user']['admin'] == 1 or $_SESSION['user']['ovd_id'] != 59) : ?>
    <div class="registration_block">
      <form type="json" wait_back="true">
        <input type="hidden" name="method" value="account_save"/>
        <div class="add_button_box">
          <div class="button_block"><span class="button_name">Сохранить квитанцию</span></div>
        </div>
      </form>
      <div class="response_place">
      </div>
    </div>
  <?php endif; ?>
</div>

  
<?php require_once($_SERVER['DOCUMENT_ROOT'].'/footer.php'); ?>