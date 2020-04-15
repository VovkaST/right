//проверка на число
function isNumeric(n){
  return !isNaN(parseFloat(n)) && isFinite(n);
}

function myTrim(str, charlist) {
  charlist = !charlist ? ' \\s\xA0' : charlist.replace(/([\[\]\(\)\.\?\/\*\{\}\+\$\^\:])/g, '\$1');
  var re = new RegExp('^[' + charlist + ']+|[' + charlist + ']+$', 'g');
  return str.replace(re, '');
};

//плагин добавления ссылки очистки поля
(function($){
  $.fn.clearLink = function(){
    $(this).each(function(i){
      $(this)
       .after(
         $('<span class="clear_link" id="clear_link_'+i+'">&times;</span>')
          .css("display", "none")
          .click(function(){
            $(this).prev().val("");
            $("#clear_link_"+i).hide();
          })
       )
      $(this).bind("change keyup", function(){
        if ($(this).val().length > 0) {
          $(this).next("#clear_link_"+i).css("display", "inline")
        } else {
          $("#clear_link_"+i).hide();
        }
      })
      if ($(this).val().length > 0) {
        $(this).next("#clear_link_"+i).css("display", "inline");
      }
    })
  };
})($);

(function($){
  $.fn.onlyNumbers = function(message){
    function numButton(button) {
      if ((button == 32) || (button >= 65 && button <= 90) || (button >= 106 && button <= 111) || (button >= 186 && button <= 222)) { //[48,49,50,51,52,53,54,55,56,57,96,97,98,99,100,101,102,103,104,105]
        return false;
      } else {
        return true;
      }
    }
    var elem = this;
    elem.keydown(function(){
      if (!numButton(event.keyCode)) {
        //var hintLeft = Math.round(elem.offset().left+elem.outerWidth()+11); //справа
        var hintTop = Math.round(elem.offset().top); //сверху
        //var hintTop = Math.round(elem.offset().top - elem.innerHeight()/2); //справа
        var hintLeft = Math.round(elem.offset().left - (elem.outerWidth()-elem.innerWidth())); //сверху
        if (!$("div").is(".numberHint")) {
          elem.after(
            $('<div class="numberHint">'+message+'</div>')
              .css({"left": hintLeft+"px", "top": (elem.offset().top - $(this).outerHeight()-30)+"px"})
              .fadeIn(200)
          );
        }
        return false;
      } else {
        if ($("div").is(".numberHint")) {
          $(".numberHint")
            .fadeOut(200, function(){ $(this).remove(); })
        }
      }
    });
    elem.focusout(function(){
      if ($("div").is(".numberHint")) {
        $(".numberHint")
          .fadeOut(200, function(){ $(this).remove(); })
      }
      var val = elem.val();
      var newVal = '';
      var i = 0;
      var flag = false;
      while (i < val.length) {
        if (isNumeric(val.charAt(i))) {
          newVal += val.charAt(i);
          flag = true;
        } else {
          if (flag) {
            break;
          }
        }
        i++;
      }
      elem.each(function(){
        elem.val(newVal);
      });
    });
  }
})(jQuery);


