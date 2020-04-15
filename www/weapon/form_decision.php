<?php
  if (empty($_POST['method']) or empty($_POST['id']) or strpos($_SERVER['HTTP_REFERER'], 'http://.../weapon/receipt.php') === false)
    header('Location: /error/404.php');
  switch ($_POST['id']) {
    case 'firearms':
      $type = 1;
      break;
    case 'ammunition':
      $type = 2;
      break;
    case 'explosives':
      $type = 3;
      break;
    case 'steelarms':
      $type = 4;
      break;
  }
  if (isset($_SESSION['WeaponAccount']['tmp_decision'][$type])) {
    $dec = unserialize($_SESSION['WeaponAccount']['tmp_decision'][$type]);
  } else {
    $dec = new WeaponDecision();
  }
?>
<div class="wpn_history" style="display: none;">
  <div class="fieldset">
    <div class="table">
      <div class="table-cell">
        <div class="header_row">Добавить решение</div>
        
        <form class="main_form" type="json">
          <input type="hidden" name="object" value="<?= $_POST['id'] ?>"/>
          <input type="hidden" name="method" value="tmp_decision"/>
          
          <div class="weapons_list">
            <?php $i = 1;
                  foreach ($acc->get_weapon($type) as $n => $wpn) : 
                  $str = array();
                  if ($wpn->is_model()) {
                    $str[] = '<b>'.$wpn->get_model_string().'</b>';
                  } else {
                    $str[] = '<b>'.$wpn->get_sort_string().'</b>'.((!is_null($wpn->get_group_string())) ? ' ('.$wpn->get_group_string().')' : null);
                  }
                  if (!is_null($wpn->get_manufacture_year()))
                    $str[] = $wpn->get_manufacture_year().' г.';
                  if (!is_null($wpn->get_caliber()))
                    $str[] = ' <i>'.$wpn->get_caliber().' мм</i>';
                  if (!is_null($wpn->get_series()))
                    $str[] = ' сер.'.$wpn->get_series();
                  if (!is_null($wpn->get_number()))
                    $str[] = ' № '.$wpn->get_number();
                  if ($wpn->get_type() == 2)
                    $str[] = ' приход: <i>'.$wpn->get_quantity_incoming().' '.(($wpn->get_sort() == 24) ? 'гр' : 'шт').'.</i>';
            ?>
              <label>
                <div id="<?= $wpn->get_id() ?>" class="added_relation <?php if ($wpn->get_type() == 2) echo 'with_quantity'?>">
                  <input type="checkbox" name="weapons[]" value="<?= $wpn->get_id() ?>"/>
                  <span class="order_number"><?= $i++ ?></span>.
                    <?= implode(', ', $str) ?><br />
                    <i><span class="count"><?= (($wpn->get_quantity_total() != $wpn->get_quantity_incoming()) ? 'в КХО: '.$wpn->get_quantity_total().' шт.' : null) ?> </span></i>
                  <?php if ($wpn->get_type() == 2) : ?>
                    <div class="quantity_block" title="Расход">
                      <input class="no_vis_effect" type="text" autocomplete="off" name="quantity[<?= $wpn->get_id() ?>]" value="<?= $wpn->get_quantity_total() ?>"/>
                    </div>
                  <?php endif; ?>
                </div>
              </label>
            <?php endforeach; ?>
          </div>
          <div class="input_row">
            <div class="field_box">
              <span class="field_name">Дата:</span>
              <?= my_date_field('date', $dec->get_date()) ?>
            </div>
            <div class="field_box" title="Рег.№ сопр.письма, накладной, заявления на выдачу">
              <span class="field_name">№</span>
              <div class="input_field_block" style="width: 115px;">
                <input type="text" name="number" autocomplete="off" maxlength="10" value="<?= $dec->get_number() ?>"/>
              </div>
            </div>
          </div>
          <div class="input_row">
            <div class="field_box">
              <span class="field_name">Решение: </span>
              <?= my_select('decision', 'spr_decision_in_arms', $dec->get_decision()) ?>
            </div>
          </div>
          <div class="fieldset">
            <div class="legenda">Место хранения документов</div>
            <div class="input_row">
              <div class="field_box">
                <span class="field_name">Дело №:</span>
                <div class="input_field_block" style="width: 80px">
                  <input type="text" name="case" autocomplete="off" maxlength="15" value="<?= $dec->get_case() ?>"/>
                </div>
              </div>
              <div class="field_box">
                <span class="field_name">Страница:</span>
                <div class="input_field_block" style="width: 50px;">
                  <input type="text" name="page" autocomplete="off" maxlength="3" value="<?= $dec->get_page() ?>"/>
                </div>
              </div>
            </div>
          </div>
          
        </form>
        <?php if ($_SESSION['user']['admin'] == 1 or $_SESSION['user']['ovd_id'] != 59) : ?>
          <form type="json">
            <input type="hidden" name="object" value="<?= $_POST['id'] ?>"/>
            <input type="hidden" name="method" value="save_decision"/>
            
            <div class="add_button_box">
              <div class="button_block"><span class="button_name">Добавить</span></div>
            </div>
          </form>
        <?php endif; ?>
      
      </div>
      <div class="table-cell">
        <div class="header_row">Принятые решения</div>
        <div class="added_items">
          <?php foreach ($acc->get_weapon_history($type) as $i => $item) : ?>
            <div class="added_relation">
              <span class="order_number"><?= ++$i ?></span>. <?= $item['name'] ?>, № <?= $item['number'] ?> от <?= $item['date'] ?>:
              <?php foreach ($item['weapons'] as $n => $p) : 
                $_w = new Weapon($p['weapon']);
                if ($wpn->is_model()) {
                  $str = '&nbsp;&nbsp;'.$_w->get_model_string();
                } else {
                  $str = '&nbsp;&nbsp;'.$_w->get_sort_string().((!is_null($_w->get_group_string())) ? ' ('.$_w->get_group_string().')' : null);
                }
              ?>
                <ul>
                  <li>&nbsp;&nbsp; &ndash;<?= $str ?>, <?= $p['quantity'] ?> шт.</li>
                </ul>
              <?php endforeach; ?>
            <div class="right-align"><i><?= $item['case'] ?></i></div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>
</div>