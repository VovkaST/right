<?php
$need_auth = 1;
require ($_SERVER['DOCUMENT_ROOT'].'/sessions.php');
require_once('require.php');

if (empty($_GET['id'])) {
  die(header('location: e-kusp.php'));
} else {
  $id = to_integer($_GET['id']);
}

$data = new Event($id);
if (is_null($data->get_id())) {
  define('ERROR', 'Что-то пошло не так...');
  require_once($_SERVER['DOCUMENT_ROOT'].'/error/error.php');
}

$history = $data->get_history();
$relatives = $data->get_relatives();

$breadcrumbs = array(
  'Главная' => '/index.php',
  'Текстовый массив' => 'index.php',
  'Электронный КУСП' => 'e-kusp.php',
  '' => ''
);
require_once($_SERVER['DOCUMENT_ROOT'].'/header.php');
?>
<div class="header_row">Сообщение о преступлении, происшествии</div>

<div class="work_on_the_crime ek">
  <div class="fieldset main table_block">
    <div class="legenda">Осн.сведения:</div>
    <table rules="none" border="0" cellpadding="3" class="result_table woborders" width="100%">
      <tr>
        <td align="right" width="120px">ОВД:</td>
        <td><?= $data->get_ovd_string() ?></td>
      </tr>
      <tr>
        <td align="right">Тип:</td>
        <td><?= $data->get_reason() ?></td>
      </tr>
      <tr>
        <td align="right">Окраска:</td>
        <td><?= $data->get_marking() ?></td>
      </tr>
      <tr>
        <td align="right">Рег.№:</td>
        <td><?= $data->get_reg_number() ?> <?php if (!is_null($data->get_ticket_number())) echo '(ТУ №'.$data->get_ticket_number().')' ?></td>
      </tr>
      <tr>
        <td align="right">Дата, время рег.:</td>
        <td><?= $data->get_reg_date() ?>, <?= $data->get_reg_time() ?></td>
      </tr>
      <tr>
        <td align="right">Сотрудник:</td>
        <td><?= $data->get_reg_emp() ?></td>
      </tr>
      <tr>
        <td align="right">Заявитель:</td>
        <td>
          <?= $data->get_declarer() ?>
          <?php if (!is_null($data->get_declarer_tel())) echo '<br /><i>Телефон: '.$data->get_declarer_tel().'</i>' ?>
          <?php if (!is_null($data->get_declarer_address())) echo '<br /><i>Адрес: '.$data->get_declarer_address().'</i>' ?>
        </td>
      </tr>
      <tr>
        <td align="right">Фабула:</td>
        <td><?= $data->get_story() ?></td>
      </tr>
    </table>
  </div>
  
  <div class="table_block">
    <div class="fieldset decisions">
      <div class="legenda">История решений:</div>
      <?php if (empty($history)) : ?>
        Нет принятых решений
      <?php else : ?>
        <?php foreach ($history as $n => $dec) : ?>
          <dl>
            <dt class="light-gray"><?= ++$n ?>.</dt>
            <?php
              $str = null;
              $str[] = $dec->get_dec_date();
              $str[] = $dec->get_decision();
              if (!is_null($dec->get_dec_number()) and is_numeric($dec->get_dec_number())) {
                $same = null;
                switch ($dec->get_decision_code()) {
                  case 1:  // возбуждено уголовное дело
                    $query = '
                      SELECT
                        f1.`id`
                      FROM
                        `ic_f1_f11` as f1
                      WHERE
                        f1.`d3n_f10` = '.substr($dec->get_dec_number(), -5).' AND
                        YEAR(f1.`d11_f10`) = YEAR("'.date('Y-m-d', strtotime($dec->get_dec_date())).'")
                      LIMIT 1
                    ';
                    $result = mysql_query($query) or die('<b>Error</b>: '.mysql_error().'.Query string: <pre>'.$query.'</pre>');
                    $row = mysql_fetch_assoc($result);
                    if (!empty($row['id'])) {
                      $str[] = '№&nbsp;<a href="/decisions/indict/case.php?id='.$row['id'].'">'.$dec->get_dec_number().'</a>';
                    } else {
                      $str[] = '№&nbsp;'.$dec->get_dec_number();
                    }
                    break;
                  
                  case 26: // приобщено к ранее зарег.кусп о том же происшествии
                    $query = '
                      SELECT
                        ek.`id`
                      FROM
                        `ek_kusp` as ek
                      WHERE
                        ek.`reg_number` = '.$dec->get_dec_number().' AND
                        ek.`ovd` = '.$data->get_ovd().' AND
                        ek.`reg_date` <= "'.date('Y-m-d', strtotime($data->get_reg_date())).'"
                      ORDER BY
                        ek.`reg_date` DESC
                      LIMIT 1
                    ';
                    $result = mysql_query($query) or die('<b>Error</b>: '.mysql_error().'.Query string: <pre>'.$query.'</pre>');
                    $row = mysql_fetch_assoc($result);
                    $same = $row['id'];
                    if ($same) {
                      $str[] = '№&nbsp;<a href="ek.php?id='.$same.'">'.$dec->get_dec_number().'</a>';
                    } else {
                      $str[] = '№&nbsp;'.$dec->get_dec_number();
                    }
                    break;
                  
                  default:
                    $str[] = '№&nbsp;'.$dec->get_dec_number();
                    break;
                }
                
              }
              if (!is_null($dec->get_term()) and $dec->get_term() != '00.00.0000')
                $str[] = 'срок: '.$dec->get_term();
              if (!is_null($dec->get_org_target()))
                $str[] = 'в '.$dec->get_org_target();
              if (!is_null($dec->get_emp_person())) 
                $str[] = 'принял: '.$dec->get_emp_person().((!is_null($dec->get_emp_position())) ? ', '.$dec->get_emp_position() : '');
              if (!is_null($dec->get_qualification())) 
                $str[] = 'квалификация: '.$dec->get_qualification().' УК РФ';
            ?>
            <dd <?php if ($dec->is_error()) echo 'class="light-gray" title="Ошибочная запись"' ?>>
              <?=implode(', ', $str) ?>
            </dd>
          </dl>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
    <?php if (!empty($relatives)) : ?>
      <?php $n = 1; ?>
      <div class="fieldset">
        <div class="legenda">Дополнительно:</div>
          <ul>
          <?php foreach ($relatives as $i => $rel) : ?>
            <li><?= $n++ ?>. <?= $rel['link'] ?></li>
          <?php endforeach; ?>
          </ul>
      </div>
    <?php endif; ?>
  </div>
</div>
<?php require_once($_SERVER['DOCUMENT_ROOT'].'/footer.php'); ?>