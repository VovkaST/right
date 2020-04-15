var wait_block = '<div class="ajax_response_wait"><img src="/images/ajax-loader.gif"/></div>';
var wait_block_wform = '<div class="ajax_response_wait wform"><div class="icon-block"><img src="/images/ajax-loader.gif"/><div class="text-block">Жду ответа, подождите...</div></div></div>';
var wait_block_ltext = '<div class="ajax_response_wait left-text"><div class="text-block">Загружаю...</div><div class="icon-block"><img src="/images/ajax-loader.gif"/></div></div>';
var opacity_back = $('<div class="opacity_back"></div>');
var wait_block_wback = '<div class="ajax_response_wait wback"><div class="icon-block"><img src="/images/ajax-loader.gif"/><div class="text-block">Жду ответа, подождите...</div></div></div>';

function getRandom(min, max) {
  if (min == undefined && max == undefined) {
    min = Math.random() * 10;
    max = Math.random() * 1000000000;
    return Math.round(Math.random() * (max - min) + Math.random() * 3210);
  } else {
    return Math.round(Math.random() * (max - min) + min);
  }
}

function getXMLHttpRequest() {
  var XMLHttp;
  try {
    XMLHttp = new XMLHttpRequest();
  }
  catch(e) {
    try {
      XMLHttp = new ActiveXObject("Msxml2.XMLHTTP");
    }
    catch(e) {
      try {
        XMLHttp = new ActiveXObject("Microsoft.XMLHTTP");        
      }
      catch(e) {
        XMLHttp = false;
      }
    }
  }
  return XMLHttp;
}

if (document.getElementsByClassName) {

	getElementsByClass = function(classList, node) {    
		return (node || document).getElementsByClassName(classList)
	}

} else {

	getElementsByClass = function(classList, node) {			
		var node = node || document,
		list = node.getElementsByTagName('*'), 
		length = list.length,  
		classArray = classList.split(/\s+/), 
		classes = classArray.length, 
		result = [], i,j
		for(i = 0; i < length; i++) {
			for(j = 0; j < classes; j++)  {
				if(list[i].className.search('\\b' + classArray[j] + '\\b') != -1) {
					result.push(list[i])
					break
				}
			}
		}
	
		return result
	}
}

function addClass(o, c) {
  var re = new RegExp("(^|\\s)" + c + "(\\s|$)", "g");
  if (re.test(o.className)) return
    o.className = (o.className + " " + c).replace(/\s+/g, " ").replace(/(^ | $)/g, "");
}

function removeClass(o, c) {
  var re = new RegExp("(^|\\s)" + c + "(\\s|$)", "g");
  o.className = o.className.replace(re, "$1").replace(/\s+/g, " ").replace(/(^ | $)/g, "");
}

function hasClass(elem, cls) {
  return elem.className.match(new RegExp('(\\s|^)' + cls + '(\\s|$)'));
}

function in_array(what, where) {
  for (var i = 0; i < where.length; i++) {
    if (what == where[i])
      return i;
  }
  return -1;
}

function my_select_reset_js(elem) {
  var i = elem.getElementsByTagName('input')[0];
  i.value = '';
  i.onchange();
  getElementsByClass('my_select_button_value', elem)[0].innerHTML = '';
  var a = elem.getElementsByTagName('a');
  for (var i = 0; i < a.length; i++) {
    if (hasClass(a[i], 'selected')) {
      removeClass(a[i], 'selected');
    }
  }
}

function order_recalc(elts) {
  var i = 1;
  $(elts).each(function(){
    $(this).find('.order_number').text(i++);
  });
}

function json_response_handling(resp) {
  if (resp === null) return false;
  var r;
  if ('html' in resp) {
    $.each(resp.html, function(key, value){
      r = $(key);
      r.html(value);
      r.find('div.my_select').my_select();
      r.find('.datepicker').datepicker();
      r.find('.ajax_quick_search').ajax_quick_search();
      r.find('#lightGallery').lightGallery({
        thumbnail: true,
        animateThenb: true,
        showThumbByDefault: true,
        mode: 'lg-fade'
      });
    })
  }
  if ('error' in resp) {
    info_box('handling_errors', resp.error);
  }
  if ('msg' in resp) {
    info_box('handling_done', resp.msg);
  }
  if ('updates' in resp) {
    $.each(resp.updates, function(mtd, elts) {
      switch (mtd) {
        case 'resetForm':
          $.each(elts, function(n, form) {
            $(form).trigger('reset');
          });
          break;
        case 'fadeOut':
          $.each(elts, function(n, elt) {
            $(elt).fadeOut(500, function(){
              $(this).remove();
              if ('order_recalc' in resp.updates) {
                $.each(resp.updates.order_recalc, function(n, e) {
                  order_recalc(e);
                });
              }
            });
          });
          break;
        case 'remove':
          $.each(elts, function(elt, cls) {
            $(elt).remove();
          });
          break;
        case 'removeClass':
          $.each(elts, function(elt, cls) {
            $(elt).removeClass(cls);
          });
          break;
        case 'addClass':
          $.each(elts, function(elt, cls) {
            $(elt).addClass(cls);
          });
          break;
        case 'append':
          $.each(elts, function(par, elt) {
            $(par).append(
              $(elt).hide().fadeIn(200).css('display', 'block')
            );
          });
          break;
        case 'after':
          $.each(elts, function(par, elt) {
            $(par).after(elt);
          });
          break;
        case 'replaceWith':
          $.each(elts, function(par, elt) {
            $(par).replaceWith(elt);
          });
          break;
        case 'text':
          $.each(elts, function(elt, cap) {
            $(elt).text(cap);
          });
          break;
        case 'order_recalc':
          $.each(elts, function(n, elt) {
            order_recalc(elt);
          });
          break;
      }
    });
  }
}

