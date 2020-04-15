<?php
if (isset($_SESSION['WeaponAccount']['temp'][4])) {
  $data = unserialize($_SESSION['WeaponAccount']['temp'][4]);
  if ($data->get_type() != 4)
    $data = null;
}
if (empty($data))
  $data = new Weapon();
$acc = (isset($_SESSION['WeaponAccount']['object'])) ? unserialize($_SESSION['WeaponAccount']['object']) : new WeaponAccount();

setcookie('weapon_page', 'steelarms', 0, '/weapon/', '...');
ob_start();
?>
<div class="fieldset steelarms bookmark_block">
  <div class="bookmark_list">
    <div class="item current" id="decision" target="wpn_decision"><span>Наименования</span></div>
    <?php if (count($acc->get_weapon(4)) > 0) : ?>
      <div class="item" id="history" target="wpn_history">
        <span>
          Движение
          <span class="count"><?php if ($acc->get_weapon_history(4)) echo '('.count($acc->get_weapon_history(4)).')' ?></span>
        </span>
      </div>
    <?php endif; ?>
  </div>
  
  <div class="wpn_decision">
    <div class="fieldset">
      <span class="close_message" title="Сбросить данные" onclick=" $('#reset_form').trigger('submit'); "><span class="text"><i>Очистить </i></span>×</span>
      <form class="sess_form" type="json">
        <input type="hidden" name="form_name" value="steelarms"/>
      
        <div class="input_row">
          <div class="label-box">
            <div class="field_box">
              <span class="field_name">Вид:</span>
            </div>
            <div class="field_box <?php if ($data->get_sort() == 20) echo 'checked' ?>">
              <label class="field_name"><input type="radio" name="sort" value="20" <?php if ($data->get_sort() == 20) echo 'checked' ?>/>Нож</label>
            </div>
            <div class="field_box <?php if ($data->get_sort() == 21) echo 'checked' ?>">
              <label class="field_name"><input type="radio" name="sort" value="21" <?php if ($data->get_sort() == 21) echo 'checked' ?>/>Клинок</label>
            </div>
            <div class="field_box <?php if ($data->get_sort() == 22) echo 'checked' ?>">
              <label class="field_name"><input type="radio" name="sort" value="22" <?php if ($data->get_sort() == 22) echo 'checked' ?>/>Кинжал</label>
            </div>
            <div class="field_box <?php if ($data->get_sort() == 23) echo 'checked' ?>">
              <label class="field_name"><input type="radio" name="sort" value="23" <?php if ($data->get_sort() == 23) echo 'checked' ?>/>Иное</label>
            </div>
          </div>
        </div>
        <table width="100%" rules="none" border="0">
          <tr>
            <td width="50%">
              <div>Примечание:</div>
              <textarea name="note" rows="3"><?= $data->get_note() ?></textarea>
            </td>
            <td valign="top">
              <div class="input_row">
                <div class="field_box">
                  <span class="field_name">Серия:*</span>
                  <div class="input_field_block" style="width: 80px">
                    <input type="text" name="series" autocomplete="off" maxlength="10" value="<?= $data->get_series() ?>"/>
                  </div>
                </div>
                <div class="field_box">
                  <span class="field_name">Номер:*</span>
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
                <div class="add_button_box" form="add_item">
                  <div class="button_block"><span class="button_name">Добавить</span></div>
                </div>
              </div>
            </td>
          </tr>
        </table>
      </form>
      
      <hr color="#C6C6C6" size="0px">
      <div>
        <ul class="prim">
          <li>* В случае отсутствия серии и номера, в поле "Номер" указать "б/н"<li>
        </ul>
      </div>
    
      <form type="json" id="add_item">
        <input type="hidden" name="object" value="steelarms"/>
        <input type="hidden" name="method" value="add_item"/>
      </form>
      
      <form type="json" id="reset_form">
        <input type="hidden" name="object" value="steelarms"/>
        <input type="hidden" name="method" value="reset"/>
      </form>
    </div>
  
    <div class="added_items">
      <?php if (isset($_SESSION['WeaponAccount']['object'])) : 
        $acc = unserialize($_SESSION['WeaponAccount']['object']);
        $i = 1;
        foreach ($acc->get_weapon(4) as $n => $wpn) : ?>
          <div n="<?= $n ?>" <?= (is_null($wpn->get_id())) ? 'class="added_relation new" title="Несохраненная запись"' : 'class="added_relation"' ?>>
            <span class="order_number"><?= $i++ ?></span>. <b><?= $wpn->get_sort_string() ?></b>: 
              серия &ndash; <i><?= $wpn->get_series() ?></i>, 
              № &ndash; <i><?= $wpn->get_number() ?></i>.
              Хранилище: <?= $wpn->get_storage() ?>.
              <i><?= (!is_null($wpn->get_note())) ? '(прим.: '.$wpn->get_note().')' : null ?></i>
            <span class="delete_relation" id="<?= $n ?>" group="steelarms">×</span>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>
  
<?php 
  if (count($acc->get_weapon(4)) > 0)
    require('form_decision.php'); 
?>
</div>
<?php
$content = ob_get_contents();
ob_end_clean();
?>