<!DOCTYPE html>
<html>
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
  <title>...</title>
  <link rel="shortcut icon" href="/images/favicon.ico">
  <link rel="icon" href="/images/favicon.ico" type="/images/vnd.microsoft.icon">
  <link rel="stylesheet" href="/css/redmond/jquery-ui-1.10.4.custom.css">
  <script type="text/javascript" src="/js/jquery-1.10.2.js"></script>
  <script type="text/javascript" src="/js/jquery-ui-1.10.4.custom.js"></script>
  <script type="text/javascript">
    $(document).on('keyup', 'input#user_input, input#password_input', function(){
      ($(this).val() == '') ? $(this).css('color', '#A7A7A7') : $(this).css('color', '#515558');
    });
    $(document).on('focus', 'input#user_input, input#password_input', function(){
      $(this).closest('div.row').animate({borderBottomColor: '#FFE52D'}, 200);
    });
    $(document).on('blur', 'input#user_input, input#password_input', function(){
      $(this).closest('div.row').animate({borderBottomColor: '#E7E8E8'}, 200);
    });
    $(document).on('submit', '#auth_form', function(){
      $.ajax({
        url: '/login.php',
        dataType: 'json',
        type: 'POST',
        data: $(this).serialize(),
        beforeSend: function(){
          var h = $('.auth_block').outerHeight();
          $('.auth_form_block .block_header').after(
            $('<div class="waiting_background"><img src="/images/ajax-loader.gif"/></div>')
              .css('height', h)
              .fadeIn(200)
          );
        },
        success: function(resp) {
          if ('html' in resp) {
            $.each(resp.html, function(key, value){
              $(key).html(value);
            })
          }
          if ('error' in resp) {
            info_box('handling_errors', resp.error);
          }
          if ('msg' in resp) {
            info_box('handling_done', resp.msg);
          }
        },
        complete: function(){
          $('.auth_form_block .waiting_background').fadeOut(200, function(){
            $(this).remove();
          });
        }
      });
      return false;
    });
    $(function(){
      $('.head_menu_item > a').click(function(){
        var item = $(this).parent('.head_menu_item');
        if(item.children('.sub_menu').length) {
          var sub = item.children('.sub_menu');
          if (sub.css('display') != 'none') {
            sub.slideUp(300);
          } else {
            $('.sub_menu').slideUp(300, function(){ sub.slideDown(300); });
          }
          event.preventDefault ? event.preventDefault() : (event.returnValue = false);
        }
      });
      $('html').click(function(){
        if (event.target) {
          var trg = event.target;
        } else {
          var trg = window.event.srcElement;
        }
        if ($(trg).closest('.head_menu_item').length == 0) $('.sub_menu').slideUp(300);
      });
    });
  </script>
</head>
<style>
  body, h2 {
    margin: 0px;
    padding: 0px;
  }
  body, a, input {
    font: 12px Verdana, 'Geneva CY', 'DejaVu Sans', Arial, Helvetica, sans-serif;
  }
  body, input {
    color: #515558;
  }
  a {
    text-decoration: none;
    color: #2ba6cb;
    }
    a:hover {
      text-decoration: underline;
      color: #515558;;
      }
  .head_menu {
    display: table-row;
    }
    .head_menu_item {
      display: table-cell;
      text-align: center;
      vertical-align: middle;
      }
      .head_menu_item > a {
        display: block;
        width: 200px;
        padding: 15px 0px;
        background-color: #EFEFEF;
        border: 1px solid white;
        }
      .head_menu_item > a:hover {
        background-color: #e1e1e1;
        text-decoration: none;
      }
  ul.sub_menu {
    position: absolute;
    text-align: left;
    list-style: disc;
    background-color: #e1e1e1;
    border: 1px solid white;
    padding-left: 25px;
    display: none;
    z-index: 1;
    }
    .sub_menu li {
      background-color: #EFEFEF;
      padding: 5px 20px 5px 10px;
      }
    .sub_menu li:hover {
      background-color: #e1e1e1;
      }
  #left_block {
    float: left;
    width: 200px;
    margin: 10px 0px 10px 5px;
  }
  #central_block {
    margin: 10px 5px 10px 210px;
    min-width: 900px;
  }
  #footer {
    clear: both;
  }
  .auth_form_block {
    background-color: rgb(243, 243, 243);
    -webkit-border-radius: 5px;
    -moz-border-radius: 5px;
    border-radius: 5px;
    position: relative;
  }
  .block_header {
    padding: 10px;
    text-align: center;
    font-weight: bold;
    -webkit-border-radius: 5px 5px 0px 0px;
    -moz-border-radius: 5px 5px 0px 0px;
    border-radius: 5px 5px 0px 0px;
    background-color: rgb(231, 232, 232);
  }
  .auth_block {
    padding: 0px 10px 10px;
    min-height: 140px;
    -moz-box-sizing: border-box;
    -webkit-box-sizing: border-box;
    box-sizing: border-box;
  }
  .auth_block .row {
    padding: 5px 5px;
    border-bottom: 2px solid #E7E8E8;
    }
  .row.submit_row, .row.others {
    border-bottom: none;
  }
  .row.others {
    text-align: center;
  }
  .row.submit_row input {
    width: 100%;
    -moz-box-sizing: border-box;
    -webkit-box-sizing: border-box;
    box-sizing: border-box;
  }
  #user_input, #password_input {
    width: 100%;
    border: none;
    background-color: transparent;
    outline: none;
    color: #A7A7A7;
    }
  .auth_user{
    min-height: 67px;
    margin-top: 5px;
    }
  .authorized {
    text-align: center;
    padding: 5px 0px 0px;
    height: 80px;
    -moz-box-sizing: border-box;
    -webkit-box-sizing: border-box;
    box-sizing: border-box;
  }
  .user, .ip {
    margin: 5px 0px;
  }
  .waiting_background {
    display: none;
    position: absolute;
    width: 100%;
    -moz-box-sizing: border-box;
    -webkit-box-sizing: border-box;
    box-sizing: border-box;
    -webkit-border-radius: 0px 0px 5px 5px;
    -moz-border-radius: 0px 0px 5px 5px;
    border-radius: 0px 0px 5px 5px;
    height: 149px;
    background-image: url('/images/background.png');
  }
  .waiting_background > img {
    margin: 41px auto;
    display: block;
  }
  span.error {
    color: #BF3333;
  }