function getAttributes(elem) {
  var arr = {};
  $.each(elem.attributes, function() {
    if (this.specified) {
      arr[this.name] = this.value;
    }
  });
  return arr;
}

(function($){
  $.fn.my_select = function(){
  
    function select_clear(elem) {
      elem.find('input.my_select_search').val('');
      elem.find('li').show();
    }
    
    function hide_select(elem){
      elem.closest('.my_select').find('ul.my_select_list').slideUp(200, function(){ 
        select_clear($(this));
        elem.removeClass('wrapped');
      });
    }
    
    return this.each(function(){
      var $my_select = $(this);
      var select_button = $my_select.find('.my_select_button');
      var my_select_data = $my_select.find('input[type="hidden"]');
      var list = $my_select.find('ul.my_select_list');
      var my_select_search = list.find('input.my_select_search');
      ($my_select.attr('online') != undefined) ? online = true : online = false;
      if (online) {
        var rvs = list.find('li:not([class="skip"])');
      }
      
      select_button.click(function() {
        var $that = $(this).closest('div.my_select');
        if ($that.hasClass('wrapped')) {
          hide_select($that);
        } else {
          hide_select($('div.my_select.wrapped'));
          $that
            .addClass('wrapped')
            .find('ul.my_select_list').slideDown(200, function(){ my_select_search.focus(); });
        }
        
      });
      
      /*select_button.keydown(function(key){
        switch(key.keyCode) {
          case 38: case 40:
            list.slideDown(200);
            $(this).addClass('wrapped');
            my_select_search.focus();
            break;
          case 27:
            if (list.css('display') != 'none') list.slideUp(200, function(){ select_clear($(this)); });
            break;
          default:
            break;
        }
      });*/
      
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
            if (online) {
              $.ajax({
                url: 'procedures.php',
                dataType: 'json',
                type: 'POST',
                data: {ajsrch: my_select_data.attr('id'), value: val},
                success: function(resp) {
                  if ('html' in resp) {
                    list.find('li:not([class*="skip"])').remove();
                    list.append(resp.html);
                  }
                }
              });
            } else {
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
            }
            break;
        }
        
      });
      
      list.find('li a').on('click', function(event){
        $my_select.removeClass('wrapped');
        list.slideUp(200, function(){
          select_clear($(this));
          my_select_search.val('');
          list.find('li')
            .show()
            .find('a.selected').removeClass('selected');
          $(event.target || window.event.srcElement).addClass('selected');
        });
        select_button.children('span').text($(this).text());
        my_select_data
          .val($(this).attr('id'))
          .change();
        event.preventDefault ? event.preventDefault() : (event.returnValue = false);
      });
      
      $(document).on('click', function(event){
        (event.target) ? trg = event.target : trg = window.event.srcElement;
        var $wrapped = $('div.my_select.wrapped');
        if (!$wrapped.is(trg) & $wrapped.has(trg).length === 0) {
          $wrapped.find('ul.my_select_list').slideUp(200,  function(){ 
            select_clear($(this));
            $wrapped.removeClass('wrapped');
          });
        }
      });
      
      
    })
  };
})(jQuery);

