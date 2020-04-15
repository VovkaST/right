<?php
$need_auth = 1;
require ($_SERVER['DOCUMENT_ROOT'].'/sessions.php');
require_once('require.php');

if (empty($_SESSION['user']['admin']) and empty($_SESSION['user']['references'])) {
  define('ERROR', 'Недостаточно прав.');
  require_once($_SERVER['DOCUMENT_ROOT'].'/error/error.php');
}

$data = $id = null;
$f = 1;
  
if (!empty($_GET['id']))
  $id = to_integer($_GET['id']);

if (empty($id)) {
  define('ERROR', 'Что-то пошло не так...');
  require_once($_SERVER['DOCUMENT_ROOT'].'/error/error.php');
}

$data = new Reference($id);
$data->full_data();
$files = $data->get_files_list();

/*$dir = $data->get_path(true);
if (is_dir($dir.'Images/')) {
  $imgDir = opendir($dir.'Images/');
  while (($file = readdir($imgDir)) !== false) {
    if (in_array($file, array('.', '..'))) {
      continue;
    }
    $info = pathinfo($file);
    if (in_array(strtolower($info['extension']), array('jpg', 'png', 'gif')))
      $images[] = $info['basename'];
  }
}
*/

$breadcrumbs = array(
  'Главная' => '/index.php',
  'Текстовый массив' => 'index.php',
  'Обзорные справки по преступлениям' => 'references.php',
  'Обзорные справки по преступлениям по ОВД' => 'ref_list.php?ovd='.$data->get_ovd(),
  '' => ''
);
$page_title = 'Обзорная справка';
if (!is_null($data->get_crime_case_number()))
  $page_title .= ' (У/д № '.$data->get_crime_case_number().')';
/*<pre><?= var_dump($data) ?></pre>*/

require_once($_SERVER['DOCUMENT_ROOT'].'/header.php');
?>
<style>
</style>
<center><span style="font-size: 1.2em;"><strong><?= $page_title ?></strong></span></center>
<hr color="#C6C6C6" size="0px"/>

<div class="work_on_the_crime orientation_view">
  
  <div class="actions_block">
    <ul class="actions_list">
      <li class="item">Действия:</li>
      <li class="item"><div class="block current">Просмотр</div></li>
    </ul>
  </div>
  <div class="fieldset main table_block">
    <div class="legenda">Осн.сведения:</div>
    <table rules="none" border="0" cellpadding="3" class="result_table woborders" width="100%">
      <tr>
        <td align="right" width="120px">ОВД:</td>
        <td><?= $data->get_ovd_string() ?></td>
      </tr>
      <tr>
        <td align="right">КУСП:</td>
        <td>
          <?php foreach ($data->get_kusp_list() as $n => $kusp) : ?>
            <?php if (!is_null($kusp->get_ek())) : ?>
              <a href="ek.php?id=<?= $kusp->get_ek() ?>" target="_blank"><?= $kusp->get_ovd_string() ?>, № <?= $kusp->get_kusp() ?> от <?= $kusp->get_date() ?><br /></a>
            <?php else : ?>
              <?= $kusp->get_ovd_string() ?>, № <?= $kusp->get_kusp() ?> от <?= $kusp->get_date() ?><br />
            <?php endif; ?>
          <?php endforeach; ?>
        </td>
      </tr>
      <tr>
        <td align="right">Уголовное дело:</td>
        <td>
          <?php if ($data->get_crime_case() !== false) : ?>
            № <?= $data->get_crime_case_number() ?> от <?= $data->get_crime_case_date() ?>
          <?php endif; ?>
        </td>
      </tr>
    </table>
  </div>
  
  <div class="fieldset files table_block">
    <div class="legenda">Файлы:</div>
    <ul>
    <?php foreach ($files as $n => $file) : ?>
      <li>
        <?php if ($file->is_indexed()) {
          echo $f++.'. <span class="actions_list"><a href="#" method="file_preview" file="'.$file->get_link().'" title="Предпросмотр">'.$file->get_type_string().' </a></span>';
          if (is_file($file->get_path())) {
            echo '<a href="download.php?file='.$file->get_link().'" target="_blank">(Скачать) </a>';
          } else {
            echo '<i>(Файл недоступен!)</i>';
          }
        } else {
          echo $f++.'. '.$file->get_type_string().' <i>(В обработке...)</i>';
        } ?>
      </li>
    <?php endforeach; ?>
    </ul>
  </div>
  
  <?php if (!empty($images)) : ?>
    <div class="fieldset images">
      <div class="legenda">Изображения:</div>
        <div id="lightGallery">
          <?php foreach ($images as $image) : ?>
            <a href="<?= $data->get_path().'images/'.$image ?>">
              <img src="<?= $data->get_path().'images/'.$image ?>"/>
              <div class="gallery-poster"><img src="/images/zoom.png"/></div>
            </a>
          <?php endforeach; ?>
        </div>
    </div>
  <?php endif; ?>
  <div id="file_preview"></div>
</div>
<?php require_once($_SERVER['DOCUMENT_ROOT'].'/footer.php'); ?>