<?php
$q_a_sort = array(
  1 => 'Патроны',
  2 => 'Гильзы'
);
if (isset($_SESSION['WeaponAccount']['temp'][2])) {
  $data = unserialize($_SESSION['WeaponAccount']['temp'][2]);
  if ($data->get_type() != 2)
    $data = null;
}
if (empty($data))
  $data = new Weapon();

$acc = (isset($_SESSION['WeaponAccount']['object'])) ? unserialize($_SESSION['WeaponAccount']['object']) : new WeaponAccount();

setcookie('weapon_page', 'ammunition', 0, '/weapon/', '...');
ob_start();
?>
<div class="fieldset ammunition bookmark_block">
  <div class="bookmark_list">
    <div class="item current" id="decision" target="wpn_decision"><span>Наименования</span></div>
    <?php if (count($acc->get_weapon(2)) > 0) : ?>
      <div class="item" id="history" target="wpn_history">
        <span>
          Движение 
          <span class="count"><?php if ($acc->get_weapon_history(2)) echo '('.count($acc->get_weapon_history(2)).')' ?></span>
        </span>
      </div>
    <?php endif; ?>
  </div>
  
  <div class="wpn_decision">
    <div class="fieldset">
      <span class="close_message" title="Сбросить данные" onclick=" $('#reset_form').trigger('submit'); "><span class="text"><i>Очистить </i></span>×</span>
      <form class="sess_form" type="json">
        <input type="hidden" name="form_name" value="ammunition"/>
        <div class="input_row">
          <div class="label-box">
            <input type="hidden" name="sort" value="0"/>
            <div class="field_box">
              <span class="field_name">Вид: </span>
            </div>
            <div class="field_box <?php if ($data->get_sort() == 14) echo 'checked' ?>">
              <label class="field_name"><input type="radio" name="sort" value="14" <?php if ($data->get_sort() == 14) echo 'checked' ?>/>Патрон</label>
            </div>
            <div class="field_box <?php if ($data->get_sort() == 15) echo 'checked' ?>">
              <label class="field_name"><input type="radio" name="sort" value="15" <?php if ($data->get_sort() == 15) echo 'checked' ?>/>Гильза</label>
            </div>
            <div class="field_box <?php if ($data->get_sort() == 24) echo 'checked' ?>">
              <label class="field_name"><input type="radio" name="sort" value="24" <?php if ($data->get_sort() == 24) echo 'checked' ?>/>Порох</label>
            </div>
          </div>
          <div class="field_box">
            <span class="field_name">К оружию: </span>
            <?= my_select('group', 'spr_weapon_groups', $data->get_group(), null, 200) ?>
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
                  <span class="field_name">Калибр:</span>
                  <div class="input_field_block" style="width: 72px">
                    <input type="text" name="caliber" autocomplete="off" maxlength="6" value="<?= $data->get_caliber() ?>"/>
                  </div>
                </div>
                <div class="field_box">
                  <span class="field_name">Количество:*</span>
                  <div class="input_field_block" style="width: 60px">
                    <input type="text" name="quantity_incoming" autocomplete="off" maxlength="11" value="<?= $data->get_quantity_incoming() ?>"/>
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
          <li>* При добавлении пороха указать вес в граммах.<li>
        </ul>
      </div>

      
      <form type="json" id="add_item">
        <input type="hidden" name="object" value="ammunition"/>
        <input type="hidden" name="method" value="add_item"/>
      </form>
      
      <form type="json" id="reset_form">
        <input type="hidden" name="object" value="ammunition"/>
        <input type="hidden" name="method" value="reset"/>
      </form>
    </div>
    

    <div class="added_items">
      <?php if (!empty($acc)) :
        $i = 1;
        foreach ($acc->get_weapon(2) as $n => $wpn) : ?>
          <div n="<?= $n ?>" <?= (is_null($wpn->get_id())) ? 'class="added_relation new" title="Несохраненная запись"' : 'class="added_relation"' ?>>
            <span class="order_number"><?= $i++ ?></span>. <b><?= $wpn->get_sort_string() ?><?= (!is_null($wpn->get_group_string())) ? ' ('.$wpn->get_group_string().')' : null ?></b>: 
              <?= (!is_null($wpn->get_caliber())) ? 'калибр &ndash; <i>'.$wpn->get_caliber().' мм</i>, ' : null ?> 
              количество &ndash; <i><?= $wpn->get_quantity_incoming().(($wpn->get_sort() == 24) ? ' гр' : ' шт') ?>.</i> 
              Хранилище: <?= $wpn->get_storage() ?>.
            <span class="delete_relation" id="<?= $n ?>" group="ammunition">×</span>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>
  
<?php 
  if (count($acc->get_weapon(2)) > 0)
    require('form_decision.php'); 
?>
</div>
<?php
$content = ob_get_contents();
ob_end_clean();
?>