(function($){
  $.fn.ajax_quick_search = function(o){
  
    o = $.extend({
      script: 'procedures.php',
      search_group: null,
      fields: null,
      ajax_timer_limit: 300,
      ajax_timer: null,
      ajax_result: null,
      validClass: 'valid',
      notValidClass: 'not-valid'
    }, o);
    
    ajax_query = function(event){
      (event.target) ? $trg = $(event.target) : $trg = $(window.event.srcElement);
      var wait = $('<div class="ajax_quick_search_wait"><img src="/images/ajax-loader.gif"/></div>');
      var r_place = $trg.next('.ajax_search_result');
      var variants = '';
      var search_group = $trg.attr('ajax_group');
      var fields = $('input[ajax_group="' + search_group + '"]');
      for (i = 0; i < fields.length; i++) {
        if (fields[i].type == 'text' & fields[i].value == fields[i].placeholder) {
          delete fields[i];
        }
      }
      $.ajax({
        url: o.script,
        dataType: 'json',
        type: 'POST',
        data: 'ajsrch=' + search_group + '&' + fields.serialize(),
        beforeSend: function(){
          $trg.after(wait);
          o.ajax_result = null;
          $('.ajax_search_result').html('');
        },
        success: function(resp){
          o.ajax_result = resp;
          if ('html' in resp) {
            $.each(resp.html, function(key, value){
              variants += '<div class="ajax_search_variant" id="' + key + '">' + value + '</div>';
              /* else {
                if (form.attr('class') == 'organisation_data_form' && field.attr('name') == 'organisation_text') { $('.popup_window_button_box.save_box').html('<button type="submit" class="popup_window_save">Сохранить</button>'); }
              }*/
            });
            r_place
              .html(variants)
              .slideDown(100);
          }
        },
        complete: function(resp){
          if (!variants) {
            r_place.slideUp(100);
          }
          wait.remove();
        }
      });
    };
    
    this.each(function(event) {

      var $that = $(this);
      var ifb = $that.closest('.input_field_block');
      hdfld = ifb.find('input[type="hidden"]');
      var search_result = null;
      search_result = ifb.find('.ajax_search_result');
      var variants = null;
      var value = null;
      
      (ifb.attr('validate') != undefined) ? validate = true : validate = false;
      
      if (validate) {
        if ($that.val() != '' & hdfld.val() != '') ifb.addClass(o.validClass);
        if (($that.val() != '' || hdfld.val() != '') & ($that.val() == '' || hdfld.val() == '')) ifb.addClass(o.notValidClass);
      }
      
      $that.keydown(function(){
        value = $(this).val();
      });
      
      $that.keyup({validate: validate, ifb: ifb, hdfld: hdfld}, function(K) {
        variants = search_result.find('.ajax_search_variant');
        if (
          (K.keyCode > 47 & K.keyCode < 58) || // цифры верхние
          (K.keyCode > 95 & K.keyCode < 106) || // цифры доп.клавиатуры
          (K.keyCode > 64 & K.keyCode < 91) || // буквы
          K.keyCode == 8 || // backspace
          K.keyCode == 32 || // space
          K.keyCode == 46 || // delete
          K.keyCode == 186 || // ж
          K.keyCode == 188 || // б
          K.keyCode == 190 || // ю
          K.keyCode == 191 || // точка
          K.keyCode == 219 || // х
          K.keyCode == 221 || // ъ
          K.keyCode == 222 // э
        ) {
          clearTimeout(o.ajax_timer);
          if ($that.val().length >= 1) {
            o.search_group = $(this).attr('ajax_group');
            o.fields = $('input[ajax_group="' + o.search_group + '"]');
            for (i = 0; i < o.fields.length; i++) {
              if (o.fields[i].type == 'text' & o.fields[i].value == o.fields[i].placeholder) {
                delete o.fields[i];
              }
            }
            o.ajax_timer = setTimeout(function(){ ajax_query(K) }, o.ajax_timer_limit);
            if (K.data.validate & $that.val() != value) {
              K.data.hdfld.val('');
              K.data.ifb.removeClass(o.validClass).addClass(o.notValidClass);
            }
          } else {
            K.data.hdfld.val('');
            K.data.ifb.removeClass(o.validClass + ' ' + o.notValidClass);
          }
        } else if (K.keyCode == 40) { // стрелка вниз
          if (variants.length) {
            search_result.slideDown(100);
          }
        } else if (K.keyCode == 27) { // escape
          search_result.slideUp(100);
        }
      });
      
      $that.focus(function(){
        variants = search_result.find('.ajax_search_variant');
        if (variants.length) {
          search_result.slideDown(100);
        }
      });
      
      
      search_result.on('click', {validate: validate, ifb: ifb, hdfld: hdfld}, function(event){
        (event.target) ? $trg = $(event.target) : $trg = $(window.event.srcElement);
        var cf = null;
        if (o.ajax_result.group_result) {
          var selector = null;
          $.each(o.ajax_result.group_result[$trg.attr('id')], function(f, v) {
            selector = '[ajax_group="' + o.search_group + '"][name="' + f + '"]';
            if ($(selector).length) {
              cf = $(selector);
              cf.val(v);
            }
          });
        } else {
          $.each(o.ajax_result.html, function(f, v){
            cf = $('[ajax_group="' + o.search_group + '"][name="' + f + '"]');
            cf.val(v);
          });
        }
        if (event.data.validate) {
          ifb.removeClass(o.notValidClass).addClass(o.validClass);
        }
        cf.change();
        $(this).html('');
      });
    });
  };
})(jQuery);

(function($){
  $.fn.sys_buttons = function(options){
    var stmt = 1;
    var sign_min = '&ndash;';
    var sign_max = '+';
    var text_min = 'Свернуть';
    var text_max = 'Развернуть';
    
    options = $.extend({
      min: true,
      cls: true,
      def: stmt
    }, options);
  
    return this.each(function(){
      var $block = $(this);
      var tb = $block.find('.toggled-block');
      var $sys_bb = $('<div class="system-buttons-block"></div>');
      
      if (!options.min && !options.max)
        return false;
      
      $block.append($sys_bb);
      
      
      if (options.min && tb.length) {
        var $min = $('<div class="system-button minimize"></div>');
      
        if (options.def != 1) {
          if (!$block.hasClass('minimized'))
            $block.addClass('minimized');
          $min
            .html(sign_max)
            .prop('title', text_max);
        } else {
          if (!$block.hasClass('maximized'))
            $block.addClass('maximized');
          $min
            .html(sign_min)
            .prop('title', text_min);
        }
        
        $sys_bb.append($min);
        
        $min.click(function(){
          if ($block.hasClass('minimized')) {
            $block
              .removeClass('minimized')
              .addClass('maximized');
            $min
              .html(sign_min)
              .prop('title', text_min);
          } else {
            $block
              .removeClass('maximized')
              .addClass('minimized');
            $min
              .html(sign_max)
              .prop('title', text_max);
          }
        });

      }
      
      if (options.cls) {
        var $cls = $('<div class="system-button close">&times;</div>');
        
        $sys_bb.append($cls);
        
        $cls.click(function() {
          $block.slideUp(200, function(){
            $(this).remove();
          });
        });
      }
      
    })
  };
})(jQuery);

