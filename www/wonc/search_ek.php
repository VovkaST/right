<?php
$need_auth = 1;
require ($_SERVER['DOCUMENT_ROOT'].'/sessions.php');
$breadcrumbs = array(
  'Главная' => '/index.php',
  'Текстовый массив' => 'index.php',
  'Электронный КУСП' => 'e-kusp.php',
  'Поиск' => ''
);

if (empty($_GET['limit']) or !is_numeric($_GET['limit'])) {
  define('RECORDS', 30);
} else {
  define('RECORDS', (integer)$_GET['limit']);
}

$limit_array = array(15, 30, 50, 100);

if (!in_array(RECORDS, $limit_array)) {
  $limit_array = array_merge($limit_array, array(RECORDS));
  sort($limit_array);
  $limit_array = array_combine($limit_array, $limit_array);
}
$limit_array = array_combine($limit_array, $limit_array);

require_once($_SERVER['DOCUMENT_ROOT'].'/header.php');
?>
<div class="header_row">Расширенный поиск по электронному КУСП</div>
<div class="fieldset search_form ek">
  <form method="GET">
    <div class="legenda">КУСП</div>
    <div class="input_row">
      <div class="field_box">
        <span class="field_name">ОВД:</span>
        <?= my_select('ovd', 'spr_ovd', ((!empty($_GET['ovd'])) ? $_GET['ovd'] : null)) ?>
      </div>
      <div class="field_box">
        <span class="field_name">Рег.№:</span>
        <div class="input_field_block" style="width: 90px;">
          <input type="text" name="kusp" autocomplete="off"<?php if (!empty($_GET['kusp'])) echo ' value="'.$_GET['kusp'].'"' ?>/>
        </div>
      </div>
      <div class="field_box">
        <span class="field_name">Дата рег.:</span>
        <?= my_date_field('r_s', ((!empty($_GET['r_s'])) ? $_GET['r_s'] : null)) ?>
        <span class="field_name">&mdash;</span>
        <?= my_date_field('r_t', ((!empty($_GET['r_t'])) ? $_GET['r_t'] : null)) ?>
      </div>
    </div>
    
    <div class="input_row">
      <div class="field_box">
        <span class="field_name">Заявитель:</span>
        <div class="input_field_block">
          <input type="text" name="declarer" autocomplete="off"<?php if (!empty($_GET['declarer'])) echo ' value="'.htmlspecialchars($_GET['declarer'], ENT_QUOTES).'"'; ?>>
        </div>
      </div>
      <div class="field_box">
        <span class="field_name">Фабула:</span>
        <div class="input_field_block" style="width: 400px;">
          <input type="text" name="story" autocomplete="off"<?php if (!empty($_GET['story'])) echo ' value="'.htmlspecialchars($_GET['story'], ENT_QUOTES).'"'; ?>>
        </div>
      </div>
    </div>
    
    <div class="header_row"></div>
    
    <div class="legenda">Решение</div>
    <div class="input_row">
      <div class="field_box">
        <span class="field_name">Вид:</span>
        <?= my_select('decision', 'spr_kusp_decisions', ((!empty($_GET['decision'])) ? $_GET['decision'] : null)) ?>
      </div>
      <div class="field_box">
        <span class="field_name">№:</span>
        <div class="input_field_block" style="width: 90px;">
          <input type="text" name="dec_num" autocomplete="off"<?php if (!empty($_GET['dec_num'])) echo ' value="'.$_GET['dec_num'].'"' ?>/>
        </div>
      </div>
      <div class="field_box">
        <span class="field_name">Дата:</span>
        <?= my_date_field('dec_s', ((!empty($_GET['dec_s'])) ? $_GET['dec_s'] : null)) ?>
        <span class="field_name">&mdash;</span>
        <?= my_date_field('dec_t', ((!empty($_GET['dec_t'])) ? $_GET['dec_t'] : null)) ?>
      </div>
      <div class="field_box">
        <span class="field_name">Ст. УК:</span>
        <div class="input_field_block" style="width: 90px;">
          <input type="text" name="article" autocomplete="off"<?php if (!empty($_GET['article'])) echo ' value="'.$_GET['article'].'"' ?>/>
        </div>
      </div>
    </div>

    <div class="header_row"></div>
    
    <div class="bottom_row">
      <div class="field_box">
        <span class="field_name">Показывать записей:</span>
        <?= my_select('limit', $limit_array, RECORDS, null, 60) ?>
      </div>
      <div class="add_button_box">
        <div class="button_block"><span class="button_name">Искать</span></div>
      </div>
    </div>
  </form>
  
  <div class="header_row"></div>
  
  <div>
    <ul class="prim">
      <li class="green"><sup>1</sup> Процедура поиска будет осуществляться быстрее, если задать период регистрации КУСП. Рекомендуется указывать периоды внутри одного года.</li>
      <li><sup>2</sup> Все поисковые параметры по умолчанию объединяются через условие "И".</li>
      <li><sup>3</sup> Для полей "Заявитель", "Фабула", "№ решения", "Ст.УК" действуют следующие правила:</li>
      <li><sup>&nbsp;3.1</sup> Поисковая фраза соответствует только строкам, содержащим эту фразу, написанную буквально, без учета регистра.</li>
      <li><sup>&nbsp;3.2</sup> Две поисковые фразы необходимо разделять <i>знаком подстановки</i>. Порядок фраз имеет значение. Например, если в искомой строке должны присутствовать слова "Иван" и "пропал", то необходимо осуществить два поиска: "иван%пропал" и "пропал%иван".</li>
      <li><sup>&nbsp;3.3</sup> <i>Знак подстановки <b>любого количества символов</b></i> &ndash; знак "%".</li>
      <li><sup>&nbsp;3.4</sup> <i>Знак подстановки <b>символа</b></i> &ndash; знак "_".</li>
      <li><sup>4</sup> Поиск по реквизитам "Заявитель" и "№ решения" осуществялется с начала строки. Для поиска по всей строке в начале поисковой фразы необходимо использовать <i>знак подстановки</i>.</li>
    </ul>
  </div>
</div>

<span class="result_count"></span>

<div class="search_result_block">
  <?php if (!empty($_GET)) : ?>
    <h4>Идет поиск, подождите...</h4>
    <div class="ajax_response_wait"><img src="/images/ajax-loader.gif"/></div>
    <script>$(window).load( function(){ egrul_ip_data( '<?= 'method=search_ek&'.$_SERVER["QUERY_STRING"] ?>' ); } );</script>
  <?php endif; ?>
</div>
<?php require_once($_SERVER['DOCUMENT_ROOT'].'/footer.php'); ?>