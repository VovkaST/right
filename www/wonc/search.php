<?php
$need_auth = 1;
require ($_SERVER['DOCUMENT_ROOT'].'/sessions.php');
$breadcrumbs = array(
  'Главная' => '/index.php',
  'Текстовый массив' => 'index.php',
  'Поиск' => ''
);
require_once($_SERVER['DOCUMENT_ROOT'].'/header.php');
?>
<div class="fieldset search_form">
  <div class="legenda">Поиск</div>
  <form method="GET">
    <div class="input_row">
      <div class="input_field_block search_input">
        <input type="text" name="query" autocomplete="off"<?php if (!empty($_GET['query'])) echo ' value="'.htmlspecialchars($_GET['query'], ENT_QUOTES).'"'; ?>>
      </div>
    </div>
    <div class="legenda">Разделы</div>
    <div class="bottom_row">
      <div class="switcher_block">
        <div>
          <label><input type="checkbox" name="types[ornt]" <?php if (!empty($_GET['types']['ornt'])) echo 'checked' ?>/>Ориентировки</label>
          <label><input type="checkbox" name="types[addon]" <?php if (!empty($_GET['types']['addon'])) echo 'checked' ?>/>Дополнения к орентировкам</label>
          <label><input type="checkbox" name="types[recall]" <?php if (!empty($_GET['types']['recall'])) echo 'checked' ?>/>Отбой по ориентировкам</label>
		  <label><input type="checkbox" name="types[ref]" <?php if (!empty($_GET['types']['ref'])) echo 'checked' ?>/>Справки по преступлениям/БВП</label>
        </div>
        <div>
          <?php if (!empty($_SESSION['user']['admin']) or !empty($_SESSION['user']['references'])) : ?>
            <!-- <label><input type="checkbox" name="types[ref]" <?php if (!empty($_GET['types']['ref'])) echo 'checked' ?>/>Справки по преступлениям</label> -->
          <?php endif; ?>
          <label><input type="checkbox" disabled/>Электронный КУСП (<a href="search_ek.php">Расширенный поиск</a>)</label>
        </div>
      </div>
      <div class="add_button_box">
        <div class="button_block"><span class="button_name">Искать</span></div>
      </div>
    </div>
  </form>
  <hr color="#C6C6C6" size="0px"/>
  <div>
    <ul class="prim">
      <li><sup>1</sup> Все поисковые параметры по умолчанию объединяются через условие "ИЛИ".</li>
      <li><sup>2</sup> Для объединения нескольких условий через уловие "И" необходимо перед каждым параметром поставить знак "+" (например, запрос "иванов сергей" вернет результат, содержащий ИЛИ "иванов", ИЛИ "сергей", а запрос "+иванов +сергей" вернет только те записи, которые удовлетворяют оба условия).</li>
      <li><sup>3</sup> Предшествующий параметру знак "-" означает, что он не должен присутствовать в искомой строке.</li>
      <li><sup>4</sup> Знак подстановки одного символа &ndash; знак "?".</li>
      <li><sup>5</sup> Знак подстановки любого количества символов &ndash; знак "*" (учитывается на любой позиции, кроме первой).</li>
      <li><sup>6</sup> Фраза, заключенная в двойные кавычки, соответствует только строкам, содержащим эту фразу, написанную буквально.</li>
    </ul>
  </div>
</div>

<span class="result_count"></span>

<div class="search_result_block">
  <?php if (!empty($_GET['query'])) : ?>
    <h4>Идет поиск, подождите...</h4>
    <div class="ajax_response_wait"><img src="/images/ajax-loader.gif"/></div>
    <script>$(window).load( function(){ egrul_ip_data( '<?= 'method=search&'.$_SERVER["QUERY_STRING"] ?>' ); } );</script>
  <?php endif; ?>
</div>
<?php require_once($_SERVER['DOCUMENT_ROOT'].'/footer.php'); ?>