function reset_my_select(elem) {
  elem.find('input[type="hidden"]').each(function(){
    $(this).val('');
  });
  elem.find('.my_select_button_value').each(function(){
    $(this).text('');
  });
  elem.find('.my_select_list a.selected').each(function(){
    $(this).removeClass('selected');
  });
  
}

function highlightTableRows(tableId, hoverClass, clickClass, multiple) {
  var table = document.getElementById(tableId);
  //var table = document.getElementByTagName('table');
  
  //если не был передан четвертый аргумент, то по умолчанию принимаем его как true
  if (typeof multiple == 'undefined') multiple = true;
  
  if (hoverClass)
  {
    //регулярное выражение для поиска среди значений атрибута class элемента, имени класса обеспечивающего подсветку по наведению мыши на строку.
    //Данное рег. выражение используется и в обработчике onclick
    var hoverClassReg = new RegExp("\\b"+hoverClass+"\\b");
    
    table.onmouseover = table.onmouseout = function(e)
    {
      if (!e) e = window.event;
      var elem = e.target || e.srcElement;
      while (!elem.tagName || !elem.tagName.match(/td|th|table/i)) elem = elem.parentNode;

      //Если событие связано с элементом TD или TH из раздела TBODY
      if (elem.parentNode.tagName == 'TR' && elem.parentNode.parentNode.tagName == 'TBODY')
      {
        var row = elem.parentNode;//ряд содержащий ячейку таблицы в которой произошло событие
        //Если текущий ряд не "кликнутый" ряд, то в разисимости от события либо применяем стиль, назначая класс, либо убираем.
        if (!row.getAttribute('clickedRow')) row.className = e.type=="mouseover"?row.className+" "+hoverClass:row.className.replace(hoverClassReg," ");
      }
    };
  }

  
  if (clickClass) table.onclick = function(e)
  {
    if (!e) e = window.event;
    var elem = e.target || e.srcElement;
    while (!elem.tagName || !elem.tagName.match(/td|th|table/i)) elem = elem.parentNode;

    //Если событие связано с элементом TD или TH из раздела TBODY
    if (elem.parentNode.tagName == 'TR' && elem.parentNode.parentNode.tagName == 'TBODY')
    {
      //регулярное выражение для поиска среди значений атрибута class элемента, имени класса обеспечивающего подсветку по клику на строке.
      var clickClassReg = new RegExp("\\b"+clickClass+"\\b");
      var row = elem.parentNode;//ряд содержащий ячейку таблицы в которой произошло событие
      
      //Если текущий ряд уже помечен стилем как "кликнутый"
      if (row.getAttribute('clickedRow'))
      {
        row.removeAttribute('clickedRow');//убираем флаг того что ряд "кликнут"
        row.className = row.className.replace(clickClassReg, "");//убираем стиль для выделения кликом
        row.className += " "+hoverClass;//назначаем класс для выделения строки по наведею мыши, т.к. курсор мыши в данный момент на строке, а выделение по клику уже снято
      }
      else //ряд не подсвечен
      {
        //если задана подсветка по наведению на строку, то убираем её
        if (hoverClass) row.className = row.className.replace(hoverClassReg, "");
        row.className += " "+clickClass;//применяем класс подсветки по клику
        row.setAttribute('clickedRow', true);//устанавливаем флаг того, что ряд кликнут и подсвечен
        
        //если разрешена подсветка только последней кликнутой строки
        if (!multiple)
        {
          var lastRowI = table.getAttribute("lastClickedRowI");
          //Если то текущей строки была кликнута другая строка, то снимаем с неё подсветку и флаг "кликнутости"
          if (lastRowI!==null && lastRowI!=='' && row.sectionRowIndex!=lastRowI)
          {
            var lastRow = table.tBodies[0].rows[lastRowI];
            lastRow.className = lastRow.className.replace(clickClassReg, "");//снимаем подсветку с предыдущей кликнутой строки
            lastRow.removeAttribute('clickedRow');//удаляем флаг "кликнутости" с предыдущей кликнутой строки
          }
        }
        //запоминаем индекс последнего кликнутого ряда
        table.setAttribute("lastClickedRowI", row.sectionRowIndex);
      }
    }
  };
}

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

