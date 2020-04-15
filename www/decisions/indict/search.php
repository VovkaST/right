<?php
$need_auth = 1;
require ($_SERVER['DOCUMENT_ROOT'].'/sessions.php');

$page_title = 'Регистрация электронных копий постановлений об отказе в возбуждении уголовного дела';

$breadcrumbs = array(
  'Процессуальные документы, вынесенные по результатам расследования УД' => 'index.php',
  '' => ''
);

$organ = array(  
  1=> 'ОВД',
  2=> 'СК',
  5=> 'Суд'
);


require_once($_SERVER['DOCUMENT_ROOT'].'/header.php');
?>
<div class="fieldset search_form">
  <div class="legenda">Поиск</div>
  <form method="GET">
  
    <div class="input_row">
      <div class="field_box">
        <span class="field_name">Номер у/д:</span>
        <div class="input_field_block" style="width: 120px;">
          <input type="text" name="n" autocomplete="off" value="<?php if (!empty($_GET['n'])) echo $_GET['n'] ?>"/>
        </div>
      </div>

      <div class="field_box">
        <span class="field_name">Год:</span>
        <div class="input_field_block" style="width: 80px;">
          <input type="text" name="year" autocomplete="off" value="<?php if (!empty($_GET['year'])) echo $_GET['year'] ?>"/>
        </div>
      </div>
	  
      <div class="field_box">
        <span class="field_name">ОВД:</span>
        <?= my_select('ovd', 'spr_ovd', ((!empty($_GET['ovd'])) ? $_GET['ovd'] : null)) ?>
      </div>
      
      <div class="field_box">
        <span class="field_name">Орган:</span>
        <?= my_select('organ', $organ, ((!empty($_GET['organ'])) ? $_GET['organ'] : null), null, 100) ?>
      </div>
    </div>
    
    <div class="input_row">  
      <div class="field_box">
        <span class="field_name">Статья:</span>
        <div class="input_field_block" style="width: 80px;">
          <input type="text" name="statya" autocomplete="off" value="<?php if (!empty($_GET['statya'])) echo $_GET['statya'] ?>"/>
        </div>
      </div>
      
      <div class="field_box">
        <span class="field_name">Часть:</span>
        <div class="input_field_block" style="width: 40px;">
          <input type="text" name="chast" autocomplete="off" value="<?php if (!empty($_GET['chast'])) echo $_GET['chast'] ?>"/>
        </div>
      </div>
      
      <div class="field_box">
        <span class="field_name">Пункт:</span>
        <div class="input_field_block" style="width: 40px;">
          <input type="text" name="punkt" autocomplete="off" value="<?php if (!empty($_GET['punkt'])) echo $_GET['punkt'] ?>"/>
        </div>
      </div>
      
      <div class="field_box">
        <span class="field_name">NGASPS:</span>
        <div class="input_field_block" style="width: 170px;">
          <input type="text" name="ngasps" autocomplete="off" value="<?php if (!empty($_GET['ngasps'])) echo $_GET['ngasps'] ?>"/>
        </div>
      </div>
    </div>
    
    
    
        
    <div class="header_row"></div>
    
    <div class="input_row">
      <div class="field_box">
        <span class="field_name">Фамилия:</span>
        <div class="input_field_block">
          <input type="text" name="surname" autocomplete="off" value="<?php if (!empty($_GET['surname'])) echo $_GET['surname'] ?>"/>
        </div>
      </div>

      <div class="field_box">
        <span class="field_name">Имя:</span>
        <div class="input_field_block">
          <input type="text" name="name" autocomplete="off" value="<?php if (!empty($_GET['name'])) echo $_GET['name'] ?>"/>
        </div>
      </div>
      
      <div class="field_box">
        <span class="field_name">Отчество:</span>
        <div class="input_field_block">
          <input type="text" name="fname" autocomplete="off" value="<?php if (!empty($_GET['fname'])) echo $_GET['fname'] ?>"/>
        </div>
      </div>
      
      <div class="field_box">
        <span class="field_name">ДР:</span>
        <?= my_date_field('borth', ((!empty($_GET['borth'])) ? $_GET['borth'] : null)) ?>
      </div>
    </div>
    
    <div class="bottom_row">
      <div class="switcher_block">
        <div>
          <label><input type="checkbox" name="forms[f2]" <?php if (!empty($_GET['forms']['f2'])) echo 'checked' ?>/>Ф2</label>
          <label><input type="checkbox" name="forms[f5]" <?php if (!empty($_GET['forms']['f5'])) echo 'checked' ?>/>Ф5</label>
          <label><input type="checkbox" name="forms[f6]" <?php if (!empty($_GET['forms']['f6'])) echo 'checked' ?>/>Ф6</label>
          <label><input type="checkbox" name="forms[ref]" <?php if (!empty($_GET['forms']['ref'])) echo 'checked' ?>/>Постановления об отказе в возбуждении у/д</label>
        </div>
      </div>
      
      <div class="add_button_box">
        <div class="button_block"><span class="button_name">Искать</span></div>
      </div>
    </div>
    
    <div class="header_row"></div>
    <div>
      <ul class="prim">
        <li><sup>1</sup> Знак подстановки &ndash; знак "*".</li>
        <li><sup>2</sup> Все поисковые параметры по умолчанию объединяются через условие "И".</li>
        <li><sup>3</sup> Поиск осуществляется по одному из блоков: "У/д" или "Лицо". Приоритетным является блок "У/д".</li>
        <li><sup>4</sup> По форме №5 осуществялется дополнительный поиск по единственному параметру "Фамилия".</li>
        <li><sup>5</sup> Если не выбран ни один из поисковых разделов, выборка осуществляется по ним всем.</li>
      </ul>
    </div>
  </form>
</div>
<span class="result_count"></span>

<div class="search_result_block">
  <?php if (!empty($_GET)) : ?>
    <h4>Идет поиск, подождите...</h4>
    <div class="ajax_response_wait"><img src="/images/ajax-loader.gif"/></div>
    <script>$(window).load( function(){ egrul_ip_data( '<?= 'method=fs&'.$_SERVER["QUERY_STRING"] ?>' ); } );</script>
  <?php endif; ?>
</div>

<?php require_once($_SERVER['DOCUMENT_ROOT'].'/footer.php'); ?>