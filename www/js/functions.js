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
  $.fn.my_select = function(){
    
    return this.each(function(){
      var $my_select = $(this);
      var select_button = $my_select.find('.my_select_button');
      var my_select_data = $my_select.find('input[type="hidden"]');
      var list = $my_select.find('ul.my_select_list');
      var my_select_search = list.find('input.my_select_search');
      
      $my_select.attr('id', Math.round(Math.random()*(654321 - 123456) / Math.random()));
      
      select_button.click(function(){
        list.slideToggle(200);
      });
      
      select_button.keydown(function(key){
        switch(key.keyCode) {
          case 38: case 40:
            list.slideDown(200);
            break;
          case 27:
            if (list.css('display') != 'none') list.slideUp(200);
            break;
          default:
            break;
        }
      });
      
      list.find('li a').on('click', function(){
        list.find('a.selected').each(function(){ $(this).removeClass('selected'); });
        $(this).addClass('selected');
        select_button.children('span').text($(this).text());
        my_select_data.val($(this).attr('id'));
        list.slideUp(200);
        event.preventDefault ? event.preventDefault() : (event.returnValue = false);
      });
      
      
    })
  };
})(jQuery);

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

function check_fields_req(form) {
  var color = $('body').css('background-color');
  var error = false;
  form.find('[req="true"]').each(function(){
    var elem = $(this);
    if (elem.val() == "") {
      error = true;
    }
    if (elem.attr("class") == 'email') {
      if (!check_email(elem)) {
        error = true;
      }
    }
    if (error) {
      elem.animate({backgroundColor:"rgba(255,66,45,0.5)"},200);
      elem.animate({backgroundColor:color},200);
    }
  });
  if (error) {
    return false;
  } else {
    return true;
  }
}

function toggle_file_input(){
  var button = $('.file_upload');
  var text = $('.file_input span');
  var input = $('.file_input input');
  if (button.hasClass('disabled')) {
    button.removeClass('disabled');
  } else {
    button.addClass('disabled');
  }
  if (input.prop('disabled')) {
    input.removeProp('disabled');
    text.html('Выберите файл...');
  } else {
    input.prop('disabled', true);
    text.html('&nbsp;');
  }
}

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
    var my_select_list = $('.my_select_list');
    if ($(trg).closest('.head_menu_item').length == 0) $('.sub_menu').slideUp(300);
  });
  
  $('.file_input').change(function(){
    var text = $(this).children('span');
    var file = $(this).children('input').val().replace(/^.*[\\\/]/, '');
    if (file.length > 25) {
      file = file.substr(0, 25)+'...';
    }
    if (file != '') {
      text.html(file);
    } else {
      text.html('Выберите файл...');
    }
  });
  
  $(document).on('click', '.save_str, .file_upload', function(){
    if (event.target) {
      var trg = event.target;
    } else {
      var trg = window.event.srcElement;
    }
    if ($(trg).hasClass('busy') || $(trg).hasClass('disabled')) {
      return false;
    }
    
    var form = $(trg).closest("form");
    if (check_fields_req(form)) {
      if (form.find('input[type="file"]').length == 0) {
        form.submit();
      } else {
        var isFile = true;
        form.find('input[type="file"]').each(function(){
          if ($(this).val() == '') {
            isFile = false;
            return false;
          }
        });
        if (isFile) {
          form.attr({
            enctype: 'multipart/form-data',
            method: 'POST',
            action: 'procedures.php',
            target: 'frame'
          });
          form.submit();
          form
            .removeAttr('enctype')
            .removeAttr('method')
            .removeAttr('action')
            .removeAttr('target');
        } else {
          form.submit();
        }
      }
    }
    event.preventDefault ? event.preventDefault() : (event.returnValue = false);
  });
  
  $('.upload_form').submit(function(){
    if ($(this).find('input[type="file"]').val() == '') {
      $(".uploaded_file_form").html('<div class="error"><p>Не выбран файл!</p></div>')
      return false;
    }
  });
  
  $(document).on("submit", ".uploaded_file", function(){
    $.post('procedures.php', $(this).serialize(), function(resp) {
      toggle_file_input();
      $('.uploaded_file_form')
        .html('')
        .append(resp)
        .children()
          .fadeOut(1000, function(){
            $(this).remove();
          });
    });
  return false;
});
  $('div.my_select').my_select();
  
  $(".datepicker").datepicker();
  
  $('#sel_service_form select, #search_reg select, #search_reg input:not([type="checkbox"]), #search_face input').clearLink();
});