function popup_window(popupData, wid) {
  wid = (wid | 500);
  var win = $(window);
  var popup_form = '';
  var opacity_back = $('<div class="opacity_back" style="height: '+$(document).height()+'px; display: none;"></div>');
  $.each(popupData.html, function(key, value){
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
  $('body').append(
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
    .fadeIn(300)
    .find('.info_box').fadeIn(300);
  opacity_back.fadeIn(300);
  $('.datepicker').datepicker();
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
        popup_window(resp, wid)
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
        link.css("color", "#BE4141");
        next_block.fadeIn(200).css('display', 'table-cell');
      }
      json_response_handling(resp);
    }
  });
}



function restore_data(par) {
  $.ajax({
    url: 'procedures.php',
    dataType: 'json',
    type: 'POST',
    data: 'restore='+par,
    success: function(resp){
      json_response_handling(resp);
    }
  });
}

function egrul_ip_data(pars) {
  if (!pars) return false;
  $.ajax({
    url: 'procedures.php',
    dataType: 'json',
    type: 'POST',
    data: pars,
    success: function(resp){
      json_response_handling(resp);
    }
  });
}

function online_changes(curt, sec) {
  var rrsadd;
  var rrsnew;
  var isActive;
  var lqt = new Date;
  lqt.setTime(curt + '000');
  var oa = $('.online_addons');
  setInterval(function(){
    if (!isActive)
      $.ajax({
        url: 'procedures.php',
        type: 'POST',
        dataType: 'json',
        data: {method: oa.attr('group'), ovd: oa.attr('ovd'), lqt: lqt.getTime()},
        beforeSend: function() {
          rrsadd = null;
          rrsnew = null;
          isActive = true;
        },
        success: function(resp) {
          rrsadd = $('.online_addons .result_row:first-child');
          if (!rrsadd.length)
            rrsnew = $('.online_addons');
          if (resp.html['updates']) {
            lqt = new Date;
            var r = $(resp.html['updates']).css('display', 'none');
            (rrsadd.length) ? rrsadd.before(r) : rrsnew.append(r);
            r.fadeIn(1000);
            r.removeClass('new');
          }
          isActive = false;
        }
      });
  }, sec * 1000);
}

/*function reset_form_js(form) {
  var arr = ['region', 'district', 'city', 'locality', 'street', 'drug', 'id'];
  var form = this;
  var elem;
  var inputs = form.getElementsByTagName('input');
  for (var i = 0; i < inputs.length; i++) {
    if ((inputs[i].getAttribute('type') == 'hidden') && (in_array(inputs[i].getAttribute('name'), arr) != -1)) {
      inputs[i].removeAttribute('value');
    }
  }
  elem = getElementsByClass('my_select', form);
  for (var i = 0; i < elem.length; i++) {
    my_select_reset_js(elem[i]);
  }
  elem = getElementsByClass('file_input_value', form);
  for (var i = 0; i < elem.length; i++) {
    if (elem[i].tagName == 'SPAN')
      elem[i].innerHTML = 'Выберите файл...';
  }
  elem = getElementsByClass('file_upload', form);
  for (var i = 0; i < elem.length; i++) {
    if (elem[i].tagName == 'SPAN')
      elem[i].removeAttr('disabled');
  }
  elem = getElementsByClass('ajax_search_result', form);
  for (var i = 0; i < elem.length; i++) {
    elem[i].innerHTML = '';
  }
}

$(document).on('reset', 'form', function(){
  reset_form_js($(this)[0]);
});
*/

/*var forms = document.getElementsByTagName('form');
for (var f = 0; f < forms.length; f++) {
  forms[f].onreset = function() {
    
  };
}*/

$(window).scroll(function(){
  if ($(window).scrollTop() + $(window).height() + 140 >= $(document).height()) {
    var ar = $('#add-ready');
    if (ar.length) {
      $.ajax({
        url: 'procedures.php',
        dataType: 'json',
        type: 'POST',
        data: getAttributes(ar[0]),
        beforeSend: function() {
          ar.replaceWith(wait_block_ltext);
        },
        success: function(resp) {
          json_response_handling(resp);
        }
      });
    }
  }
});

$(document).on('submit', '.decision_block .file_block form, .file_analyse_block form', function(){
  $('.uploading_file_block').append(wait_block);
});

$(document).on('click', '.uploading_file_block .file_upload', function(event){
  (event.target) ? $trg = $(event.target) : $trg = $(window.event.srcElement);
  var form = $trg.closest('form');
  if ($.inArray($(this).attr('status'), ['disabled', 'undefined']) === -1) {
    if (!form.find('input[type="file"]').length) {
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
        form
          .attr({
              enctype: 'multipart/form-data',
              method: 'POST',
              action: 'procedures.php',
              target: 'frame'
            })
          .submit();
        form
          .removeAttr('enctype')
          .removeAttr('method')
          .removeAttr('action')
          .removeAttr('target');
        form[0].reset();
      } else {
        if (form.attr('file_required') != 'true') {
          form.submit();
        }
      }
    }
  }
  return false;
});

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

