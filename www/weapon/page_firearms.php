<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/sessions.php');

$q_marking = '
  SELECT
    m.`id`, m.`marking`
  FROM
    `spr_marking` as m
  WHERE
    m.`category` = 7 AND
    m.`obj_type` = 0 AND
    m.`ais` = 0
';

$acc = (isset($_SESSION['WeaponAccount']['object'])) ? unserialize($_SESSION['WeaponAccount']['object']) : null;

if (!empty($acc)) {
  $data = $acc->get_weapon(1);
  if (isset($data[0]))
    $data = $data[0];
  if (empty($data))
    $data = new Weapon();
} else {
  $acc = new WeaponAccount();
  $data = new Weapon();
}


setcookie('weapon_page', 'firearms', 0, '/weapon/', '...');

ob_start();
?>
<div class="fieldset firearms bookmark_block">
  <div class="bookmark_list">
    <div class="item current" id="decision" target="wpn_decision"><span>Наименование</span></div>
    <?php if (count($acc->get_weapon(1)) > 0) : ?>
      <div class="item" id="history" target="wpn_history">
        <span>
          Движение
          <span class="count"><?php if ($acc->get_weapon_history(1)) echo '('.count($acc->get_weapon_history(1)).')' ?></span>
        </span>
      </div>
    <?php endif; ?>
  </div>
  
  <div class="wpn_decision">
    <span class="close_message" title="Сбросить данные" onclick=" $('#reset_form').trigger('submit'); "><span class="text"><i>Очистить </i></span>×</span>
    <form class="sess_form" type="json">
      <input type="hidden" name="form_name" value="firearms"/>
      
      <div class="input_row">
        <div class="field_box">
          <span class="field_name">Тип: </span>
          <?= my_select('sort', 'spr_weapon_sorts_fa', $data->get_sort()) ?>
        </div>
        
        <div class="field_box">
          <input type="hidden" name="unknown_model" value="0"/>
          <label class="field_name">
            <input type="checkbox" id="fa_unkmodel" name="unknown_model" value="1" <?php if (!is_null($data->get_type()) and !$data->is_model()) echo 'checked' ?>/>Модель не установлена
          </label>
        </div>
      </div>
      
      <table width="100%">
        <tr>
          <td>
            <div class="field_box">
                <span class="field_name">Модель:</span>
                <div class="input_field_block" style="width: 480px" validate>
                  <input type="text" name="value" class="ajax_quick_search" ajax_group="fa_model" autocomplete="off" value="<?= $data->get_model_string() ?>"/>
                  <div class="ajax_search_result"></div>
                  <input type="hidden" id="fa_model" name="model" ajax_group="fa_model" value="<?= $data->get_model() ?>"/>
                </div>
              </div>
            <div class="input_row">
              <div class="field_box">
                <span class="field_name">Год:</span>
                <div class="input_field_block" style="width: 60px">
                  <input type="text" name="manufacture_year" autocomplete="off" maxlength="4" value="<?= $data->get_manufacture_year() ?>"/>
                </div>
              </div>
              <div class="field_box">
                <span class="field_name">Серия:</span>
                <div class="input_field_block" style="width: 80px">
                  <input type="text" name="series" autocomplete="off" maxlength="10" value="<?= $data->get_series() ?>"/>
                </div>
              </div>
              <div class="field_box">
                <span class="field_name">Номер:</span>
                <div class="input_field_block">
                  <input type="text" name="number" autocomplete="off" maxlength="50" value="<?= $data->get_number() ?>"/>
                </div>
              </div>
            </div>
            <div class="input_row">
              <div class="field_box">
                <span class="field_name">Хранилище:</span>
                <div class="input_field_block" style="width: 105px">
                  <input type="text" name="storage" autocomplete="off" maxlength="10" value="<?= $data->get_storage() ?>"/>
                </div>
              </div>
              <input type="hidden" name="add_attributes" value="0"/>
            </div>
            
            <div>Примечание:</div>
            <textarea name="note" rows="3"><?= $data->get_note() ?></textarea>
          </td>
          <td width="450px">
            <div class="fieldset addititional">
              <div class="legenda">Дополнительные характеристики:</div>
              <div class="input_row">
                <div class="label-box">
                  <div class="field_box <?php if ($data->get_add_attributes() == 40) echo 'checked' ?>">
                    <label class="field_name"><input type="radio" name="add_attributes" value="40" <?php if ($data->get_add_attributes() == 40) echo 'checked' ?>/>Обрез</label>
                  </div>
                  <div class="field_box <?php if ($data->get_add_attributes() == 41) echo 'checked' ?>">
                    <label class="field_name"><input type="radio" name="add_attributes" value="41" <?php if ($data->get_add_attributes() == 41) echo 'checked' ?>/>Переделка</label>
                  </div>
                  <div class="field_box <?php if ($data->get_add_attributes() == 42) echo 'checked' ?>">
                    <label class="field_name"><input type="radio" name="add_attributes" value="42" <?php if ($data->get_add_attributes() == 42) echo 'checked' ?>/>Деталь, часть</label>
                  </div>
                </div>

              </div>
              <div class="input_row">
                <div class="field_box">
                  <span class="field_name">Ствол: серия </span>
                  <div class="input_field_block" style="width: 80px">
                    <input type="text" name="barrel_series" autocomplete="off" maxlength="10" value="<?= $data->get_barrel_series() ?>"/>
                  </div>
                </div>
                <div class="field_box">
                  <span class="field_name">номер </span>
                  <div class="input_field_block">
                    <input type="text" name="barrel_number" autocomplete="off" maxlength="50" value="<?= $data->get_barrel_number() ?>"/>
                  </div>
                </div>
              </div>
              <div class="input_row">
                <div class="field_box">
                  <span class="field_name">Цевье: серия </span>
                  <div class="input_field_block" style="width: 80px">
                    <input type="text" name="fore-end_serial" autocomplete="off" maxlength="10" value="<?= $data->get_fore_end_serial() ?>"/>
                  </div>
                </div>
                <div class="field_box">
                  <span class="field_name">номер </span>
                  <div class="input_field_block">
                    <input type="text" name="fore-end_number" autocomplete="off" maxlength="50" value="<?= $data->get_fore_end_number() ?>"/>
                  </div>
                </div>
              </div>
              <div class="input_row">
                <div class="field_box">
                  <span class="field_name">Колодка: серия </span>
                  <div class="input_field_block" style="width: 80px">
                    <input type="text" name="shoe_serial" autocomplete="off" maxlength="10" value="<?= $data->get_shoe_serial() ?>"/>
                  </div>
                </div>
                <div class="field_box">
                  <span class="field_name">номер </span>
                  <div class="input_field_block">
                    <input type="text" name="shoe_number" autocomplete="off" maxlength="50" value="<?= $data->get_shoe_number() ?>"/>
                  </div>
                </div>
              </div>
            </div>
          </td>
        </tr>
      </table>
    </form>
  </div>
  
<?php 
  if (count($acc->get_weapon(1)) > 0)
    require('form_decision.php'); 
?>
  
  <form type="json" id="reset_form">
    <input type="hidden" name="object" value="firearms"/>
    <input type="hidden" name="method" value="reset"/>
  </form>
</div>
<?php
$content = ob_get_contents();
ob_end_clean();
?>