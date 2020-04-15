<?php
if (isset($_SESSION['WeaponAccount']['temp'][3])) {
  $data = unserialize($_SESSION['WeaponAccount']['temp'][3]);
  if ($data->get_type() != 3)
    $data = null;
}
if (empty($data))
  $data = new Weapon();
$acc = (isset($_SESSION['WeaponAccount']['object'])) ? unserialize($_SESSION['WeaponAccount']['object']) : new WeaponAccount();

setcookie('weapon_page', 'explosives', 0, '/weapon/', '...');
ob_start();
?>
<div class="fieldset explosives bookmark_block">
  <div class="bookmark_list">
    <div class="item current" id="decision" target="wpn_decision"><span>Наименования</span></div>
    <?php if (count($acc->get_weapon(3)) > 0) : ?>
      <div class="item" id="history" target="wpn_history">
        <span>
          Движение
          <span class="count"><?php if ($acc->get_weapon_history(3)) echo '('.count($acc->get_weapon_history(3)).')' ?></span>
        </span>
      </div>
    <?php endif; ?>
  </div>
  
  <div class="wpn_decision">
    <div class="fieldset">
      <span class="close_message" title="Сбросить данные" onclick=" $('#reset_form').trigger('submit'); "><span class="text"><i>Очистить </i></span>×</span>
      <form class="sess_form" type="json">
        <input type="hidden" name="form_name" value="explosives"/>
      
        <div class="input_row">
          <div class="label-box">
            <div class="field_box">
              <span class="field_name">Вид: </span>
            </div>
            <div class="field_box <?php if ($data->get_sort() == 16) echo 'checked' ?>">
              <label class="field_name"><input type="radio" name="sort" value="16" <?php if ($data->get_sort() == 16) echo 'checked' ?>/>Граната</label>
            </div>
            <div class="field_box <?php if ($data->get_sort() == 17) echo 'checked' ?>">
              <label class="field_name"><input type="radio" name="sort" value="17" <?php if ($data->get_sort() == 17) echo 'checked' ?>/>Мина</label>
            </div>
            <div class="field_box <?php if ($data->get_sort() == 18) echo 'checked' ?>">
              <label class="field_name"><input type="radio" name="sort" value="18" <?php if ($data->get_sort() == 18) echo 'checked' ?>/>СВУ</label>
            </div>
            <div class="field_box <?php if ($data->get_sort() == 19) echo 'checked' ?>">
              <label class="field_name"><input type="radio" name="sort" value="19" <?php if ($data->get_sort() == 19) echo 'checked' ?>/>Взрывчатое вещество</label>
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
        <input type="hidden" name="object" value="explosives"/>
        <input type="hidden" name="method" value="add_item"/>
      </form>
      <form type="json" id="reset_form">
        <input type="hidden" name="object" value="explosives"/>
        <input type="hidden" name="method" value="reset"/>
      </form>
    </div>
    
    <div class="added_items">
      <?php if (!empty($acc)) :
        $i = 1;
        foreach ($acc->get_weapon(3) as $n => $wpn) : ?>
          <div n="<?= $n ?>" <?= (is_null($wpn->get_id())) ? 'class="added_relation new" title="Несохраненная запись"' : 'class="added_relation"' ?>>
            <span class="order_number"><?= $i++ ?></span>. <b><?= $wpn->get_sort_string() ?></b>: 
              серия &ndash; <i><?= $wpn->get_series() ?></i>, 
              № &ndash; <i><?= $wpn->get_number() ?></i>.
              Хранилище: <?= $wpn->get_storage() ?>.
              <i><?= (!is_null($wpn->get_note())) ? '(прим.: '.$wpn->get_note().')' : null ?></i>
            <span class="delete_relation" id="<?= $n ?>" group="explosives">×</span>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>
  
<?php 
  if (count($acc->get_weapon(3)) > 0)
    require('form_decision.php'); 
?>
</div>

<?php
$content = ob_get_contents();
ob_end_clean();
?>