$(document).on('click', '.face_cell .delete_link', function(event){
  (event.target) ? $trg = $(event.target) : $trg = $(window.event.srcElement);
  var par = $trg.closest('.face_cell');
  
  $(".msg_box#delete").remove();
  var msg_box = $('\
    <form class="msg_box" id="delete" method="POST"> \
      <input type="hidden" value="'+$(this).attr("id")+'" name="id"/> \
      <img class="attention_img" src="http://.../images/error_record.png" height="40" width="40"> \
      <span class="msg_txt"><strong>Вы действительно хотите удалить этот материал?</strong></span> \
      <span style="display: block;">Причина:</span>  \
      <textarea rows="3" name="reason" id="reason"></textarea>\
      <div class="field_box"> \
        <span class="field_name">Сотрудник:</span> \
        <div class="input_field_block"> \
          <input type="text" name="deleter" autocomplete="off" placeholder="Сотрудник"/> \
          <div class="ajax_search_result"></div> \
        </div> \
      </div> \
      <div class="add_button_box"> \
        <div class="button_block"><span class="button_name">Удалить</span></div> \
      </div> \
    </form> \
  ');
  par.append(msg_box);
  
  msg_box
    .css({
      "left": event.pageX-msg_box.outerWidth()+"px", 
      "top": event.pageY-msg_box.outerHeight()+"px"
    })
    .fadeIn(200)
    .find('#reason')
      .focus();
  event.preventDefault ? event.preventDefault() : (event.returnValue = false);
});

$(document).on('click', '.delete_relation', function(event){
  var e = $(this);
  var id = e.attr('id') || 0;
  if (e.attr('group')) {
    $.ajax({
      url: 'procedures.php',
      dataType: 'json',
      type: 'POST',
      data: {del_relation : id, group : e.attr('group'), section: e.attr('section')},
      success: function(resp) {
        json_response_handling(resp);
      }
    });
  }
});

