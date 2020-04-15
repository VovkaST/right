<?php
$need_auth = 0;
require ($_SERVER['DOCUMENT_ROOT'].'/sessions.php');

$data = $id = $images = null;
$f = 1;
if (!empty($_GET['id'])) {
  $id = to_integer($_GET['id']);
}
if (empty($id))
  die(header('location: orientations.php'));

require_once('require.php');
$data = new Orientation($id);

if (is_null($data->get_id()) or $data->is_deleted()) {
  header('Location: /error/404.php');
}

$data->full_data();
$files = $data->get_files_list();

$dir = $data->get_path(true);
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


$breadcrumbs = array(
  'Главная' => '/index.php',
  'Текстовый массив' => 'index.php',
  'Ориентировки' => 'orientations.php',
  'Ориентировки по ОВД' => 'ornts_list.php?ovd='.$data->get_ovd(),
  '' => ''
);
$page_title = 'Ориентировка №'.$data->get_number().' (Просмотр)';

require_once($_SERVER['DOCUMENT_ROOT'].'/header.php');
?>
<style>
</style>
<center><span style="font-size: 1.2em;"><strong>Ориентировка №<?= $data->get_number() ?> от <?= $data->get_date() ?></strong></span></center>
<hr color="#C6C6C6" size="0px"/>

<div class="work_on_the_crime orientation_view">
  
  <div class="actions_block">
    <ul class="actions_list">
      <li class="item">Действия:</li>
      <li class="item"><div class="block current">Просмотр</div></li>
      <li class="item"><div class="block"><a href="ornt_addon.php?id=<?= $id ?>">Дополнение</a></div></li>
      <?php if (is_null($data->get_recall())) : ?>
        <li class="item"><div class="block"><a href="ornt_recall.php?id=<?= $id ?>">Отбой</a></div></li>
      <?php endif; ?>
      <li class="item"><div class="block"><a href="orientation.php?id=<?= $id ?>">Редактировать</a></div></li>
      <li class="item"><div class="block"><a href="mailing.php?id=<?= $id ?>">Рассылка</a></div></li>
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
      <?php if (is_null($data->get_marking_string())) : ?>
        <td align="right">Ст.УК РФ:</td>
        <td><?= $data->get_uk_string() ?></td>
      <?php else : ?>
        <td align="right">Происшествие:</td>
        <td><?= $data->get_marking_string() ?></td>
      <?php endif; ?>
      </tr>
      <tr>
        <td align="right">КУСП:</td>
        <td>
          <?php foreach ($data->get_kusp_list() as $n => $kusp) : ?>
            <?php if (!is_null($kusp->get_ek())) : ?>
              <a href="ek.php?id=<?= $kusp->get_ek() ?>"><?= $kusp->get_ovd_string() ?>, № <?= $kusp->get_kusp() ?> от <?= $kusp->get_date() ?><br /></a>
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
      <tr>
        <td align="right">Отбой:</td>
        <td><?= $data->get_recall() ?></td>
      </tr>
    </table>
  </div>
  
  <div class="fieldset files table_block">
    <div class="legenda">Файлы:</div>
    <ul>
    <?php foreach ($files as $n => $file) : 
      $history = null;
      ?>
      <li>
        <span class="actions_list">
        <?php if ($file->is_indexed()) {
          echo $f++.'. <a href="#" method="file_preview" file="'.$file->get_link().'" title="Предпросмотр">'.$file->get_type_string().' </a>';
          if (is_file($file->get_path())) {
            echo '<a href="download.php?file='.$file->get_link().'" target="_blank">(Скачать) </a>';
          } else {
            echo '<i>(Файл недоступен!)</i>';
          }
          if (in_array($file->get_type(), array(1, 3, 4))) {
            $history = $file->get_mail_history();
            if (count($history)) {
              echo '<a href="#" method="file_send_story" file="'.$file->get_link().'" title="История рассылки">История рассылки</a>';
            }
          }
        } else {
          echo $f++.'. '.$file->get_type_string().' <i>(В обработке...)</i>';
        } ?>
        </span>
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