(function($){
  $.fn.onlyLetters = function(message, parts, wordList){
    function numLetters(button) {
      if ((button >= 48 && button <= 57) || (button >= 96 && button <= 105) || (button >= 106 && button <= 109) || (button >= 110 && button <= 111) || (button >= 186 &&  button <= 192) || (button >= 219 &&  button <= 221)) { //[цифры, знаки препинания]
        if ((button == 186) || (button == 188) || (button == 190) || (button == 219) || (button == 221)) {
          if (button == 186) return 'ж';
          if (button == 188) return 'б';
          if (button == 190) return 'ю';
          if (button == 219) return 'х';
          if (button == 221) return 'ъ';
        }
      } else {
        return true;
      }
    }
    function hintHide() {
      if ($("div").is(".numberHint")) {
        $(".numberHint")
          .fadeOut(200, function(){ $(this).remove(); })
      }
    }
    var elem = this;
    elem.keydown(function(){
      if (!numLetters(event.keyCode)) {
        //var hintLeft = Math.round(elem.offset().left+elem.outerWidth()+11); //справа
        var hintTop = Math.round(elem.offset().top); //сверху
        //var hintTop = Math.round(elem.offset().top - elem.innerHeight()/2); //справа
        var hintLeft = Math.round(elem.offset().left - (elem.outerWidth()-elem.innerWidth())); //сверху
        if (!$("div").is(".numberHint")) {
          elem.after(
            $('<div class="numberHint">'+message+'</div>')
              .css({"left": hintLeft+"px", "top": (elem.offset().top - $(this).outerHeight()-30)+"px"})
              .fadeIn(200)
          );
        }
        return false;
      } else {
        hintHide();
      }
    });
    elem.focusout(function(){
      hintHide();
      var val = myTrim(this.value.toLowerCase());
      val = val.replace(/[\da-zA-Z\_\-\$\#\*\/\+\.\;\:\"\'\,\{\}\(\)\\]/g, '');
      val = val.replace(/\s{2,}/g, ' ');
      if (!wordList) {
        var wrongWords = ["нет", "отсут", "устан", "неустан", "не устан", "н/у", "ну", "нз", "хз", "неизве", "работ"];
      } else {
        var wrongWords = wordList;
      }      
      for (var i = 0; i < wrongWords.length; i++) {
        if (val.indexOf(wrongWords[i]) == 0) {
          val = '';
        }
      }
      val = myTrim(val);
      var splitStr = val.split(" ");
      if (splitStr.length > parts) {
        val = '';
        for (var i = 0; i < parts; i++) {
          val += splitStr[i];
          if (i != parts-1) val += ' ';
        }
      }
      this.value = val;
    });
  }
})($);

(function($){
  $.fn.my_select = function(){
  
    function select_clear(elem) {
      elem.find('input.my_select_search').val('');
      elem.find('li').each(function(){ $(this).show(); });
    }
    
    return this.each(function(){
      var $my_select = $(this);
      var select_button = $my_select.find('.my_select_button');
      var my_select_data = $my_select.find('input[type="hidden"]');
      var list = $my_select.find('ul.my_select_list');
      var my_select_search = list.find('input.my_select_search');
      
      $my_select.attr('id', Math.round(Math.random()*(654321 - 123456) / Math.random()));
      
      select_button.click(function(){
        if (list.css('display') == 'none') {
          list.slideDown(200);
          my_select_search.focus();
        } else {
          list.slideUp(200, function(){ select_clear($(this)); });
        }
      });
      
      select_button.keydown(function(key){
        switch(key.keyCode) {
          case 38: case 40:
            list.slideDown(200);
            my_select_search.focus();
            break;
          case 27:
            if (list.css('display') != 'none') list.slideUp(200, function(){ select_clear($(this)); });
            break;
          default:
            break;
        }
      });
      
      my_select_search.keyup(function(I){
        var elem = '';
        switch(I.keyCode) {
          case 13:
          case 27:
          case 37:
          case 38:
          case 39:
          case 40:
            break;
          default:
            var val = $(this).val().toLowerCase();
            if (val.length >= 1) {
              elem = '';
              list.find('li').each(function(){
                if (!$(this).hasClass('skip')) {
                  ($(this).find('a').length) ? elem = $(this).find('a') : elem = $(this);
                  if (elem.html().toLowerCase().indexOf(val) == -1) {
                    $(this).css('display', 'none');
                  } else {
                    $(this).css('display', 'list-item');
                  }
                }
              });
            } else {
              list.find('li').css('display', 'list-item');
            }
            break;
        }
        
      });
      
      list.find('li a').on('click', function(event){
        list.slideUp(200, function(){ select_clear($(this)); });
        my_select_data.val($(this).attr('id'));
        list.find('a.selected').each(function(){ $(this).removeClass('selected'); });
        select_button.children('span').text($(this).text());
        my_select_search.val('');
        list.find('li').each(function(){
          $(this).show();
        });
        $(this).addClass('selected');
        event.preventDefault ? event.preventDefault() : (event.returnValue = false);
      });
      
      $(document).on('click', function(event){
        (event.target) ? trg = event.target : trg = window.event.srcElement;
        if ($(trg).closest('div.my_select').length == 0) {
          $('ul.my_select_list').each(function(){
            $(this).slideUp(200, function(){ select_clear($(this)); })
          });
        } else {
          var id = $(trg).closest('div.my_select').attr('id');
          $('div.my_select').each(function(){
            if ($(this).attr('id') != id) {
              $(this).find('ul.my_select_list').slideUp(200, function(){ select_clear($(this)); });
            }
          });
        }
      });
      
      
    })
  };
})(jQuery);

function info_box(handling_class, handling_msg) {
  $('body').append(
    '<div class="info_box"> \
      <div class="handling_result ' + handling_class + '"> \
        <p>' + handling_msg + '</p> \
        <input type="button" value="Ok" class="info_box_close"/> \
      </div> \
    </div>');
  var body = $('body');
  var win = $(window);
  var box = $('.info_box');
  box.css({
        top: ((win.height() - box.outerHeight()) / 2 + win.scrollTop()) + "px", 
        left: ((win.width() - box.outerWidth()) / 2) + "px" 
      })
     .fadeIn(200);
  $('.info_box_close').focus();
}

function info_box_show(data){
  if ('html' in data) {
    $.each(data.html, function(key, value){
      $('.' + key).html(value);
    })
  }
  if ('error' in data) {
    info_box('handling_errors', data.error);
  }
  if ('msg' in data) {
    info_box('handling_done', data.msg);
  }
}

function popup_form(link, wid) {
  var form = link.closest("form");
  $.ajax({
    url: 'procedures.php',
    dataType: 'json',
    type: 'POST',
    data: link.attr('href') + '&' + form.serialize(),
    success: function(resp){
      if ('html' in resp) {
        var win = $(window);
        var popup_form = '';
        var opacity_back = $('<div class="opacity_back" style="height: '+$(document).height()+'px; display: none;"></div>');
        $.each(resp.html, function(key, value){
          popup_form = $(key);
          popup_form
            .html(value)
            .css({
              width: wid + "px",
              maxheight: (win.height() - 40) + "px",
              top: ((win.height() - popup_form.outerHeight()) / 2 + win.scrollTop()) + "px", 
              left: ((win.width() - popup_form.outerWidth()) / 2) + "px"
            });
        });
        $('html').append(
          opacity_back.click(function(){
            opacity_back.fadeOut(300, function(){ $(this).remove(); });
            popup_form.fadeOut(300);
          })
        );
        popup_form
          .keydown(function(I){
            if (I.keyCode == 27) {
              opacity_back.fadeOut(300, function(){ $(this).remove(); });
              popup_form.fadeOut(300);
            }
          })
          .fadeIn(300);
        popup_form.fadeIn(300);
        opacity_back.fadeIn(300);
        $('.datepicker').datepicker();
      }
      if ('error' in resp) {
        info_box('handling_errors', resp.error);
      }
      if ('msg' in resp) {
        info_box('handling_done', resp.msg);
      }
    }
  });
}

function navigation(next_block, link, code) {
  $('.'+link.attr('class')+'_list a').css('color', '');
  $.ajax({
    url: 'procedures.php',
    dataType: 'json',
    type: 'POST',
    data: link.attr('class')+'='+code+'&navigation=true',
    success: function(resp){
      if ('html' in resp) {
        $.each(resp.html, function(key, value){
          $(key).html(value);
        });
        link.css("color", "#BE4141");
        next_block.fadeIn(200).css('display', 'table-cell');
      }
      if ('error' in resp) {
        info_box('handling_errors', resp.error);
      }
      if ('msg' in resp) {
        info_box('handling_done', resp.msg);
      }
    }
  });
}

$(document).on('click', '.district_list a.district, .locality_list a.locality', function(event){
  var link = $(this);
  var code = link.attr('id');
  switch(link.attr('class')){
    case 'district': 
      var next_block = $('.locality_block');
      if ($('.passport_block').css('display') != 'none') {
        $('.passport_block').fadeOut(200);
      }
      break;
    case 'locality': var next_block = $('.passport_block'); break;
  }
  next_block.fadeOut(200, function(){
    navigation(next_block, link, code);
  });
  event.preventDefault ? event.preventDefault() : (event.returnValue = false);
});

$(document).on('click', '.locality_organisations_list a', function(event){
  var link = $(this);
  var form = link.closest("form");
  var org_list = $('.organisation_list_box');
  $.ajax({
    url: 'procedures.php',
    dataType: 'json',
    type: 'POST',
    data: 'popup=org_view&org_type=' + link.attr('id') + '&' + form.serialize(),
    beforeSend: function(){
      
    },
    success: function(resp){
      org_list.fadeOut(200, function(){
        $.each(resp.html, function(key, value){
          org_list
            .html(value)
            .fadeIn(200);
        });
      });
      if ('error' in resp) {
        info_box('handling_errors', resp.error);
      }
      if ('msg' in resp) {
        info_box('handling_done', resp.msg);
      }
    }
  });
  event.preventDefault ? event.preventDefault() : (event.returnValue = false);
});

$(document).on('click', '.organisations_list a', function(event){
  switch($(this).attr('class')) {
    case 'locality_org_add':
      popup_form($(this), 500);
      break;
    case 'locality_org_redaction':
      popup_form($(this), 500);
      break;
  }
  event.preventDefault ? event.preventDefault() : (event.returnValue = false);
});

$(document).on('submit', '.passport_data_form, .organisation_data_form', function(){
  var form = $(this);
  $.ajax({
    url: 'procedures.php',
    dataType: 'json',
    type: 'POST',
    data: form.serialize(),
    success: function(resp){
      if ('html' in resp) {
        $.each(resp.html, function(key, value){
          $(key).html(value);
          if (key == '.locality_organisations') {
            $('.opacity_back').fadeOut(300, function(){ $(this).remove(); });
            $('div.organisation_form').fadeOut(300);
          }
        });
      }
      if ('error' in resp) {
        info_box('handling_errors', resp.error);
      }
      if ('msg' in resp) {
        info_box('handling_done', resp.msg);
      }
    }
  });
  return false;
});

$(document).on('keyup', 'input.list_search_field', function(I){
  var box = $(this).closest('.list_search_box');
  var list = box.find('ul');
  switch(I.keyCode) {
    case 13:
    case 27:
    case 37:
    case 38:
    case 39:
    case 40:
      break;
    default:
      var val = $(this).val().toLowerCase();
      if (val.length >= 1) {
        var elem = '';
        list.find('li').each(function(){
          if ($(this).attr('class') != 'skip') {
            ($(this).find('a').length) ? elem = $(this).find('a') : elem = $(this);
            if (elem.html().toLowerCase().indexOf(val) == -1) {
              $(this).css('display', 'none');
            } else {
              $(this).css('display', 'list-item');
            }
          }
        });
      } else {
        list.find('li').css('display', 'list-item');
      }
      break;
  }
});

$(document).on('click', 'button.popup_window_close', function(){
  $(this).closest('div.popup').fadeOut(200);
  $('div.opacity_back').fadeOut(200);
});

$(document).on('click', '.info_box_close', function(){
  $('.info_box')
    .fadeOut(200, function(){ $(this).remove(); });
});

$(function(){
  $(".datepicker").datepicker();
  $('#sel_service_form select, #search_reg select, #search_reg input:not([type="checkbox"]), #search_face input').clearLink();
});