$(document).on('change', '.file_input', function(){
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

$(document).on('submit', '.msg_box#delete', function(){
  var $form = $(this);
  $.getJSON('procedures.php', 'method=delete&' + $(this).serialize(), function(resp) {
    if ('msg' in resp && resp.msg == '200') {
      $form.closest('.info_row').remove();
    } else {
      json_response_handling(resp);
    }
  });
  return false;
});

$(document).on('click', '.links_block a, .actions_list a', function(event) {
  var a = $(this);
  var wbf = $(wait_block_wform);
  var lb = a.closest('.actions_block');
  if (a.attr('method')) {
    $.ajax({
      url: 'procedures.php',
      type: 'POST',
      dataType: 'json',
      //data: {method: a.attr('method'), id: a.attr('id'), file: a.attr('file')},
      data: getAttributes(a[0]),
      beforeSend: function() {
        if (lb)
          lb.append(
            wbf.fadeIn(100)
          );
      },
      success: function(resp) {
        if ('msg' in resp && resp.msg == 'ready') {
          location.href = a.attr('href');
        } else {
          json_response_handling(resp);
          if (('html' in resp) && (('#file_preview' in resp.html) || ('#preview' in resp.html))) {
            var $win = $(window);
            var $p = null;
            ($('#file_preview').length) ? $p = $('#file_preview') : $p = $('#preview');
            $p.find('.opacity_back')
              .css('height', $(document).height())
              .fadeIn(200)
              .click(function(){
                $(this).fadeOut(300, function(){ $(this).remove(); });
                $p.find('.info_box').fadeOut(300);
              });
            $p.find('.info_box').each(function(){
              var href = $(this).find('.add_button_box').attr('href');
              $(this)
                .css({
                    top: (($win.height() - $(this).outerHeight()) / 2 + $win.scrollTop()) + "px", 
                    left: (($win.width() - $(this).outerWidth()) / 2) + "px"
                  })
                .fadeIn(200)
                .find('.add_button_box *').click(function(){
                  $p.find('.opacity_back').fadeOut(300);
                  $p.find('.info_box').fadeOut(300);
                });
            });
          }
        }
      },
      error: function (e, st){
        alert(st);
      },
      complete: function() {
        if (lb)
          wbf.fadeOut(100, function() {
            wbf.remove();
          })
      }
    });
    event.preventDefault ? event.preventDefault() : (event.returnValue = false);
  }
});

$(document)
  .on('reset', 'form', function(event){
    var $form = $(this);
    $form.find('input').each(function(){
      var el = $(this);
      switch (el.attr('type')) {
        case 'hidden':
          if ($.inArray(el.attr('name'), ['region', 'district', 'city', 'locality', 'street', 'drug', 'id']) != -1) {
            el.val('');
          }
          break;
        case 'text': el.val(''); break;
        case 'radio':
        case 'checkbox':
          if (el.prop('checked')) 
            el.prop('checked', false);
          break;
      }
    });
    $form.find('textarea').val('');
    $form.find('.label-box .checked').removeClass('checked');
    $form.find('span.my_select_button_value').html('&nbsp;');
    $form.find('div.my_select input').val('');
    $form.find('span.file_input_value').text('Выберите файл...');
    $form.find('div.file_upload').removeAttr('disabled');
    $form.find('.my_select_list >li > a.selected').removeClass('selected');
    $form.find('.ajax_search_result').html('');
    event.preventDefault ? event.preventDefault() : (event.returnValue = false);
  })
  .on('focus', 'input#user_input, input#password_input', function(){
    $(this).closest('div.row').animate({borderBottomColor: '#FFE52D'}, 200);
  })
  .on('blur', 'input#user_input, input#password_input', function(){
    $(this).closest('div.row').animate({borderBottomColor: '#E7E8E8'}, 200);
  })
  .on('submit', '.passport_data_form, .organisation_data_form', function(){
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
  })
  .on('submit', 'form', function(){
    var $win = $(window);
    var $doc = $(document);
    var $form = $(this);
    var ob = $(opacity_back);
    
    if ($form.attr('type') == 'json') {
      $.ajax({
        url: 'procedures.php',
        dataType: 'json',
        type: 'POST',
        data: $form.serialize(),
        beforeSend: function() {
          if ($form.attr('wait_back')) {
            var wb = $(wait_block_wback);
            $('body').append(
              ob
                .css('height', $doc.height())
                .fadeIn(200)
                .append(wb)
            );
            wb
              .css({
                top: (($win.height() - wb.outerHeight()) / 2 + $win.scrollTop()) + "px", 
                left: (($win.width() - wb.outerWidth()) / 2) + "px"
              })
              .fadeIn(200);
          }
        },
        success: function(resp) {
          json_response_handling(resp);
          if (resp.html && !resp.error && !$form.is('.main_form')) {
            $form[0].reset();
          }
          var $rnb = $('.registration_block .registration_number_block');
          if (!$rnb.length)
            $rnb = $('.registration_block .response_place');
          $rnb.find('.opacity_back')
            .css('height', $doc.height())
            .fadeIn(200)
            .click(function(){
              if ($(this).attr('href'))
                location.replace($(this).attr('href'));
            });
          $rnb.find('.info_box').each(function(){
            $(this).find('.add_button_box').each(function(){
              var href = $(this).attr('href');
            });
            $(this)
              .css({
                  top: (($win.height() - $(this).outerHeight()) / 2 + $win.scrollTop()) + "px", 
                  left: (($win.width() - $(this).outerWidth()) / 2) + "px"
                })
              .fadeIn(200)
              .find('.add_button_box *').click(function(){
                if ($(this).attr('href')) {
                  location.replace($(this).attr('href'));
                } else {
                  location.replace($(this).closest('.add_button_box').attr('href'));
                }
              });
              
          });
        },
        complete: function() {
          if ($form.attr('wait_back'))
            ob.fadeOut(200, function() { $(this).remove(); });
        }
      });
      return false;
    } else if ($form.attr('id') == 'auth_form') {
      $.ajax({
        url: '/auth/login.php',
        dataType: 'json',
        type: 'POST',
        data: $form.serialize(),
        beforeSend: function(){
          var h = $('.auth_block').outerHeight();
          $('.auth_form_block .block_header').after(
            $('<div class="waiting_background"><img src="/images/ajax-loader.gif"/></div>')
              .css('height', h)
              .fadeIn(200)
          );
        },
        success: function(resp) {
          json_response_handling(resp);
        },
        complete: function(){
          $('.auth_form_block .waiting_background').fadeOut(200, function(){
            $(this).remove();
          });
        }
      });
      return false;
    }
  })
  .on('change', '.orientation input#regnumber', function(){
    if ($(this).val() != '') 
      $('.orientation input#wonumber').prop('checked', false).change();
  })
  .on('change', '.orientation input#wonumber', function(){
    if ($(this).prop('checked')) {
      $('.orientation input#regnumber').val('').change();
    }
  })
  .on('change', '#fa_model', function(){
    if ($(this).val()) 
      $('#fa_unkmodel')
        .prop('checked', false)
        .change();
  })
  .on('change', '#fa_unkmodel', function(){
    if ($(this).prop('checked')) 
      reset_my_select($('#fa_model').closest('.my_select'));
  })
  .on('change', '.work_on_the_crime .marking .my_select input[type="hidden"]', function(){
    if ($(this).val() != '') {
      $('.work_on_the_crime .marking .marking_variants').find('input').each(function(){
        if (($(this).attr('type') == 'radio') && ($(this).prop('checked')))
          $(this).prop('checked', false).change();
      });
    }
  })
  .on('change', '.main_form input, .sess_form input, .main_form textarea, .sess_form textarea', function(event){
    (event.target) ? $trg = $(event.target) : $trg = $(window.event.srcElement);
    $trg.closest('form').submit();
    if ($trg.is('.decision_block input[type="checkbox"][name="missed"]')) {
      var $cb = $('.decision_block .criminal_block');
      ($(this).prop('checked')) ? $cb.slideUp(200) : $cb.slideDown(200);
    }
  })
  .on('change', '.work_on_the_crime .marking_variants input[type="radio"]', function() {
    if ($(this).prop('checked')) {
      reset_my_select($('.work_on_the_crime .marking .my_select'));
    }
  })
  .on('mousedown', '.label-box label', function(e){
    console.log(e.target.tagName);
    if (e.which != 1) return;
    $inp = $(this).find('input');
    $inp.prop('isChecked', $inp.prop('checked'));
  })
  .on('click', '.label-box label', function(e){
    if ((e.target || window.e.srcElement).tagName == 'LABEL') return;
    var $el = $(this);
    var $inp = $el.find('input');
    if ($inp.prop('isChecked')) {
      $inp.prop('checked', false);
      if ($('.label-box').has($el)) {
        $el.closest('.field_box').removeClass('checked');
        $inp.change();
      }
    } else {
      $inp.prop('checked', true);
      if ($('.label-box').has($(e))) {
        $el.closest('.label-box').find('.checked').removeClass('checked');
        $el.closest('.field_box').addClass('checked');
      }
    }
  })
  .on('click', '.bookmark_block .item', function(event) {
    $item = $(this);
    if ($item.hasClass('current'))
      return false;
    var bb = $item.closest('.bookmark_block');
    var slu = bb.find('.item.current');
    slu.removeClass('current');
    bb.find('.' + slu.attr('target')).slideUp(200);
    $item.addClass('current');
    bb.find('.' + $item.attr('target')).slideDown(200);
  })
  .on('click', '.image_cell img', function(e){
    (e.target) ? $trg = $(e.target) : $trg = $(window.e.srcElement);
    var gr = $trg.closest('.image_cell');
    var ex = /\w{32}/;
    var hash = ex.exec(gr.prop('class'));
    if (gr.hasClass('hidden')) {
      $('.'+hash[0])
        .removeClass('hidden')
        .addClass('visible')
        .css('border-color', 'rgb(' + getRandom(1, 255) + ', ' + getRandom(1, 255) + ',' + getRandom(1, 255) + ')');
    } else {
      $('.'+hash[0])
        .removeClass('visible')
        .addClass('hidden')
        .css('border-color', 'rgb(198, 198, 198)');
    }
  });

$(function(){
  $('body').click(function(event){
    (event.target) ? $trg = $(event.target) : $trg = $(window.event.srcElement);
    if ($trg.closest('.head_menu_item').length == 0) $('.sub_menu').slideUp(300);
    if ($('div.add_button_box').has($trg).length || $('div.add_button_box').is($trg)) {
      var button;
      ($('div.add_button_box').has($trg).length) ? button = $($trg).closest('div.add_button_box') : button = $trg;
      var form = $('#' + button.attr('form'));
      (form.length) ? form.submit() : button.closest('form').submit();
    }
    if ($('.msg_box').has($trg).length == 0 && !$('.msg_box').is($trg)) $('.msg_box').fadeOut(200);
  });
  
  $('.calendar_icon').click(function(){
    $(this).closest('.datepicker_block').find('input.datepicker').focus();
  });
  
  $('.head_menu_item > a').click(function(event){
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
  
  $('.switcher_block input').change(function(){
    var form = $(this).closest('label');
    if (form.hasClass('reg_data')) {
      form.addClass('active');
      $('label.face_org_data').removeClass('active');
      $('div.reg_data').slideDown(200);
      $('div.face_org_data').slideUp(200);
    } else {
      form.addClass('active');
      $('label.reg_data').removeClass('active');
      $('div.face_org_data').slideDown(200);
      $('div.reg_data').slideUp(200);
    }
  });
  
  $('.close_message').click(function(){
    $(this).closest('.user_message')
      .slideUp(200, function(){
        $(this).remove();
      });
  });
  
  $('.official_documents .document').hover(function(){
    $(this).children('.document').slideToggle(200);
  });
  
  if ($('table#myTable').length) {
    highlightTableRows("myTable","","clickedRow",false);
  }
  
  $('a.check_all').click(function(event){
    var $a = $(this);
    var def = 'Выделить все';
    var unchk = 'Снять выделение';
    var block = $a.closest('.checkbox');
    var chk;
    ($a.text() == unchk) ? chk = false : chk = true;
    block.find('input[type="checkbox"]').each(function(){
      if (chk) {
        $(this).prop('checked', true);
      } else {
        $(this).prop('checked', false);
      }
    });
    (chk) ? $a.text(unchk) : $a.text(def);
    event.preventDefault ? event.preventDefault() : (event.returnValue = false);
  });
  
  $('a.maximize').click(function(event){
    var $a = $(this);
    var def = 'Развернуть';
    var unchk = 'Свернуть';
    var block = $('.'+$a.prop('target'));
    var v;
    ($a.text() == unchk) ? v = false : v = true;
    if (v) {
      $a.text(unchk)
      block.slideDown(200);
    } else {
      $a.text(def)
      block.slideUp(200);
    }
    event.preventDefault ? event.preventDefault() : (event.returnValue = false);
  });
  
  $('.ident_block #ruler').click(function(){
    var l = $('.ident_block .phototypes_list');
    ($(this).prop('checked')) ? l.slideDown(200).css('display', 'inline-block') : l.slideUp(200);
  });
  
  $('.ajax_quick_search').ajax_quick_search();
  $('.datepicker').datepicker();
  $(".tel_num").inputmask("(999)999-99-99");
  $(".time").inputmask("99:99");
  $('div.my_select').my_select();
  $('#lightGallery').lightGallery({
    thumbnail: true,
    animateThenb: true,
    showThumbByDefault: true,
    mode: 'lg-fade'
  });
});