</style>
<body>
<div id="head">
 <div id="gerb">
  <a href="<?= INDEX ?>"><img src="/images/gerb_kir.gif" height="100"></a>
 </div>
 <div id="OORI">
  ...<br>...
 </div>
 <div id="kir_map">
  <img src="/images/kirov.gif" height="100">
 </div>
</div>
<div class="menu">
 <ul class="head_menu">
  <li class="head_menu_item"><a href="<?= INDEX ?>">ГЛАВНАЯ</a></li>
  <li class="head_menu_item">
    <a href="#">УЧЕТЫ</a>
    <ul class="sub_menu">
      <li><a href="<?= REFUSAL_VIEW_UPLOAD ?>">Регистрация электронных копий постановлений об отказе в возбуждении уголовного дела</a><br/>&nbsp;&nbsp;&nbsp;(Скачать <a href="<?= ADDR ?>documents/download_document.php?id=33">инструкцию</a> / <a href="<?= ADDR ?>documents/download_document.php?id=34">титульный лист</a>)</li>
      <li><a href="<?= DEBT ?>">Сведения о количестве вынесенных постановлений об отказе / возбуждении УД</a></li>
      <li><a href="<?= MIGRATION ?>">Соблюдение миграционного законодательства</a></li>
      <li><a href="<?= UKR ?>">Проверка граждан, прибывших из Украины</a></li>
      <li><a href="<?= UII ?>">Лица, состоящие на учете в УИИ</a></li>
        <li><a href="<?= CRIMES ?>index.php">АИС "Мошенник"</a><br/>&nbsp;&nbsp;&nbsp;(Скачать <a href="<?= ADDR ?>documents/download_document.php?id=47">Методические рекомендации по формированию АИС</a>)</li>
    </ul>
  </li>
  <li class="head_menu_item"><a href="<?= DOCUMENTS ?>">ДОКУМЕНТЫ</a></li>
  <li class="head_menu_item" id="last-item"><a href="<?= CONTACTS ?>">КОНТАКТЫ</a></li>
 </ul>
</div>
<div id="breadcrumbs">
  <?= breadcrumbs($breadcrumbs) ?>
</div>
<div id="left_block">
  <div class="auth_form_block">
    <?php if (!isset($_SESSION['user'])) : ?>
    <div class="block_header">Авторизация</div>
    <div class="auth_block">
      <form id="auth_form">
        <div class="row"><input type="text" id="user_input" name="user" placeholder="Пользователь" autocomplete="off"/></div>
        <div class="row"><input type="password" id="password_input" name="password" placeholder="Пароль" autocomplete="off"/></div>
        <div class="row submit_row"><input type="submit" value="Вход"/></div>
      </form>
      <div class="row others resp"></div>
      <div class="row others">
        <a href="<?= IBD ?>">Авторизация через ИБД-Р</a>
        <a href="/registration.php">Регистрация</a>
      </div>
    </div>
    <?php else : ?>
    <div class="block_header">Добро пожаловать</div>
    <div class="auth_block">
      <div class="authorized">
        Вы вошли как
        <div class="user"><b><?= $_SESSION['user']['user'] ?></b><br/><?= (!empty($_SESSION['user']['ovd'])) ? $_SESSION['user']['ovd'] : '&nbsp;' ?></div>
        <div class="ip">ip: <?= $_SERVER['REMOTE_ADDR'] ?></div>
      </div>
      <div class="row others">
        <a href="/cabinet.php">Личный кабинет</a>
      </div>
      <div class="row others">
        <a href="/exit.php">Выход</a>
      </div>
    </div>
    <?php endif; ?>
  </div>
</div>
<div id="central_block">